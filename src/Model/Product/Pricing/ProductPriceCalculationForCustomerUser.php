<?php

declare(strict_types=1);

namespace App\Model\Product\Pricing;

use App\Model\Pricing\Group\PricingGroupFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser as BaseCustomerUser;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculation;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForCustomerUser as BaseProductPriceCalculationForCustomerUser;
use Shopsys\FrameworkBundle\Model\Product\Product as BaseProduct;

/**
 * @property \App\Model\Product\Pricing\ProductPriceCalculation $productPriceCalculation
 */
class ProductPriceCalculationForCustomerUser extends BaseProductPriceCalculationForCustomerUser
{
    /**
     * @var \App\Model\Pricing\Group\PricingGroupFacade
     */
    private $pricingGroupFacade;

    /**
     * @param \App\Model\Product\Pricing\ProductPriceCalculation $productPriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser $currentCustomerUser
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade $pricingGroupSettingFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     */
    public function __construct(
        ProductPriceCalculation $productPriceCalculation,
        CurrentCustomerUser $currentCustomerUser,
        PricingGroupSettingFacade $pricingGroupSettingFacade,
        Domain $domain,
        PricingGroupFacade $pricingGroupFacade
    ) {
        parent::__construct($productPriceCalculation, $currentCustomerUser, $pricingGroupSettingFacade, $domain);
        $this->pricingGroupFacade = $pricingGroupFacade;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param int $domainId
     * @param \App\Model\Customer\User\CustomerUser|null $customerUser
     * @param bool $simulateRegistration
     * @param bool $salePrice
     * @return \App\Model\Product\Pricing\ProductPrice
     */
    public function calculatePriceForCustomerUserAndDomainId(
        BaseProduct $product,
        $domainId,
        ?BaseCustomerUser $customerUser = null,
        bool $simulateRegistration = false,
        bool $salePrice = false
    ) {
        if ($customerUser === null) {
            if ($simulateRegistration) {
                $pricingGroup = $this->pricingGroupFacade->getRegisteredCustomerPricingGroup($domainId);
            } else {
                $pricingGroup = $this->pricingGroupFacade->getDefaultPricingGroup($domainId);
            }
        } else {
            $pricingGroup = $customerUser->getPricingGroup();
        }

        if ($salePrice) {
            $pricingGroup = $this->pricingGroupFacade->getSalePricePricingGroup($domainId);
        }

        /** @var \App\Model\Product\Pricing\ProductPrice $productPrice */
        $productPrice = $this->productPriceCalculation->calculatePrice($product, $domainId, $pricingGroup);

        return $productPrice;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param bool $salePrice
     * @return \App\Model\Product\Pricing\ProductPrice|\Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice
     */
    public function calculatePriceForCurrentUser(BaseProduct $product, bool $salePrice = false)
    {
        $pricingGroup = $this->currentCustomerUser->getPricingGroup();
        $domainId = $this->domain->getId();
        if ($salePrice) {
            $pricingGroup = $this->pricingGroupFacade->getSalePricePricingGroup($domainId);
        }

        return $this->productPriceCalculation->calculatePrice(
            $product,
            $domainId,
            $pricingGroup
        );
    }
}
