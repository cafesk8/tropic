<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product;

use Shopsys\ShopBundle\Model\Product\Parameter\ParameterValue;

class SizeHelper
{
    private const SIZES_TO_NUMBER = [
        's' => 5,
        'm' => 20,
        'l' => 50,
    ];

    /**
     * @param string $size
     * @return int|string
     */
    private static function parseSize(string $size)
    {
        $size = mb_strtolower($size);
        $finalCount = 0;
        $xCount = substr_count($size, 'x');
        $subtract = strpos($size, 's') !== false;

        foreach (str_split($size) as $char) {
            if (array_key_exists($char, self::SIZES_TO_NUMBER) === true) {
                $finalCount += self::SIZES_TO_NUMBER[$char];
            }
        }

        if ($finalCount === 0 && $xCount === 0) {
            return $size;
        }

        if ($subtract === true) {
            return $finalCount - $xCount;
        } else {
            return $finalCount + $xCount;
        }
    }

    /**
     * @param string $size1
     * @param string $size2
     * @return int
     */
    public static function compareSizes(string $size1, string $size2): int
    {
        return self::parseSize($size1) <=> self::parseSize($size2);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\ParameterValue $size1
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\ParameterValue $size2
     * @return int
     */
    public static function compareSizesInObject(ParameterValue $size1, ParameterValue $size2): int
    {
        return self::parseSize($size1->getText()) <=> self::parseSize($size2->getText());
    }
}
