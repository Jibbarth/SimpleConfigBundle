<?php

namespace Barth\SimpleConfigBundle\DependencyInjection;

use Barth\SimpleConfigBundle\NameConverter\SnakeCaseToCamelCaseNameConverter;
use Barth\SimpleConfigBundle\Service\ConfigService;
use Barth\SimpleConfigBundle\Service\ExtensionLocatorService;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
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

        if (isset($bundles['EasyAdminBundle'])) {
            $config = [
                'design' => [
                    'menu' => [[
                        'label' => 'Bundles Configuration',
                        'icon' => 'wrench',
                        'children' => $this->getEasyAdminChildren($container)
                    ]]
                ],
            ];

            $container->prependExtensionConfig('easy_admin', $config);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @return array
     */
    private function getEasyAdminChildren(ContainerBuilder $container)
    {
        $extensions = $container->getExtensions();
        $configs = $container->getExtensionConfig($this->getAlias());
        $config = $this->processConfiguration(new Configuration(), $configs);

        $childrenConfig = [];
        $nameConverter =  new SnakeCaseToCamelCaseNameConverter();

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
}
