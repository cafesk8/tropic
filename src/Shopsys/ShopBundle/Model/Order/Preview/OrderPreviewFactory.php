<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Preview;

use InvalidArgumentException;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer;
use Shopsys\FrameworkBundle\Model\Customer\User;
use Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreviewFactory as BaseOrderPreviewFactory;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\CurrentPromoCodeFacade;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCode;
use Shopsys\FrameworkBundle\Model\Payment\Payment;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\Currency;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade;
use Shopsys\FrameworkBundle\Model\Transport\Transport;
use Shopsys\ShopBundle\Model\Cart\CartFacade;

class OrderPreviewFactory extends BaseOrderPreviewFactory
{
    /**
     * @var \Shopsys\ShopBundle\Model\Cart\CartFacade
     */
    protected $cartFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Preview\OrderPreviewCalculation $orderPreviewCalculation
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     * @param \Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer $currentCustomer
     * @param \Shopsys\ShopBundle\Model\Cart\CartFacade $cartFacade
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
        $validEnteredPromoCodes = $this->currentPromoCodeFacade->getValidEnteredPromoCodes();

        return $this->create(
            $currency,
            $this->domain->getId(),
            $this->cartFacade->getQuantifiedProductsOfCurrentCustomer(),
            $transport,
            $payment,
            $this->currentCustomer->findCurrentUser(),
            null,
            null,
            $this->cartFacade->getGifts(),
            $this->cartFacade->getPromoProducts(),
            $validEnteredPromoCodes
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
     * @param \Shopsys\ShopBundle\Model\Cart\Item\CartItem[] $giftsInCart
     * @param \Shopsys\ShopBundle\Model\Cart\Item\CartItem[]|null $promoProductsInCart
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode[] $validEnteredPromoCodes
     * @return \Shopsys\ShopBundle\Model\Order\Preview\OrderPreview
     */
    public function create(
        Currency $currency,
        $domainId,
        array $quantifiedProducts,
        ?Transport $transport = null,
        ?Payment $payment = null,
        ?User $user = null,
        ?string $promoCodeDiscountPercent = null,
        ?PromoCode $validEnteredPromoCode = null,
        ?array $giftsInCart = [],
        ?array $promoProductsInCart = [],
        array $validEnteredPromoCodes = []
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
            $user,
            $promoCodeDiscountPercent,
            $validEnteredPromoCode,
            $giftsInCart,
            $promoProductsInCart,
            $validEnteredPromoCodes
        );
    }
}
