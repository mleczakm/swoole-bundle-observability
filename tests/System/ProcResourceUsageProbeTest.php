<?php

declare(strict_types=1);

namespace SwooleBundle\Observability\Tests\System;

use PHPUnit\Framework\TestCase;
use SwooleBundle\Observability\System\ProcResourceUsageProbe;
use Symfony\Component\Filesystem\Filesystem;

final class ProcResourceUsageProbeTest extends TestCase
{
    private string $path;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->path = sys_get_temp_dir() . '/observability-' . bin2hex(random_bytes(6));
        $this->filesystem->dumpFile($this->path . '/1/status', "VmRSS:\t2048 kB\n");
        $this->filesystem->dumpFile($this->path . '/1/fd/0', '');
        $this->filesystem->dumpFile($this->path . '/net/sockstat', "TCP: inuse 2 orphan 1 tw 3 alloc 4 mem 0\n");
        $this->filesystem->dumpFile($this->path . '/self/limits', "Max open files 1024 2048 files\n");
    }

    protected function tearDown(): void { $this->filesystem->remove($this->path); }

    public function testCapturesProcMetrics(): void
    {
        $snapshot = (new ProcResourceUsageProbe($this->path))->capture();
        self::assertSame(1, $snapshot->processCount);
        self::assertSame(2, $snapshot->totalRssMib());
        self::assertSame(1, $snapshot->totalOpenFds);
        self::assertSame(1024, $snapshot->fdSoftLimit);
        self::assertSame(4, $snapshot->tcpAllocated);
    }
}
