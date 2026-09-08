<?php

declare(strict_types=1);

namespace SwooleBundle\Observability\System;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

final readonly class ProcResourceUsageProbe
{
    private Filesystem $filesystem;

    public function __construct(private string $procPath = '/proc') { $this->filesystem = new Filesystem(); }

    public function capture(): ResourceUsageSnapshot
    {
        $directories = glob($this->procPath . '/[0-9]*', GLOB_ONLYDIR) ?: [];
        $processes = $rss = $maxRss = $fds = $maxFds = 0;

        foreach ($directories as $directory) {
            $status = $this->read($directory . '/status');
            if ($status === null) { continue; }

            $processes++;
            if (preg_match('/^VmRSS:\s+(\d+)\s+kB/m', $status, $matches) === 1) {
                $value = (int) $matches[1];
                $rss += $value;
                $maxRss = max($maxRss, $value);
            }

            $count = $this->countEntries($directory . '/fd');
            if ($count !== null) {
                $fds += $count;
                $maxFds = max($maxFds, $count);
            }
        }

        [$inUse, $orphan, $timeWait, $allocated] = $this->tcpStats();

        return new ResourceUsageSnapshot($processes, $rss, $maxRss, $fds, $maxFds, $this->fdLimit(), $inUse, $orphan, $timeWait, $allocated);
    }

    /** @return array{int, int, int, int} */
    private function tcpStats(): array
    {
        $contents = $this->read($this->procPath . '/net/sockstat');
        if ($contents === null || preg_match('/^TCP:\s+inuse\s+(\d+)\s+orphan\s+(\d+)\s+tw\s+(\d+)\s+alloc\s+(\d+)/m', $contents, $matches) !== 1) {
            return [0, 0, 0, 0];
        }

        return [(int) $matches[1], (int) $matches[2], (int) $matches[3], (int) $matches[4]];
    }

    private function fdLimit(): int
    {
        $contents = $this->read($this->procPath . '/self/limits');
        return $contents !== null && preg_match('/^Max open files\s+(\d+)/m', $contents, $matches) === 1 ? (int) $matches[1] : 0;
    }

    private function read(string $path): ?string
    {
        try { return $this->filesystem->readFile($path); } catch (IOException) { return null; }
    }

    private function countEntries(string $path): ?int
    {
        try { return iterator_count(new \FilesystemIterator($path, \FilesystemIterator::SKIP_DOTS)); } catch (\UnexpectedValueException) { return null; }
    }
}
