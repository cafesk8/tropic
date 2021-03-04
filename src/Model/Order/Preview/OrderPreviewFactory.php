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
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Transport\Transport;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

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
     * @var \Symfony\Component\HttpFoundation\Session\Session
     */
    private SessionInterface $session;

    /**
     * @param \App\Model\Order\Preview\OrderPreviewCalculation $orderPreviewCalculation
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser $currentCustomerUser
     * @param \App\Model\Cart\CartFacade $cartFacade
     * @param \App\Model\Order\PromoCode\CurrentPromoCodeFacade $currentPromoCodeFacade
     * @param \Symfony\Component\HttpFoundation\Session\Session $session
     */
    public function __construct(
        OrderPreviewCalculation $orderPreviewCalculation,
        Domain $domain,
        CurrencyFacade $currencyFacade,
        CurrentCustomerUser $currentCustomerUser,
        CartFacade $cartFacade,
        CurrentPromoCodeFacade $currentPromoCodeFacade,
        SessionInterface $session
    ) {
        parent::__construct($orderPreviewCalculation, $domain, $currencyFacade, $currentCustomerUser, $cartFacade, $currentPromoCodeFacade);
        $this->orderPreviewCalculation = $orderPreviewCalculation;
        $this->session = $session;
    }

    /**
     * @param \App\Model\Transport\Transport|null $transport
     * @param \App\Model\Payment\Payment|null $payment
     * @param bool $simulateRegistration
     * @return \App\Model\Order\Preview\OrderPreview
     */
    public function createForCurrentUser(?Transport $transport = null, ?Payment $payment = null, bool $simulateRegistration = false): OrderPreview
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
            $validEnteredPromoCodes,
            $this->cartFacade->getOrderGiftProduct(),
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
     * @param \App\Model\Order\PromoCode\PromoCode[] $validEnteredPromoCodes
     * @param \App\Model\Product\Product|null $orderGiftProduct
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
        array $validEnteredPromoCodes = [],
        ?Product $orderGiftProduct = null,
        bool $simulateRegistration = false
    ): OrderPreview {
        if ($promoCodeDiscountPercent !== null || $validEnteredPromoCode !== null) {
            throw new InvalidArgumentException('Neither "$promoCodeDiscountPercent" nor "$validEnteredPromoCode" argument is supported, you need to use "$promoCodes" array instead');
        }
        $orderPreview = $this->orderPreviewCalculation->calculatePreview(
            $currency,
            $domainId,
            $quantifiedProducts,
            $transport,
            $payment,
            $customerUser,
            $promoCodeDiscountPercent,
            $validEnteredPromoCode,
            $giftsInCart,
            $validEnteredPromoCodes,
            $orderGiftProduct,
            $simulateRegistration
        );

        $this->session->set(OrderPreview::ITEMS_COUNT_SESSION_KEY, $orderPreview->getProductsCount());
        $this->session->set(OrderPreview::TOTAL_PRICE_SESSION_KEY, $orderPreview->getTotalPrice()->getPriceWithVat()->getAmount());

        return $orderPreview;
    }
}
