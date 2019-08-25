<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Twig;

use Nette\Utils\Strings;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class UrlExtension extends AbstractExtension
{
    /**
     * @return array
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('webalize', [$this, 'webalize']),
        ];
    }

    /**
     * @param string $string
     * @return string
     */
    public function webalize(string $string): string
    {
        return Strings::webalize($string);
    }
}
