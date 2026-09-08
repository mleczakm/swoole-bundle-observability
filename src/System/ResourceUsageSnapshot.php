<?php

declare(strict_types=1);

namespace SwooleBundle\Observability\System;

final readonly class ResourceUsageSnapshot
{
    public function __construct(
        public int $processCount,
        public int $totalRssKib,
        public int $maxProcessRssKib,
        public int $totalOpenFds,
        public int $maxProcessOpenFds,
        public int $fdSoftLimit,
        public int $tcpInUse,
        public int $tcpOrphan,
        public int $tcpTimeWait,
        public int $tcpAllocated,
    ) {}

    public function totalRssBytes(): int { return $this->totalRssKib * 1024; }
    public function maxProcessRssBytes(): int { return $this->maxProcessRssKib * 1024; }
    public function totalRssMib(): int { return $this->totalRssKib >> 10; }
    public function maxProcessRssMib(): int { return $this->maxProcessRssKib >> 10; }

    /** @return array<string, int> */
    public function toArray(): array
    {
        return [
            'process_count' => $this->processCount,
            'total_rss_mib' => $this->totalRssMib(),
            'total_rss_kib' => $this->totalRssKib,
            'max_process_rss_mib' => $this->maxProcessRssMib(),
            'total_open_fds' => $this->totalOpenFds,
            'max_process_open_fds' => $this->maxProcessOpenFds,
            'fd_soft_limit' => $this->fdSoftLimit,
            'tcp_in_use' => $this->tcpInUse,
            'tcp_orphan' => $this->tcpOrphan,
            'tcp_time_wait' => $this->tcpTimeWait,
            'tcp_allocated' => $this->tcpAllocated,
        ];
    }
}
