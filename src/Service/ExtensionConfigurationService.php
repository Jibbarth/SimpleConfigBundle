<?php

namespace Barth\SimpleConfigBundle\Service;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class ExtensionConfigurationService
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var ContainerBuilder
     */
    private $containerBuilder;

    public function __construct(
        KernelInterface $kernel
    ) {
        $this->kernel = $kernel;
    }

    /**
     * @throws \Exception
     */
    public function validateConfiguration(ExtensionInterface $extension, array $configs): array
    {
        $configs[$extension->getAlias()] = $this->cleanConfig($configs[$extension->getAlias()], $this->getCurrentConfiguration($extension));
        $processor = new Processor();
        $container = $this->getContainerBuilder();
        $container->resolveEnvPlaceholders(
            $container->getParameterBag()->resolveValue(
                $processor->processConfiguration($this->getExtensionConfiguration($extension), $configs)
            )
        );


        return $configs[$extension->getAlias()];
    }

    public function getCurrentConfiguration(ExtensionInterface $extension): array
    {
        $config = [];

        try {
            $container = $this->getContainerBuilder();
            $configs = $container->getExtensionConfig($extension->getAlias());

            $configs = $container->resolveEnvPlaceholders(
                $container->getParameterBag()->resolveValue($configs)
            );

            $processor = new Processor();
            $config = $container->resolveEnvPlaceholders(
                $container->getParameterBag()->resolveValue(
                    $processor->processConfiguration($this->getExtensionConfiguration($extension), $configs)
                )
            );
        } catch (\Throwable $throwable) {
            // Too bad, unprocessable configuration
        }

        return $config;
    }

    public function getTreeBuilderForExtension(ExtensionInterface $extension): TreeBuilder
    {
        $container = $this->getContainerBuilder();
        $configs = $container->getExtensionConfig($extension->getAlias());

        $configuration = $extension->getConfiguration($configs, $container);

        return $configuration->getConfigTreeBuilder();
    }

    protected function getExtensionConfiguration(ExtensionInterface $extension): ConfigurationInterface
    {
        $container = $this->getContainerBuilder();
        $configs = $container->getExtensionConfig($extension->getAlias());

        return $extension->getConfiguration($configs, $container);
    }

    protected function getContainerBuilder(): ContainerBuilder
    {
        if (null === $this->containerBuilder) {
            $newKernel = clone $this->kernel;
            $newKernel->boot();

            $method = new \ReflectionMethod($newKernel, 'buildContainer');
            $method->setAccessible(true);
            $container = $method->invoke($newKernel);
            $container->getCompiler()->compile($container);

            $this->containerBuilder = $container;
        }

        return $this->containerBuilder;
    }

    /**
     * Remove key that is equals to current configuration
     */
    protected function cleanConfig(array $configs, array $currentConfig): array
    {
        foreach ($configs as $key => $config) {
            if (isset($currentConfig[$key]) && $currentConfig[$key] === $config ) {
                unset($configs[$key]);
            }
        }

        return $configs;
    }
}
