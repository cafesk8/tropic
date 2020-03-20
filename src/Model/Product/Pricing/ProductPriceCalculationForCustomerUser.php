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
 * @method \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice calculatePriceForCurrentUser(\App\Model\Product\Product $product)
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
     * @return \App\Model\Product\Pricing\ProductPrice
     */
    public function calculatePriceForCustomerUserAndDomainId(BaseProduct $product, $domainId, ?BaseCustomerUser $customerUser = null, bool $simulateRegistration = false)
    {
        if ($customerUser === null) {
            if ($simulateRegistration) {
                $pricingGroup = $this->pricingGroupFacade->getForRegisteredCustomer();
            } else {
                $pricingGroup = $this->pricingGroupSettingFacade->getDefaultPricingGroupByDomainId($domainId);
            }
        } else {
            $pricingGroup = $customerUser->getPricingGroup();
        }

        /** @var \App\Model\Product\Pricing\ProductPrice $productPrice */
        $productPrice = $this->productPriceCalculation->calculatePrice($product, $domainId, $pricingGroup);

        return $productPrice;
    }
}
