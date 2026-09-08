<?php

declare(strict_types=1);

namespace SwooleBundle\Observability;

use SwooleBundle\Observability\DependencyInjection\SwooleBundleObservabilityExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SwooleBundleObservabilityBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        if ($this->extension === null) {
            $this->extension = new SwooleBundleObservabilityExtension();
        }

        return $this->extension === false ? null : $this->extension;
    }
}
