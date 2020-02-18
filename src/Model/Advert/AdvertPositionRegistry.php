<?php

declare(strict_types=1);

namespace App\Model\Advert;

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
            'fourthRectangle' => t('4 - velký obdelník na šířku'),
        ];
    }
}
