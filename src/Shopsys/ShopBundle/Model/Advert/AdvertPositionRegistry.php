<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Advert;

use Shopsys\FrameworkBundle\Model\Advert\AdvertPositionRegistry as BaseAdvertPositionRegistry;

class AdvertPositionRegistry extends BaseAdvertPositionRegistry
{
    /**
     * @return string[]
     */
    public function getAllLabelsIndexedByNames(): array
    {
        return [
            '1_square' => t('1 - čtverec'),
            '2_square' => t('2 - čtverec'),
            '3_square' => t('3 - čtverec'),
            '4_square' => t('4 - čtverec'),
            '5_rectangle' => t('5 - obdélník na výšku'),
        ];
    }
}
