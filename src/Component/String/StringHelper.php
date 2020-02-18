<?php

declare(strict_types=1);

namespace App\Component\String;

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

    /**
     * @param string $inString
     * @return string
     */
    public static function replaceDotByComma(string $inString): string
    {
        return str_replace('.', ',', $inString);
    }

    /**
     * @param string $string
     * @return string
     */
    public static function removeWhitespaces(string $string): string
    {
        return preg_replace('/\s+/', '', $string);
    }
}
