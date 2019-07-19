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
            'firstSquare' => t('1 - čtverec'),
            'secondSquare' => t('2 - čtverec'),
            'thirdSquare' => t('3 - čtverec'),
            'fourthSquare' => t('4 - čtverec'),
            'fifthRectangle' => t('5 - obdélník na výšku'),
        ];
    }
}
