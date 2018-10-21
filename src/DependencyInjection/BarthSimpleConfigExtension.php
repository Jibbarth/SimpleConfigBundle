<?php

namespace Barth\SimpleConfigBundle\DependencyInjection;

use Barth\SimpleConfigBundle\NameConverter\SnakeCaseToCamelCaseNameConverter;
use Barth\SimpleConfigBundle\Service\ConfigService;
use Barth\SimpleConfigBundle\Service\ExtensionLocatorService;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\ClosureLoader;
use Symfony\Component\DependencyInjection\Loader\DirectoryLoader;
use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class BarthSimpleConfigExtension extends Extension implements PrependExtensionInterface
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
            $this->blacklistedBundles = $config['blacklisted_bundles'];
            $container
                ->getDefinition(ExtensionLocatorService::class)
                ->setArgument(1, $config['blacklisted_bundles']);
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    public function prepend(ContainerBuilder $container)
    {
        $bundles = $container->getParameter('kernel.bundles');

        $this->loadOverrideConfig($container);
        $config = $this->getExtensionConfig($container);

        if (isset($bundles['EasyAdminBundle']) && $config['enable_easyadmin_integration'] === true) {
            $easyConfig = [
                'design' => [
                    'menu' => [[
                        'label' => 'Bundles Configuration',
                        'icon' => 'wrench',
                        'children' => $this->getEasyAdminChildren($container, $config)
                    ]]
                ],
            ];

            $container->prependExtensionConfig('easy_admin', $easyConfig);
        }
    }


    private function loadOverrideConfig(ContainerBuilder $container)
    {
        $config = $this->getExtensionConfig($container);
        $overrideLoader = $this->getContainerLoader($container);
        $confDir = $container->getParameter('kernel.project_dir') . '/config';
        $overrideLoader->load($confDir . '/packages/' . $config['override_package_directory'] . '/*.yaml', 'glob');
    }

    /**
     * @param ContainerBuilder $container
     * @param array $config
     * @return array
     */
    private function getEasyAdminChildren(ContainerBuilder $container, array $config)
    {
        $extensions = $container->getExtensions();
        $nameConverter =  new SnakeCaseToCamelCaseNameConverter();
        $childrenConfig = [];

        foreach ($extensions as $extension) {
            if (!in_array($extension->getAlias(), $config['blacklisted_bundles']) || $config['enable_blacklist'] === false) {
                $childrenConfig[] = [
                    'label' => $nameConverter->handle($extension->getAlias()),
                    'route' => 'barth_simpleconfig_edit',
                    'params' => ['package' => $extension->getAlias()]
                ];
            }
        }

        return $childrenConfig;
    }

    private function getExtensionConfig($container)
    {
        $configs = $container->getExtensionConfig($this->getAlias());
        return $this->processConfiguration(new Configuration(), $configs);
    }

    private function getContainerLoader(ContainerInterface $container)
    {
        $locator = new FileLocator([]);
        $resolver = new LoaderResolver(array(
            new YamlFileLoader($container, $locator),
            new GlobFileLoader($container, $locator),
            new DirectoryLoader($container, $locator),
            new ClosureLoader($container)
        ));

        return new DelegatingLoader($resolver);
    }
}
