<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Administrator;

class Role
{
    public const VIEW_ORDERS = 'ROLE_VIEW_ORDERS';
    public const VIEW_CUSTOMERS = 'ROLE_VIEW_CUSTOMERS';
    public const VIEW_PRODUCTS = 'ROLE_VIEW_PRODUCTS';
    public const VIEW_PRICING = 'ROLE_VIEW_PRICING';
    public const VIEW_MARKETING = 'ROLE_VIEW_MARKETING';
    public const VIEW_ADMINISTRATORS = 'ROLE_VIEW_ADMINISTRATORS';
    public const VIEW_SETTINGS = 'ROLE_VIEW_SETTINGS';

    /**
     * @return string[]
     */
    public static function getAllRolesIndexedByTitles(): array
    {
        return [
            t('Vidět objednávky') => self::VIEW_ORDERS,
            t('Vidět zákazníky') => self::VIEW_CUSTOMERS,
            t('Vidět zboží a kategorie') => self::VIEW_PRODUCTS,
            t('Vidět cenotvorbu') => self::VIEW_PRICING,
            t('Vidět marketing') => self::VIEW_MARKETING,
            t('Vidět administrátory') => self::VIEW_ADMINISTRATORS,
            t('Vidět nastavení') => self::VIEW_SETTINGS,
        ];
    }
}
