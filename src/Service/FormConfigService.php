<?php

namespace Barth\SimpleConfigBundle\Service;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class FormConfigService
{
    /**
     * @var FormFactoryInterface
     */
    protected $factory;

    public function __construct(
        FormFactoryInterface $factory
    ) {
        $this->factory = $factory;
    }

    public function getFormForConfig(array $config): FormInterface
    {
        $formBuilder = $this->factory->createBuilder(FormType::class);

        foreach ($config as $key => $field) {
            $this->addToForm($formBuilder, $key, $field);
        }
        $formBuilder->add('save', SubmitType::class, ['label' => 'Save Config']);

        return $formBuilder->getForm();
    }

    protected function addToForm(FormBuilderInterface $formBuilder, string $key, $field, $parentKey = ''): void
    {
        $params = [
            'label' => $this->humanize($key) . (($parentKey) ? sprintf(' (%s)', $this->humanize($parentKey)) : null),
            'data' => $field,
            'required' => false,
            'translation_domain' => 'barth_simple_config',
        ];
        switch (true) {
            case \is_array($field):
                foreach ($field as $subKey => $value) {
                    if (!\is_int($subKey)) {

                        $this->addToForm($formBuilder, $subKey, $value, ($parentKey) ? $parentKey . ':' . $key : $key);
                    }
                }
                return;
                break;

            case \is_bool($field):
                $type = CheckboxType::class;
                break;
            case \is_int($field):
            case '0' === $field:
            case 0 === $field:
                $type = IntegerType::class;
                break;
            case \is_string($field):
                $type = TextType::class;
                break;
            default:
                return;
        }
        if ('' !== $parentKey) {
            $key = $parentKey . ':' . $key;
        }
        $key = \str_replace('.', '-', $key);
        $formBuilder->add($key, $type, $params);
    }

    /**
     * copied from Symfony\Component\Form\FormRenderer::humanize()
     */
    protected function humanize(string $text): string
    {
        return ucfirst(strtolower(trim(preg_replace(array('/([A-Z])/', '/[_\s]+/'), array('_$1', ' '), $text))));
    }
}
