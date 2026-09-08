<?php

declare(strict_types=1);

namespace SwooleBundle\Observability\DependencyInjection;

use RuntimeException;
use SwooleBundle\Observability\HealthCheck\HttpClientHealthCheck;
use SwooleBundle\Observability\HealthCheck\MemoryUsageHealthCheck;
use SwooleBundle\Observability\HealthCheck\ProcessCountHealthCheck;
use SwooleBundle\Observability\Metrics\MetricsRecorder;
use SwooleBundle\Observability\Metrics\MetricsRecorderInterface;
use SwooleBundle\Observability\System\ProcResourceUsageProbe;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use SymfonyHealthCheckBundle\Check\CheckInterface;

final class SwooleBundleObservabilityExtension extends Extension
{
    public function getAlias(): string
    {
        return 'swoole_bundle_observability';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        /** @var array{health_checks: bool, max_processes: int, max_rss_mib: int, http_url: string} $config */
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->register(ProcResourceUsageProbe::class)->setAutowired(true);
        $container->register(MetricsRecorder::class)->setAutowired(true);
        $container->setAlias(MetricsRecorderInterface::class, MetricsRecorder::class);

        if (! $config['health_checks']) {
            return;
        }

        if (! interface_exists(CheckInterface::class)) {
            throw new RuntimeException('Health checks require macpaw/symfony-health-check-bundle.');
        }

        $container->register(ProcessCountHealthCheck::class)
            ->setPublic(true)
            ->setArgument('$probe', new Reference(ProcResourceUsageProbe::class))
            ->setArgument('$maxProcesses', $config['max_processes']);
        $container->register(MemoryUsageHealthCheck::class)
            ->setPublic(true)
            ->setArgument('$probe', new Reference(ProcResourceUsageProbe::class))
            ->setArgument('$maxRssMib', $config['max_rss_mib']);
        $container->register(HttpClientHealthCheck::class)
            ->setPublic(true)
            ->setArgument('$httpClient', new Reference(HttpClientInterface::class, ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE))
            ->setArgument('$url', $config['http_url']);
    }
}
