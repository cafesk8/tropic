<?php

declare(strict_types=1);

namespace App\Model\Order\Preview;

use App\Model\Cart\CartFacade;
use InvalidArgumentException;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser;
use Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreviewFactory as BaseOrderPreviewFactory;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\CurrentPromoCodeFacade;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCode;
use Shopsys\FrameworkBundle\Model\Payment\Payment;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\Currency;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade;
use Shopsys\FrameworkBundle\Model\Transport\Transport;

/**
 * @property \App\Model\Order\Preview\OrderPreviewCalculation $orderPreviewCalculation
 * @property \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
 * @property \App\Model\Order\PromoCode\CurrentPromoCodeFacade $currentPromoCodeFacade
 */
class OrderPreviewFactory extends BaseOrderPreviewFactory
{
    /**
     * @var \App\Model\Cart\CartFacade
     */
    protected $cartFacade;

    /**
     * @param \App\Model\Order\Preview\OrderPreviewCalculation $orderPreviewCalculation
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser $currentCustomerUser
     * @param \App\Model\Cart\CartFacade $cartFacade
     * @param \App\Model\Order\PromoCode\CurrentPromoCodeFacade $currentPromoCodeFacade
     */
    public function __construct(
        OrderPreviewCalculation $orderPreviewCalculation,
        Domain $domain,
        CurrencyFacade $currencyFacade,
        CurrentCustomerUser $currentCustomerUser,
        CartFacade $cartFacade,
        CurrentPromoCodeFacade $currentPromoCodeFacade
    ) {
        parent::__construct($orderPreviewCalculation, $domain, $currencyFacade, $currentCustomerUser, $cartFacade, $currentPromoCodeFacade);
        $this->orderPreviewCalculation = $orderPreviewCalculation;
    }

    /**
     * @param \App\Model\Transport\Transport|null $transport
     * @param \App\Model\Payment\Payment|null $payment
     * @param bool $simulateRegistration
     * @return \App\Model\Order\Preview\OrderPreview
     */
    public function createForCurrentUser(?Transport $transport = null, ?Payment $payment = null, bool $simulateRegistration = false)
    {
        $currency = $this->currencyFacade->getDomainDefaultCurrencyByDomainId($this->domain->getId());
        $validEnteredPromoCodes = $this->currentPromoCodeFacade->getValidEnteredPromoCodes();

        return $this->create(
            $currency,
            $this->domain->getId(),
            $this->cartFacade->getQuantifiedProductsOfCurrentCustomer(),
            $transport,
            $payment,
            $this->currentCustomerUser->findCurrentCustomerUser(),
            null,
            null,
            $this->cartFacade->getGifts(),
            $this->cartFacade->getPromoProducts(),
            $validEnteredPromoCodes,
            $simulateRegistration
        );
    }

    /**
     * @param \App\Model\Pricing\Currency\Currency $currency
     * @param int $domainId
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedProduct[] $quantifiedProducts
     * @param \App\Model\Transport\Transport|null $transport
     * @param \App\Model\Payment\Payment|null $payment
     * @param \App\Model\Customer\User\CustomerUser|null $customerUser
     * @param string|null $promoCodeDiscountPercent
     * @param \App\Model\Order\PromoCode\PromoCode|null $validEnteredPromoCode
     * @param \App\Model\Cart\Item\CartItem[] $giftsInCart
     * @param \App\Model\Cart\Item\CartItem[]|null $promoProductsInCart
     * @param \App\Model\Order\PromoCode\PromoCode[] $validEnteredPromoCodes
     * @param bool $simulateRegistration
     * @return \App\Model\Order\Preview\OrderPreview
     */
    public function create(
        Currency $currency,
        $domainId,
        array $quantifiedProducts,
        ?Transport $transport = null,
        ?Payment $payment = null,
        ?CustomerUser $customerUser = null,
        ?string $promoCodeDiscountPercent = null,
        ?PromoCode $validEnteredPromoCode = null,
        ?array $giftsInCart = [],
        ?array $promoProductsInCart = [],
        array $validEnteredPromoCodes = [],
        bool $simulateRegistration = false
    ): OrderPreview {
        if ($promoCodeDiscountPercent !== null || $validEnteredPromoCode !== null) {
            throw new InvalidArgumentException('Neither "$promoCodeDiscountPercent" nor "$validEnteredPromoCode" argument is supported, you need to use "$promoCodes" array instead');
        }
        return $this->orderPreviewCalculation->calculatePreview(
            $currency,
            $domainId,
            $quantifiedProducts,
            $transport,
            $payment,
            $customerUser,
            $promoCodeDiscountPercent,
            $validEnteredPromoCode,
            $giftsInCart,
            $promoProductsInCart,
            $validEnteredPromoCodes,
            $simulateRegistration
        );
    }
}
