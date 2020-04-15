<?php

declare(strict_types=1);

namespace App\Model\Product\Pricing;

use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser as BaseCustomerUser;
use Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice;
use Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedProduct;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Product\Pricing\QuantifiedProductPriceCalculation as BaseQuantifiedProductPriceCalculation;

/**
 * @property \App\Model\Product\Pricing\ProductPriceCalculationForCustomerUser $productPriceCalculationForCustomerUser
 * @method __construct(\App\Model\Product\Pricing\ProductPriceCalculationForCustomerUser $productPriceCalculationForCustomerUser, \Shopsys\FrameworkBundle\Model\Pricing\Rounding $rounding, \Shopsys\FrameworkBundle\Model\Pricing\PriceCalculation $priceCalculation)
 * @method \Shopsys\FrameworkBundle\Component\Money\Money getTotalPriceVatAmount(\Shopsys\FrameworkBundle\Component\Money\Money $totalPriceWithVat, \App\Model\Pricing\Vat\Vat $vat)
 */
class QuantifiedProductPriceCalculation extends BaseQuantifiedProductPriceCalculation
{
    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedProduct $quantifiedProduct
     * @param int $domainId
     * @param \App\Model\Customer\User\CustomerUser|null $customerUser
     * @param bool $simulateRegistration
     * @return \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice
     */
    public function calculatePrice(
        QuantifiedProduct $quantifiedProduct,
        int $domainId,
        ?BaseCustomerUser $customerUser = null,
        bool $simulateRegistration = false
    ): QuantifiedItemPrice {
        $product = $quantifiedProduct->getProduct();

        $productPrice = $this->productPriceCalculationForCustomerUser->calculatePriceForCustomerUserAndDomainId(
            $product,
            $domainId,
            $customerUser,
            $simulateRegistration
        );

        $totalPriceWithVat = $this->getTotalPriceWithVat($quantifiedProduct, $productPrice);
        $totalPriceVatAmount = $this->getTotalPriceVatAmount($totalPriceWithVat, $product->getVatForDomain($domainId));
        $priceWithoutVat = $this->getTotalPriceWithoutVat($totalPriceWithVat, $totalPriceVatAmount);

        $totalPrice = new Price($priceWithoutVat, $totalPriceWithVat);

        return new QuantifiedItemPrice($productPrice, $totalPrice, $product->getVatForDomain($domainId));
    }

    /**
     * @param array $quantifiedProducts
     * @param int $domainId
     * @param \App\Model\Customer\User\CustomerUser|null $customerUser
     * @param bool $simulateRegistration
     * @return array
     */
    public function calculatePrices(
        array $quantifiedProducts,
        int $domainId,
        ?BaseCustomerUser $customerUser = null,
        bool $simulateRegistration = false
    ): array {
        $quantifiedItemsPrices = [];
        foreach ($quantifiedProducts as $quantifiedItemIndex => $quantifiedProduct) {
            $quantifiedItemsPrices[$quantifiedItemIndex] = $this->calculatePrice($quantifiedProduct, $domainId, $customerUser, $simulateRegistration);
        }

        return $quantifiedItemsPrices;
    }
}
