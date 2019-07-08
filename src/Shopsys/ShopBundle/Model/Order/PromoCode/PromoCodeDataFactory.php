<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\PromoCode;

use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCode as BasePromoCode;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeData as BasePromoCodeData;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeDataFactory as BasePromoCodeDataFactory;

class PromoCodeDataFactory extends BasePromoCodeDataFactory
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade
     */
    private $adminDomainTabsFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade
     */
    public function __construct(AdminDomainTabsFacade $adminDomainTabsFacade)
    {
        $this->adminDomainTabsFacade = $adminDomainTabsFacade;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeData
     */
    public function create(): BasePromoCodeData
    {
        $promoCodeData = new PromoCodeData();
        $promoCodeData->unlimited = false;
        $promoCodeData->numberOfUses = 0;
        $promoCodeData->domainId = $this->adminDomainTabsFacade->getSelectedDomainId();
        $promoCodeData->massGenerate = false;
        $promoCodeData->quantity = 0;

        return $promoCodeData;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode $promoCode
     * @return \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeData
     */
    public function createFromPromoCode(BasePromoCode $promoCode): BasePromoCodeData
    {
        $promoCodeData = $this->create();
        $this->fillFromPromoCode($promoCodeData, $promoCode);

        return $promoCodeData;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeData $promoCodeData
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode $promoCode
     */
    protected function fillFromPromoCode(BasePromoCodeData $promoCodeData, BasePromoCode $promoCode)
    {
        parent::fillFromPromoCode($promoCodeData, $promoCode);

        $promoCodeData->unlimited = $promoCode->isUnlimited();
        $promoCodeData->usageLimit = $promoCode->getUsageLimit();
        $promoCodeData->numberOfUses = $promoCode->getNumberOfUses();
        $promoCodeData->validFrom = $promoCode->getValidFrom();
        $promoCodeData->validTo = $promoCode->getValidTo();
        $promoCodeData->domainId = $promoCode->getDomainId();
        $promoCodeData->minOrderValue = $promoCode->getMinOrderValue();
        $promoCodeData->massGenerate = $promoCode->isMassGenerated();
        $promoCodeData->prefix = $promoCode->getPrefix();
    }
}
