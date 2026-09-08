<?php

declare(strict_types=1);

namespace SwooleBundle\Observability\Tests\HealthCheck;

use PHPUnit\Framework\TestCase;
use SwooleBundle\Observability\HealthCheck\HttpClientHealthCheck;
use SwooleBundle\Observability\HealthCheck\MemoryUsageHealthCheck;
use SwooleBundle\Observability\HealthCheck\ProcessCountHealthCheck;
use SwooleBundle\Observability\System\ProcResourceUsageProbe;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Filesystem\Filesystem;

final class HealthChecksTest extends TestCase
{
    public function testRuntimeAndHttpChecksPass(): void
    {
        $probe = new ProcResourceUsageProbe();
        self::assertTrue((new ProcessCountHealthCheck($probe, PHP_INT_MAX))->check()->getResult());
        self::assertTrue((new MemoryUsageHealthCheck($probe, 0))->check()->getResult());
        self::assertTrue((new HttpClientHealthCheck(new MockHttpClient(new MockResponse('', ['http_code' => 204])), 'https://example.test'))->check()->getResult());
    }

    public function testThresholdsAndUnexpectedHttpStatusFail(): void
    {
        $filesystem = new Filesystem();
        $path = sys_get_temp_dir() . '/observability-health-' . bin2hex(random_bytes(6));
        $filesystem->dumpFile($path . '/1/status', "VmRSS:\t2048 kB\n");

        try {
            $probe = new ProcResourceUsageProbe($path);
            self::assertFalse((new ProcessCountHealthCheck($probe, 0))->check()->getResult());
            self::assertFalse((new MemoryUsageHealthCheck($probe, 1))->check()->getResult());
            self::assertFalse((new HttpClientHealthCheck(new MockHttpClient(new MockResponse('', ['http_code' => 500])), 'https://example.test'))->check()->getResult());
        } finally {
            $filesystem->remove($path);
        }
    }
}
