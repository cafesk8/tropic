<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product;

class ColorHelper
{
    /**
     * Convert a hexadecimal color in RGB
     * @param string $hex
     * @return int|float
     */
    public static function hexToHue(string $hex)
    {
        list($red, $green, $blue) = sscanf($hex, '#%02x%02x%02x');
        return self::calculateHue([
            'red' => $red,
            'green' => $green,
            'blue' => $blue,
        ]);
    }

    /**
     * @param array $color
     * @return float|int
     */
    private static function calculateHue(array $color)
    {
        $red = $color['red'] / 255;
        $green = $color['green'] / 255;
        $blue = $color['blue'] / 255;

        $min = min($red, $green, $blue);
        $max = max($red, $green, $blue);

        switch ($max) {
            case 0:
                // If the max value is 0.
                $hue = 0;
                break;
            case $min:
                // If the maximum and minimum values are the same.
                $hue = 0;
                break;
            default:
                $delta = $max - $min;
                if ($red == $max) {
                    $hue = 0 + ($green - $blue) / $delta;
                } elseif ($green == $max) {
                    $hue = 2 + ($blue - $red) / $delta;
                } else {
                    $hue = 4 + ($red - $green) / $delta;
                }
                $hue *= 60;
                if ($hue < 0) {
                    $hue += 360;
                }
        }

        return $hue;
    }
}
