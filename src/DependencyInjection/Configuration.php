<?php

declare(strict_types=1);

namespace SwooleBundle\Observability\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('swoole_bundle_observability');
        $treeBuilder->getRootNode()
            ->children()
                ->booleanNode('health_checks')->defaultTrue()->end()
                ->integerNode('max_processes')->min(1)->defaultValue(20)->end()
                ->integerNode('max_rss_mib')->min(0)->defaultValue(1536)->end()
                ->scalarNode('http_url')->defaultValue('https://connectivitycheck.gstatic.com/generate_204')->cannotBeEmpty()->end()
            ->end();

        return $treeBuilder;
    }
}
