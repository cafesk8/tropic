<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer;

class EanFacade
{
    public const EAN_RANGE_START = 100000000000;
    public const EAN_RANGE_END = 999999999999;

    /**
     * @return string
     */
    public function getNewGeneratedEan(): string
    {
        $code = (string)mt_rand(self::EAN_RANGE_START, self::EAN_RANGE_END);
        $code .= $this->getCheckDigit($code);

        return $code;
    }

    /**
     * From: https://glot.io/snippets/eta2anhsg5
     *
     * @param string $code
     * @return int|null
     */
    private function getCheckDigit(string $code): ?int
    {
        $codePartials = str_split($code);
        $checkDigit = null;
        $evenNumbers = 0;
        $oddNumbers = 0;

        foreach ($codePartials as $key => $value) {
            if (($key + 1) % 2 === 0) { // Keys start from 0, We want the start to be 1d
                $evenNumbers += $value;
            } else {
                $oddNumbers += $value;
            }
        }
        $evenNumbers *= 3;
        $total = $evenNumbers + $oddNumbers;

        if ($total % 10 === 0) {
            $checkDigit = 0;
        } else {
            $nextMultiple = $total + (10 - $total % 10);
            $checkDigit = $nextMultiple - $total;
        }

        return $checkDigit;
    }
}
