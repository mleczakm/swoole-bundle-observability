# Swoole Bundle Observability

Reusable runtime telemetry for Symfony applications hosted by Swoole. The bundle reads the
container PID namespace through `/proc`, exposes health checks, and provides a Sentry Trace
Metrics recorder suitable for HTTP workers, task workers, schedulers, and CLI commands.

## Installation

```bash
composer require mleczakm/swoole-bundle-observability
```

Register `SwooleBundle\Observability\SwooleBundleObservabilityBundle` in `config/bundles.php`.

## Configuration

```yaml
# config/packages/swoole_bundle_observability.yaml
swoole_bundle_observability:
  health_checks: true
  max_processes: 20
  max_rss_mib: 1536 # 0 reports memory without failing
  http_url: https://connectivitycheck.gstatic.com/generate_204
```

When `macpaw/symfony-health-check-bundle` is installed, add these public services to its
`health_checks` list:

```yaml
- { id: SwooleBundle\Observability\HealthCheck\ProcessCountHealthCheck }
- { id: SwooleBundle\Observability\HealthCheck\MemoryUsageHealthCheck }
- { id: SwooleBundle\Observability\HealthCheck\HttpClientHealthCheck }
```

The checks report process count, total and largest-process RSS, open file descriptors, the
soft FD limit, and TCP in-use/orphan/TIME_WAIT/allocated counters. They read only `/proc`
and never shell out.

## Sentry metrics

Inject `SwooleBundle\Observability\Metrics\MetricsRecorderInterface` and call `distribution()`.
It uses Sentry's current Trace Metrics API and flushes after each write, which is required for
long-running workers that do not execute Symfony's HTTP termination listeners.

```php
$metrics->distribution('runtime.memory.rss_total', $snapshot->totalRssBytes(), [], Sentry\Unit::byte());
```

Use `ProcResourceUsageProbe::capture()` for scheduled samples and structured logs. The bundle
does not impose a scheduler: applications choose their own interval and transport.

## Operational guidance

Choose thresholds above normal worker overlap and below the host/container limit. The defaults
match a Swoole master, manager, one HTTP worker, and two task workers. Pair failing `/health`
responses with Docker autoheal or an orchestrator restart policy. TCP and FD values are
diagnostic only; RSS and process count determine health.

## Development

```bash
composer install
vendor/bin/phpunit
vendor/bin/phpstan analyse src tests --level=max
```

## License

MIT
