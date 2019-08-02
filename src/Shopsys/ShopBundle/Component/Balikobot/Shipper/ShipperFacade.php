<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Balikobot\Shipper;

class ShipperFacade
{
    public const
        SHIPPER_CESKA_POSTA = 'cp',
        SHIPPER_DPD = 'dpd',
        SHIPPER_GEIS = 'geis',
        SHIPPER_GLS = 'gls',
        SHIPPER_INTIME = 'intime',
        SHIPPER_POSTA_BEZ_HRANIC = 'pbh',
        SHIPPER_PPL = 'ppl',
        SHIPPER_TOPTRANS = 'toptrans',
        SHIPPER_ULOZENKA = 'ulozenka',
        SHIPPER_ZASILKOVNA = 'zasilkovna';

    /**
     * @return string[]
     */
    public function getShipperNamesIndexedById(): array
    {
        return [
            self::SHIPPER_CESKA_POSTA => t('Česká pošta'),
            self::SHIPPER_DPD => t('DPD'),
            self::SHIPPER_GEIS => t('GEIS'),
            self::SHIPPER_GLS => t('GLS'),
            self::SHIPPER_INTIME => t('IN TIME'),
            self::SHIPPER_POSTA_BEZ_HRANIC => t('Pošta bez hranic'),
            self::SHIPPER_PPL => t('PPL'),
            self::SHIPPER_TOPTRANS => t('TOPTRANS'),
            self::SHIPPER_ULOZENKA => t('Uloženka'),
            self::SHIPPER_ZASILKOVNA => t('Zásilkovna'),
        ];
    }

    /**
     * @param string $shipper
     * @return bool
     */
    public function isShipperAllowed(string $shipper): bool
    {
        if (array_key_exists($shipper, $this->getShipperNamesIndexedById()) === false) {
            return false;
        }

        return true;
    }
}
