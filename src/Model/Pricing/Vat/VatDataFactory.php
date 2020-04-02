<?php

declare(strict_types=1);

namespace App\Model\Pricing\Vat;

use Shopsys\FrameworkBundle\Model\Pricing\Vat\Vat as BaseVat;
use Shopsys\FrameworkBundle\Model\Pricing\Vat\VatData as BaseVatData;
use Shopsys\FrameworkBundle\Model\Pricing\Vat\VatDataFactory as BaseVatDataFactory;

/**
 * @method \App\Model\Pricing\Vat\VatData createFromVat(\App\Model\Pricing\Vat\Vat $vat)
 */
class VatDataFactory extends BaseVatDataFactory
{
    /**
     * @return \App\Model\Pricing\Vat\VatData
     */
    public function create(): BaseVatData
    {
        return new VatData();
    }

    /**
     * @param \App\Model\Pricing\Vat\VatData $vatData
     * @param \App\Model\Pricing\Vat\Vat $vat
     */
    protected function fillFromVat(BaseVatData $vatData, BaseVat $vat)
    {
        parent::fillFromVat($vatData, $vat);
        $vatData->pohodaId = $vat->getPohodaId();
    }
}
