<?php

declare(strict_types=1);

namespace SwooleBundle\Observability\HealthCheck;

use SwooleBundle\Observability\System\ProcResourceUsageProbe;
use SymfonyHealthCheckBundle\Check\CheckInterface;
use SymfonyHealthCheckBundle\Dto\Response;

final readonly class MemoryUsageHealthCheck implements CheckInterface
{
    public function __construct(private ProcResourceUsageProbe $probe, private int $maxRssMib) {}

    #[\Override]
    public function check(): Response
    {
        try {
            $snapshot = $this->probe->capture();
        } catch (\Throwable $e) {
            return new Response('memory_usage', false, 'Resource usage probe failed: ' . $e->getMessage());
        }

        $healthy = $this->maxRssMib === 0 || $snapshot->totalRssMib() <= $this->maxRssMib;

        return new Response('memory_usage', $healthy, $healthy ? 'Memory usage is healthy' : sprintf('Resident memory %d MiB exceeds threshold %d MiB', $snapshot->totalRssMib(), $this->maxRssMib), $snapshot->toArray() + ['threshold_mib' => $this->maxRssMib]);
    }
}
