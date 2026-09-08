<?php

declare(strict_types=1);

namespace SwooleBundle\Observability\HealthCheck;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use SymfonyHealthCheckBundle\Check\CheckInterface;
use SymfonyHealthCheckBundle\Dto\Response;

final readonly class HttpClientHealthCheck implements CheckInterface
{
    public function __construct(private HttpClientInterface $httpClient, private string $url) {}

    #[\Override]
    public function check(): Response
    {
        try {
            $status = $this->httpClient->request('GET', $this->url, ['max_duration' => 5, 'timeout' => 3])->getStatusCode();
        } catch (\Throwable $e) {
            return new Response('http_client', false, 'External HTTP client request failed: ' . $e->getMessage(), ['url' => $this->url]);
        }

        $healthy = $status === 204;

        return new Response('http_client', $healthy, $healthy ? 'External HTTP client request is healthy' : sprintf('External HTTP client request returned unexpected status code %d', $status), ['url' => $this->url, 'status_code' => $status]);
    }
}
