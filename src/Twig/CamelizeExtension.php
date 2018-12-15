<?php

namespace Barth\SimpleConfigBundle\Twig;

use Barth\SimpleConfigBundle\NameConverter\SnakeCaseToCamelCaseNameConverter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CamelizeExtension extends AbstractExtension
{
    /**
     * @var SnakeCaseToCamelCaseNameConverter
     */
    private $nameConverter;

    public function __construct(
        SnakeCaseToCamelCaseNameConverter $nameConverter
    ) {
        $this->nameConverter = $nameConverter;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('snakeToCamel', [$this, 'snakeToCamel']),
        ];
    }

    public function snakeToCamel(string $value): string
    {
        return $this->nameConverter->handle($value);
    }
}
