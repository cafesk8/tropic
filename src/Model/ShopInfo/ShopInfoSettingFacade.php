<?php

declare(strict_types=1);

namespace App\Model\ShopInfo;

use Shopsys\FrameworkBundle\Model\ShopInfo\ShopInfoSettingFacade as BaseShopInfoSettingFacade;

class ShopInfoSettingFacade extends BaseShopInfoSettingFacade
{
    public const SHOP_INFO_OPENING_HOURS = 'shopInfoOpeningHours';

    /**
     * @param string|null $value
     * @param int $domainId
     */
    public function setOpeningHours($value, $domainId)
    {
        $this->setting->setForDomain(self::SHOP_INFO_OPENING_HOURS, $value, $domainId);
    }

    /**
     * @param int $domainId
     * @return string|null
     */
    public function getOpeningHours($domainId)
    {
        return $this->setting->getForDomain(self::SHOP_INFO_OPENING_HOURS, $domainId);
    }
}