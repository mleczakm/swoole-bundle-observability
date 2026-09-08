<?php

declare(strict_types=1);

namespace SwooleBundle\Observability\HealthCheck;

use SwooleBundle\Observability\System\ProcResourceUsageProbe;
use SymfonyHealthCheckBundle\Check\CheckInterface;
use SymfonyHealthCheckBundle\Dto\Response;

final readonly class ProcessCountHealthCheck implements CheckInterface
{
    public function __construct(private ProcResourceUsageProbe $probe, private int $maxProcesses) {}

    #[\Override]
    public function check(): Response
    {
        try {
            $count = $this->probe->capture()->processCount;
        } catch (\Throwable $e) {
            return new Response('process_count', false, 'Process count probe failed: ' . $e->getMessage());
        }

        $healthy = $count <= $this->maxProcesses;

        return new Response('process_count', $healthy, $healthy ? 'Process count is healthy' : sprintf('Process count %d exceeds threshold %d', $count, $this->maxProcesses), ['count' => $count, 'threshold' => $this->maxProcesses]);
    }
}
