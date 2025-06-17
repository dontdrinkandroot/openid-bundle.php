<?php

namespace Dontdrinkandroot\OpenIdBundle\DependencyInjection;

use Override;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    #[Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('ddr_openid');
        $rootNode = $treeBuilder->getRootNode();

        // @formatter:off
        $rootNode->children()
        ->end();
        // @formatter:on

        return $treeBuilder;
    }
}
