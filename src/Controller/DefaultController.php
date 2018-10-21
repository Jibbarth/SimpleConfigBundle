<?php

namespace Barth\SimpleConfigBundle\Controller;

use Barth\SimpleConfigBundle\NameConverter\SnakeCaseToCamelCaseNameConverter;
use Barth\SimpleConfigBundle\Service\ConfigService;
use Barth\SimpleConfigBundle\Service\ExtensionConfigurationService;
use Barth\SimpleConfigBundle\Service\ExtensionLocatorService;
use Barth\SimpleConfigBundle\Service\FormConfigService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class DefaultController.
 */
class DefaultController extends Controller
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

    public function __construct(
        ConfigService $configService,
        ExtensionLocatorService $extensionLocatorService,
        ExtensionConfigurationService $extensionConfigurationService
    ) {
        $this->configService = $configService;
        $this->extensionLocatorService = $extensionLocatorService;
        $this->extensionConfigurationService = $extensionConfigurationService;
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
        $config = $this->extensionConfigurationService->getConfiguration($extension);

        $form = $formConfigService->getFormForConfig($config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $this->cleanData($form->getData());
            // TODO validate configuration

            $this->configService->saveNewConfig($package, $data);
            if ($this->configService->isOverrideConfigForPackageExist($package)) {
                $this->get('session')->getFlashBag()->add(
                    'success',
                    'Successfully registered config for ' . $package
                );
            }

            return $this->redirect($this->generateUrl('barth_simpleconfig_index'));
        }

        $nameConverter = new SnakeCaseToCamelCaseNameConverter();
        return $this->render('@BarthSimpleConfig/form.html.twig', [
            'config_form' => $form->createView(),
            'extension' => $nameConverter->handle($extension->getAlias()),
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
}
