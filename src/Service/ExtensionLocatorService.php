<?php

namespace Barth\SimpleConfigBundle\Service;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class ExtensionLocatorService
{
    /**
     * @var KernelInterface
     */
    private $kernel;
    /**
     * @var array
     */
    private $blacklistedBundles;

    public function __construct(
        KernelInterface $kernel,
        array $blacklistedBundles = []
    ) {
        $this->kernel = $kernel;
        $this->blacklistedBundles = $blacklistedBundles;
    }

    public function retrieveAllAvailable(): array
    {
        $bundles = $this->kernel->getBundles();
        $extensions = [];
        foreach ($bundles as $bundle) {
            $extension = $bundle->getContainerExtension();
            if ($extension && !$this->isBlackListed($extension->getAlias())) {
                $extensions[] = $extension->getAlias();
            }
        }

        return $extensions;
    }

    public function retrieveByPackageName(string $package): ExtensionInterface
    {
        $bundles = $this->kernel->getBundles();

        /** @var BundleInterface $bundle */
        foreach ($bundles as $bundle) {
            if ($package === $bundle->getName()) {
                if (!$bundle->getContainerExtension()) {
                    throw new \LogicException(\sprintf(
                        'Bundle "%s" does not have a container extension.',
                        $package
                    ));
                }
                if (!$this->isBlackListed($bundle->getContainerExtension()->getAlias())) {
                    return $bundle->getContainerExtension();
                }
            }

            $extension = $bundle->getContainerExtension();
            if ($extension) {
                if ($package === $extension->getAlias()
                    && !$this->isBlackListed($extension->getAlias())
                ) {
                    return $extension;
                }
            }
        }

        throw new \LogicException('Unable to found "' . $package . '" bundle. It\'s may blacklisted ?');
    }

    /**
     * @param string $extensionAlias
     *
     * @return bool
     */
    private function isBlackListed(string $extensionAlias): bool
    {
        return \in_array($extensionAlias, $this->blacklistedBundles);
    }
}
