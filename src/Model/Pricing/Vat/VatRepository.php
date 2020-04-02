<?php

declare(strict_types=1);

namespace App\Model\Pricing\Vat;

use Shopsys\FrameworkBundle\Model\Pricing\Vat\VatRepository as BaseVatRepository;

/**
 * @method \App\Model\Pricing\Vat\Vat[] getAllForDomainIncludingMarkedForDeletion(int $domainId)
 * @method \App\Model\Pricing\Vat\Vat|null findById(int $vatId)
 * @method \App\Model\Pricing\Vat\Vat getById(int $vatId)
 * @method \App\Model\Pricing\Vat\Vat[] getAllForDomainExceptId(int $domainId, int $vatId)
 * @method bool existsVatToBeReplacedWith(\App\Model\Pricing\Vat\Vat $vat)
 * @method \App\Model\Pricing\Vat\Vat[] getVatsWithoutProductsMarkedForDeletion()
 * @method isVatUsed(\App\Model\Pricing\Vat\Vat $vat)
 * @method bool existsPaymentWithVat(\App\Model\Pricing\Vat\Vat $vat)
 * @method bool existsTransportWithVat(\App\Model\Pricing\Vat\Vat $vat)
 * @method bool existsProductWithVat(\App\Model\Pricing\Vat\Vat $vat)
 * @method replaceVat(\App\Model\Pricing\Vat\Vat $oldVat, \App\Model\Pricing\Vat\Vat $newVat)
 * @method replacePaymentsVat(\App\Model\Pricing\Vat\Vat $oldVat, \App\Model\Pricing\Vat\Vat $newVat)
 * @method replaceTransportsVat(\App\Model\Pricing\Vat\Vat $oldVat, \App\Model\Pricing\Vat\Vat $newVat)
 * @method \App\Model\Pricing\Vat\Vat[] getAllForDomain(int $domainId)
 */
class VatRepository extends BaseVatRepository
{
    /**
     * @param int $pohodaId
     * @return \App\Model\Pricing\Vat\Vat|null
     */
    public function findByPohodaId(int $pohodaId): ?Vat
    {
        return $this->getVatRepository()->findOneBy(['pohodaId' => $pohodaId]);
    }
}
