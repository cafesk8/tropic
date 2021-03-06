<?php

declare(strict_types=1);

namespace App\Model\Advert;

use Shopsys\FrameworkBundle\Model\Advert\AdvertPositionRegistry as BaseAdvertPositionRegistry;

class AdvertPositionRegistry extends BaseAdvertPositionRegistry
{
    public const CATEGORY_ADVERT_POSITION = 'category';

    /**
     * @return string[]
     */
    public function getAllLabelsIndexedByNames(): array
    {
        return [
            'firstSquare' => t('1 - čtverec'),
            'secondSquare' => t('2 - čtverec'),
            'thirdSquare' => t('3 - čtverec'),
            'fourthRectangle' => t('4 - velký obdélník na šířku'),
            self::CATEGORY_ADVERT_POSITION => t('Pod nadpisem kategorie'),
        ];
    }

    /**
     * @return array
     */
    public function getImageSizeRecommendationsIndexedByNames(): array
    {
        return [
            'firstSquare' => t('šířka: 429px, výška: 322px'),
            'secondSquare' => t('šířka: 429px, výška: 322px'),
            'thirdSquare' => t('šířka: 429px, výška: 322px'),
            'fourthRectangle' => t('šířka: 1320px, výška: 322px'),
            self::CATEGORY_ADVERT_POSITION => t('šířka: 1180px, výška: 150px'),
        ];
    }
}
