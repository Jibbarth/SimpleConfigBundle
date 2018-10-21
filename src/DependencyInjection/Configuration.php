<?php

namespace Barth\SimpleConfigBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('barth_simpleconfig');
        $rootNode
            ->children()
                ->scalarNode('override_package_directory')->defaultValue('override')->end()
                ->arrayNode('blacklisted_bundles_old')
                    ->scalarPrototype()->end()
                    ->beforeNormalization()->castToArray()->end()
                ->end()
                ->booleanNode('enable_blacklist')
                    ->defaultValue(true)
                ->end()
                ->booleanNode('enable_easyadmin_integration')
                    ->defaultValue(true)
                ->end()
                ->variableNode('blacklisted_bundles')
                    ->defaultValue($this->getDefaultBlacklistBundle())
                    ->cannotBeOverwritten()
                    ->validate()
                        ->ifTrue(function ($v) { return false === \is_array($v); })
                        ->thenInvalid('The blacklisted_bundles parameter must be an array.')
                        ->ifArray()
                        ->then(function ($v) { return \array_merge($v, $this->getDefaultBlacklistBundle()); })
                    ->end()
                    ->info('To blacklist some modules to be configured throught UI.')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    /**
     * @return array
     */
    private function getDefaultBlacklistBundle()
    {
        return [
            'debug',
            'doctrine',
            'doctrine_cache',
            'doctrine_migrations',
            'framework',
            'maker',
            'monolog',
            'security',
            'sensio_framework_extra',
            'swiftmailer',
            'twig',
            'web_profiler',
            'web_server',
        ];
    }
}
