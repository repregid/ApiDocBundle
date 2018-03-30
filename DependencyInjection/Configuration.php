<?php

namespace Repregid\ApiDocBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 * @package Repregid\ApiDocBundle\DependencyInjection
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('repregid_api_doc');

        $rootNode
            ->children()
                ->scalarNode('routePrefix')->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
