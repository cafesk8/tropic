<?php

declare(strict_types=1);

namespace App\Model\Pricing\Vat;

use Shopsys\FrameworkBundle\Model\Pricing\Vat\VatFacade as BaseVatFacade;

/**
 * @property \App\Model\Pricing\Vat\VatRepository $vatRepository
 * @property \App\Component\Setting\Setting $setting
 * @method __construct(\Doctrine\ORM\EntityManagerInterface $em, \App\Model\Pricing\Vat\VatRepository $vatRepository, \App\Component\Setting\Setting $setting, \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceRecalculationScheduler $productPriceRecalculationScheduler, \Shopsys\FrameworkBundle\Model\Pricing\Vat\VatFactoryInterface $vatFactory, \Shopsys\FrameworkBundle\Component\Domain\Domain $domain)
 * @method \App\Model\Pricing\Vat\Vat getById(int $vatId)
 * @method \App\Model\Pricing\Vat\Vat[] getAllForDomain(int $domainId)
 * @method \App\Model\Pricing\Vat\Vat[] getAllForDomainExceptId(int $domainId, int $vatId)
 * @method \App\Model\Pricing\Vat\Vat create(\App\Model\Pricing\Vat\VatData $vatData, int $domainId)
 * @method \App\Model\Pricing\Vat\Vat edit(int $vatId, \App\Model\Pricing\Vat\VatData $vatData)
 * @method \App\Model\Pricing\Vat\Vat getDefaultVatForDomain(int $domainId)
 * @method setDefaultVatForDomain(\App\Model\Pricing\Vat\Vat $vat, int $domainId)
 * @method bool isVatUsed(\App\Model\Pricing\Vat\Vat $vat)
 * @method \App\Model\Pricing\Vat\Vat[] getAllForDomainIncludingMarkedForDeletion(int $domainId)
 */
class VatFacade extends BaseVatFacade
{
    /**
     * @param int $pohodaId
     * @return \App\Model\Pricing\Vat\Vat|null
     */
    public function getByPohodaId(int $pohodaId): ?Vat
    {
        return $this->vatRepository->findByPohodaId($pohodaId);
    }

    /**
     * @param int $domainId
     * @return string[]
     */
    public function getAllPohodaNamesIndexedByVatPercent(int $domainId): array
    {
        $allVatsForDomain = $this->getAllForDomain($domainId);
        $vatsIndexedByVatPercent = [];
        foreach ($allVatsForDomain as $vat) {
            $vatsIndexedByVatPercent[(int)$vat->getPercent()] = $vat->getPohodaName();
        }

        return $vatsIndexedByVatPercent;
    }
}
