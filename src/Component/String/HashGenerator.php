<?php

declare(strict_types=1);

namespace App\Component\String;

use Shopsys\FrameworkBundle\Component\String\HashGenerator as BaseHashGenerator;

class HashGenerator extends BaseHashGenerator
{
    /**
     * Excluding characters O, 0, I, L, 1
     */
    public $charactersWithoutConfusingCharacters = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789abcdefghjkmnpqrstuvwxyz';

    /**
     * @param int $length
     * @return string
     */
    public function generateHashWithoutConfusingCharacters(int $length): string
    {
        $numberOfChars = strlen($this->charactersWithoutConfusingCharacters);

        $hash = '';
        for ($i = 1; $i <= $length; $i++) {
            $randomIndex = random_int(0, $numberOfChars - 1);
            $hash .= ($i > 1 && $i % 4 === 1 ? '-' : '') . $this->charactersWithoutConfusingCharacters[$randomIndex];
        }

        return $hash;
    }
}
