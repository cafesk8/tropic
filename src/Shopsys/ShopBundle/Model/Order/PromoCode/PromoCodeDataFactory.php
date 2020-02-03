<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\PromoCode;

use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCode as BasePromoCode;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeData as BasePromoCodeData;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeDataFactory as BasePromoCodeDataFactory;
use Shopsys\ShopBundle\Model\Category\CategoryFacade;
use Shopsys\ShopBundle\Model\Product\Brand\BrandFacade;
use Shopsys\ShopBundle\Model\Product\ProductFacade;

class PromoCodeDataFactory extends BasePromoCodeDataFactory
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade
     */
    private $adminDomainTabsFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeLimitFacade
     */
    private $promoCodeLimitFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Brand\BrandFacade
     */
    private $brandFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Category\CategoryFacade
     */
    private $categoryFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeLimitFacade $promoCodeLimitFacade
     * @param \Shopsys\ShopBundle\Model\Product\Brand\BrandFacade $brandFacade
     * @param \Shopsys\ShopBundle\Model\Category\CategoryFacade $categoryFacade
     * @param \Shopsys\ShopBundle\Model\Product\ProductFacade $productFacade
     */
    public function __construct(
        AdminDomainTabsFacade $adminDomainTabsFacade,
        PromoCodeLimitFacade $promoCodeLimitFacade,
        BrandFacade $brandFacade,
        CategoryFacade $categoryFacade,
        ProductFacade $productFacade
    ) {
        $this->adminDomainTabsFacade = $adminDomainTabsFacade;
        $this->promoCodeLimitFacade = $promoCodeLimitFacade;
        $this->productFacade = $productFacade;
        $this->brandFacade = $brandFacade;
        $this->categoryFacade = $categoryFacade;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeData
     */
    public function create(): BasePromoCodeData
    {
        $promoCodeData = new PromoCodeData();
        $promoCodeData->unlimited = false;
        $promoCodeData->numberOfUses = 0;
        $promoCodeData->domainId = $this->adminDomainTabsFacade->getSelectedDomainId();
        $promoCodeData->massGenerate = false;
        $promoCodeData->quantity = 0;
        $promoCodeData->percent = 0;
        $promoCodeData->nominalDiscount = Money::zero();
        $promoCodeData->useNominalDiscount = false;
        $promoCodeData->type = PromoCodeData::TYPE_PROMO_CODE;
        $promoCodeData->certificateValue = Money::zero();
        $promoCodeData->usageType = PromoCode::USAGE_TYPE_ALL;
        $promoCodeData->userType = PromoCode::USER_TYPE_ALL;
        $promoCodeData->combinable = false;
        $promoCodeData->limitType = PromoCode::LIMIT_TYPE_ALL;
        $promoCodeData->limits = [];
        $promoCodeData->brandLimits = [];
        $promoCodeData->categoryLimits = [];
        $promoCodeData->productLimits = [];

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

        $promoCodeData->limits = $this->promoCodeLimitFacade->getByPromoCode($promoCode);

        foreach ($promoCodeData->limits as $limit) {
            switch ($limit->getType()) {
                case PromoCode::LIMIT_TYPE_BRANDS:
                    $promoCodeData->brandLimits[] = $this->brandFacade->getById($limit->getObjectId());
                    break;
                case PromoCode::LIMIT_TYPE_CATEGORIES:
                    $promoCodeData->categoryLimits[] = $this->categoryFacade->getById($limit->getObjectId());
                    break;
                case PromoCode::LIMIT_TYPE_PRODUCTS:
                    $promoCodeData->productLimits[] = $this->productFacade->getById($limit->getObjectId());
                    break;
            }
        }

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
        $promoCodeData->nominalDiscount = $promoCode->getNominalDiscount();
        $promoCodeData->useNominalDiscount = $promoCode->isUseNominalDiscount();
        $promoCodeData->type = $promoCode->getType();
        $promoCodeData->certificateValue = $promoCode->getCertificateValue();
        $promoCodeData->certificateSku = $promoCode->getCertificateSku();
        $promoCodeData->usageType = $promoCode->getUsageType();
        $promoCodeData->userType = $promoCode->getUserType();
        $promoCodeData->combinable = $promoCode->isCombinable();
        $promoCodeData->limitType = $promoCode->getLimitType();
    }
}
