<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer\Transfer;

class ChangedCustomerImportCronModule extends AbstractCustomerImportCronModule
{
    public const TRANSFER_IDENTIFIER = 'import_customers_changed';

    /**
     * @return string
     */
    protected function getApiUrl(): string
    {
        return '/api/Eshop/ChangedCustomers';
    }
}
