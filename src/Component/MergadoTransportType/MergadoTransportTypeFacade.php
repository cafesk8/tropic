<?php

declare(strict_types=1);

namespace App\Component\MergadoTransportType;

class MergadoTransportTypeFacade
{
    public const
        CZECH_POST = 'CESKA_POSTA',
        CZECH_POST_AT_POST = 'CESKA_POSTA_NA_POSTU',
        CZECH_POST_REGISTERED_MAIL = 'CESKA_POSTA_DOPORUCENA_ZASILKA',
        DPD = 'DPD',
        DHL = 'DHL',
        GEIS = 'GEIS',
        GLS = 'GLS',
        INTIME = 'INTIME',
        PPL = 'PPL',
        TOPTRANS = 'TOPTRANS',
        OWN_TRANSPORT = 'VLASTNI_PREPRAVA';

    /**
     * @return string[]
     */
    public function getShipperNamesIndexedById(): array
    {
        return [
            self::CZECH_POST => t('Česká pošta'),
            self::CZECH_POST_AT_POST => t('Česká pošta - na poštu'),
            self::CZECH_POST_REGISTERED_MAIL => t('Česká pošta - doporučená zásilka'),
            self::DPD => t('DPD'),
            self::DHL => t('DHL'),
            self::GEIS => t('GEIS'),
            self::GLS => t('GLS'),
            self::INTIME => t('IN TIME'),
            self::PPL => t('PPL'),
            self::TOPTRANS => t('TOPTRANS'),
            self::OWN_TRANSPORT => t('Vlastí přeprava'),
        ];
    }

    /**
     * @return string[]
     */
    public function getShipperNamesIndexedByName(): array
    {
        return array_flip($this->getShipperNamesIndexedById());
    }

    /**
     * @param string $mergadoTransportType
     * @return bool
     */
    public function isMergadoTransportTypeAllowed(string $mergadoTransportType): bool
    {
        return !(array_key_exists($mergadoTransportType, $this->getShipperNamesIndexedById()) === false);
    }
}
