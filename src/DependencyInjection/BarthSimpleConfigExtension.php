<?php

namespace Barth\SimpleConfigBundle\DependencyInjection;

use Barth\SimpleConfigBundle\Service\ConfigService;
use Barth\SimpleConfigBundle\Service\ExtensionLocatorService;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class BarthSimpleConfigExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $container->getDefinition(ConfigService::class)->setArgument(1, $config['override_package_directory']);
        if (true === $config['enable_blacklist']) {
            $container
                ->getDefinition(ExtensionLocatorService::class)
                ->setArgument(1, $config['blacklisted_bundles']);
        }
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
