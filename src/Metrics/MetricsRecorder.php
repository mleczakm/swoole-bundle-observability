<?php

declare(strict_types=1);

namespace SwooleBundle\Observability\Metrics;

use Sentry\Unit;

use function Sentry\trace_metrics;

final readonly class MetricsRecorder implements MetricsRecorderInterface
{
    #[\Override]
    public function distribution(string $name, int|float $value, array $attributes = [], ?Unit $unit = null): void
    {
        trace_metrics()->distribution($name, $value, $attributes, $unit);
        trace_metrics()->flush();
    }
}
