<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\String;

class StringHelper
{
    /**
     * @param string $string
     * @return string
     */
    public static function removeNewline(string $string): string
    {
        return trim(str_replace(PHP_EOL, ' ', $string));
    }
}
