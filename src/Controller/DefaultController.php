<?php

namespace Barth\SimpleConfigBundle\Controller;

use Barth\SimpleConfigBundle\NameConverter\SnakeCaseToCamelCaseNameConverter;
use Barth\SimpleConfigBundle\Service\ConfigService;
use Barth\SimpleConfigBundle\Service\ExtensionConfigurationService;
use Barth\SimpleConfigBundle\Service\ExtensionLocatorService;
use Barth\SimpleConfigBundle\Service\FormConfigService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class DefaultController.
 */
class DefaultController extends AbstractController
{
    /**
     * @var ConfigService
     */
    private $configService;
    /**
     * @var ExtensionLocatorService
     */
    private $extensionLocatorService;
    /**
     * @var ExtensionConfigurationService
     */
    private $extensionConfigurationService;
    /**
     * @var string
     */
    private $defaultAdminBundle;

    public function __construct(
        ConfigService $configService,
        ExtensionLocatorService $extensionLocatorService,
        ExtensionConfigurationService $extensionConfigurationService,
        string $defaultAdminBundle = null
    ) {
        $this->configService = $configService;
        $this->extensionLocatorService = $extensionLocatorService;
        $this->extensionConfigurationService = $extensionConfigurationService;
        $this->defaultAdminBundle = $defaultAdminBundle;
    }

    /**
     * @Route(
     *     name="barth_simpleconfig_index",
     *     path="/config",
     * )
     */
    public function indexAction()
    {
        $availableBundles = $this->extensionLocatorService->retrieveAllAvailable();

        return $this->render('@BarthSimpleConfig/list.html.twig', [
            'bundles' => $availableBundles,
            'parent_template' => $this->getParentTemplate(),
        ]);
    }

    /**
     * @Route(
     *     name="barth_simpleconfig_edit",
     *     path="/config/{package}",
     * )
     */
    public function editAction(
        Request $request,
        string $package,
        FormConfigService $formConfigService
    ): Response {
        $extension = $this->extensionLocatorService->retrieveByPackageName($package);
        $config = $this->extensionConfigurationService->getCurrentConfiguration($extension);

        $form = $formConfigService->getFormForConfig($config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $nameConverter = new SnakeCaseToCamelCaseNameConverter();
            $data = $this->cleanData($form->getData());
            try {
                $data = $this->configService->parseConfig($data);
                $data = $this->extensionConfigurationService->validateConfiguration($extension, [$extension->getAlias() => $data]);
                $this->configService->saveNewConfig($package, $data);
                if ($this->configService->isOverrideConfigForPackageExist($package)) {
                    $nameConverter = new SnakeCaseToCamelCaseNameConverter();
                    $this->get('session')->getFlashBag()->add(
                        'success',
                        'Successfully registered config for ' . $nameConverter->handle($package)
                    );
                }

                return $this->redirect($this->generateUrl('barth_simpleconfig_index'));
            } catch (\Throwable $throwable) {
                $this->get('session')->getFlashBag()->add(
                    'error',
                    sprintf('Error for %s : %s',
                        $nameConverter->handle($package),
                        $throwable->getMessage()
                    )
                );
            }
        }

        return $this->render($this->getTemplate(), [
            'config_form' => $form->createView(),
            'extension' => $extension->getAlias(),
            'parent_template' => $this->getParentTemplate(),
        ]);
    }

    protected function cleanData(array $data): array
    {
        foreach ($data as $key => $value) {
            if (null === $value) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    protected function getParentTemplate()
    {
        switch ($this->defaultAdminBundle) {
            case 'easy_admin':
                return '@EasyAdmin/default/layout.html.twig';
            case 'sonata_admin':
                return '@BarthSimpleConfig/sonata_base.html.twig';
            default:
                return '@BarthSimpleConfig/base.html.twig';
        }
    }

    protected function getTemplate()
    {
        switch ($this->defaultAdminBundle) {
            case 'easy_admin':
                return '@BarthSimpleConfig/easy_admin_form.html.twig';
            default:
                return '@BarthSimpleConfig/base_form.html.twig';
        }
    }
}
