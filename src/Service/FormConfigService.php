<?php

namespace Barth\SimpleConfigBundle\Service;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class FormConfigService
{
    /**
     * @var FormFactoryInterface
     */
    private $factory;

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

    protected function addToForm(FormBuilder $formBuilder, string $key, $field)
    {
        switch (true) {
            case \is_array($field):
                foreach ($field as $subKey => $value) {
                    if (!\is_int($subKey)) {
                        $this->addToForm($formBuilder, $key . ':' . $subKey, $value);
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

        $key = \str_replace('.', '-', $key);
        $formBuilder->add($key, $type, [
            'data' => $field,
            'required' => false,
        ]);
    }
}
