<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Preview;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Cart\CartFacade;
use Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer;
use Shopsys\FrameworkBundle\Model\Customer\User;
use Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreview;
use Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreviewFactory as BaseOrderPreviewFactory;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\CurrentPromoCodeFacade;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCode;
use Shopsys\FrameworkBundle\Model\Payment\Payment;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\Currency;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade;
use Shopsys\FrameworkBundle\Model\Transport\Transport;

class OrderPreviewFactory extends BaseOrderPreviewFactory
{
    /**
     * @param \Shopsys\ShopBundle\Model\Order\Preview\OrderPreviewCalculation $orderPreviewCalculation
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     * @param \Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer $currentCustomer
     * @param \Shopsys\FrameworkBundle\Model\Cart\CartFacade $cartFacade
     * @param \Shopsys\FrameworkBundle\Model\Order\PromoCode\CurrentPromoCodeFacade $currentPromoCodeFacade
     */
    public function __construct(
        OrderPreviewCalculation $orderPreviewCalculation,
        Domain $domain,
        CurrencyFacade $currencyFacade,
        CurrentCustomer $currentCustomer,
        CartFacade $cartFacade,
        CurrentPromoCodeFacade $currentPromoCodeFacade
    ) {
        parent::__construct($orderPreviewCalculation, $domain, $currencyFacade, $currentCustomer, $cartFacade, $currentPromoCodeFacade);
        $this->orderPreviewCalculation = $orderPreviewCalculation;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Transport\Transport|null $transport
     * @param \Shopsys\FrameworkBundle\Model\Payment\Payment|null $payment
     * @return \Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreview
     */
    public function createForCurrentUser(?Transport $transport = null, ?Payment $payment = null)
    {
        $currency = $this->currencyFacade->getDomainDefaultCurrencyByDomainId($this->domain->getId());
        $validEnteredPromoCode = $this->currentPromoCodeFacade->getValidEnteredPromoCodeOrNull();
        $validEnteredPromoCodePercent = null;
        if ($validEnteredPromoCode !== null) {
            $validEnteredPromoCodePercent = $validEnteredPromoCode->getPercent();
        }

        return $this->create(
            $currency,
            $this->domain->getId(),
            $this->cartFacade->getQuantifiedProductsOfCurrentCustomer(),
            $transport,
            $payment,
            $this->currentCustomer->findCurrentUser(),
            $validEnteredPromoCodePercent,
            $validEnteredPromoCode
        );
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Currency\Currency $currency
     * @param int $domainId
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedProduct[] $quantifiedProducts
     * @param \Shopsys\FrameworkBundle\Model\Transport\Transport|null $transport
     * @param \Shopsys\FrameworkBundle\Model\Payment\Payment|null $payment
     * @param \Shopsys\FrameworkBundle\Model\Customer\User|null $user
     * @param string|null $promoCodeDiscountPercent
     * @param \Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCode|null $validEnteredPromoCode
     * @return \Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreview
     */
    public function create(
        Currency $currency,
        $domainId,
        array $quantifiedProducts,
        ?Transport $transport = null,
        ?Payment $payment = null,
        ?User $user = null,
        ?string $promoCodeDiscountPercent = null,
        ?PromoCode $validEnteredPromoCode = null
    ): OrderPreview {
        return $this->orderPreviewCalculation->calculatePreview(
            $currency,
            $domainId,
            $quantifiedProducts,
            $transport,
            $payment,
            $user,
            $promoCodeDiscountPercent,
            $validEnteredPromoCode
        );
    }
}
