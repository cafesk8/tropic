<?php

declare(strict_types=1);

namespace App\Model\Order\PromoCode;

use DateTime;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\CurrentPromoCodeFacade as BaseCurrentPromoCodeFacade;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeFacade;
use App\Model\Cart\Cart;
use App\Model\Customer\User;
use App\Model\Order\PromoCode\Exception\PromoCodeAlreadyAppliedException;
use App\Model\Order\PromoCode\Exception\PromoCodeIsOnlyForLoggedBushmanClubMembers;
use App\Model\Order\PromoCode\Exception\PromoCodeIsOnlyForLoggedCustomers;
use App\Model\Order\PromoCode\Exception\PromoCodeNotApplicableException;
use App\Model\Order\PromoCode\Exception\PromoCodeNotCombinableException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @method \App\Model\Order\PromoCode\PromoCode|null getValidEnteredPromoCodeOrNull()
 */
class CurrentPromoCodeFacade extends BaseCurrentPromoCodeFacade
{
    public const SESSION_CART_PRODUCT_PRICES_TYPE = 'cartProductPricesTypes';

    public const CART_PRODUCT_ACTION_PRICE_TYPE_MIX = 'mixed';
    public const CART_PRODUCT_ACTION_PRICE_TYPE_ANY = 'any';
    public const CART_PRODUCT_ACTION_PRICE_TYPE_ALL = 'all';

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \App\Model\Order\PromoCode\PromoCodeLimitFacade
     */
    private $promoCodeLimitFacade;

    /**
     * @param \App\Model\Order\PromoCode\PromoCodeFacade $promoCodeFacade
     * @param \App\Model\Order\PromoCode\PromoCodeLimitFacade $promoCodeLimitFacade
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(PromoCodeFacade $promoCodeFacade, PromoCodeLimitFacade $promoCodeLimitFacade, SessionInterface $session, Domain $domain)
    {
        parent::__construct($promoCodeFacade, $session);
        $this->domain = $domain;
        $this->promoCodeLimitFacade = $promoCodeLimitFacade;
    }

    /**
     * @var \App\Model\Order\PromoCode\PromoCodeFacade
     */
    protected $promoCodeFacade;

    /**
     * @return \App\Model\Order\PromoCode\PromoCode[]
     */
    public function getValidEnteredPromoCodes(): array
    {
        $enteredCodes = $this->getEnteredCodesFromSession();
        $validPromoCodes = [];
        foreach ($enteredCodes as $code) {
            $validPromoCodes[] = $this->promoCodeFacade->findPromoCodeByCode($code);
        }

        return array_filter($validPromoCodes);
    }

    /**
     * @param \App\Model\Order\PromoCode\PromoCode $promoCode
     */
    public function usePromoCode(PromoCode $promoCode): void
    {
        $this->promoCodeFacade->usePromoCode($promoCode);
    }

    /**
     * @param string $enteredCode
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $totalWatchedPriceOfProducts
     * @param \App\Model\Customer\User|null $user
     */
    public function setEnteredPromoCode($enteredCode, ?Money $totalWatchedPriceOfProducts = null, ?User $user = null): void
    {
        if ($totalWatchedPriceOfProducts == null) {
            $totalWatchedPriceOfProducts = Money::zero();
        }

        $this->checkPromoCodeValidity($enteredCode, $totalWatchedPriceOfProducts, $user);

        $promoCode = $this->promoCodeFacade->findPromoCodeByCode($enteredCode);
        $codesInSession = $this->getEnteredCodesFromSession();
        if ($promoCode === null) {
            throw new \Shopsys\FrameworkBundle\Model\Order\PromoCode\Exception\InvalidPromoCodeException($enteredCode);
        } elseif (in_array($enteredCode, $codesInSession, true)) {
            throw new PromoCodeAlreadyAppliedException($enteredCode);
        } else {
            $this->checkExistingPromoCodeIsCombinable($codesInSession);
            $codesInSession[] = $enteredCode;
            $this->session->set(static::PROMO_CODE_SESSION_KEY, $codesInSession);
        }
    }

    /**
     * @param string $enteredCode
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $totalWatchedPriceOfProducts
     * @param \App\Model\Customer\User|null $user
     */
    public function checkPromoCodeValidity(string $enteredCode, Money $totalWatchedPriceOfProducts, ?User $user = null): void
    {
        $promoCode = $this->promoCodeFacade->findPromoCodeByCode($enteredCode);

        $productPricesType = $this->session->get(self::SESSION_CART_PRODUCT_PRICES_TYPE);

        if ($promoCode === null || $promoCode->getDomainId() !== $this->domain->getId()) {
            throw new \Shopsys\FrameworkBundle\Model\Order\PromoCode\Exception\InvalidPromoCodeException($enteredCode);
        } elseif ($promoCode->hasRemainingUses() === false) {
            throw new \App\Model\Order\PromoCode\Exception\UsageLimitPromoCodeException($enteredCode);
        } elseif ($this->isPromoCodeValidInItsValidDates($promoCode) === false) {
            throw new \App\Model\Order\PromoCode\Exception\PromoCodeIsNotValidNow($enteredCode);
        } elseif ($promoCode->getMinOrderValue() !== null && $totalWatchedPriceOfProducts->isLessThan($promoCode->getMinOrderValue())) {
            throw new \App\Model\Order\PromoCode\Exception\MinimalOrderValueException($enteredCode, $promoCode->getMinOrderValue());
        } elseif ($promoCode->getUsageType() === PromoCode::USAGE_TYPE_NO_ACTION_PRICE && $productPricesType === self::CART_PRODUCT_ACTION_PRICE_TYPE_ALL) {
            throw new \App\Model\Order\PromoCode\Exception\PromoCodeNoActionPriceUsageException($enteredCode);
        } elseif ($promoCode->getUsageType() === PromoCode::USAGE_TYPE_WITH_ACTION_PRICE && $productPricesType === self::CART_PRODUCT_ACTION_PRICE_TYPE_ANY) {
            throw new \App\Model\Order\PromoCode\Exception\PromoCodeWithActionPriceUsageException($enteredCode);
        } elseif (!$promoCode->isCombinable() && count($this->getValidEnteredPromoCodes()) > 1) {
            throw new PromoCodeNotCombinableException($enteredCode);
        }

        $this->checkPromoCodeUserTypeValidity($promoCode, $user);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedItemPrice[] $quantifiedItemsPrices
     */
    public function checkProductActionPriceType(array $quantifiedItemsPrices)
    {
        $hasDiscountProductCount = 0;
        foreach ($quantifiedItemsPrices as $quantifiedItemsPrice) {
            /** @var \App\Model\Product\Pricing\ProductPrice $productPrice */
            $productPrice = $quantifiedItemsPrice->getUnitPrice();
            if ($productPrice->isActionPriceByUsedForPromoCode() === true) {
                $hasDiscountProductCount++;
            }
        }

        $productActionPriceType = self::CART_PRODUCT_ACTION_PRICE_TYPE_MIX;
        if ($hasDiscountProductCount === 0) {
            $productActionPriceType = self::CART_PRODUCT_ACTION_PRICE_TYPE_ANY;
        } elseif ($hasDiscountProductCount === count($quantifiedItemsPrices)) {
            $productActionPriceType = self::CART_PRODUCT_ACTION_PRICE_TYPE_ALL;
        }

        $this->session->set(self::SESSION_CART_PRODUCT_PRICES_TYPE, $productActionPriceType);
    }

    /**
     * @param \App\Model\Order\PromoCode\PromoCode $promoCode
     * @return bool
     */
    private function isPromoCodeValidInItsValidDates(PromoCode $promoCode): bool
    {
        $validFrom = $promoCode->getValidFrom();

        $validTo = $promoCode->getValidTo();
        if ($validTo !== null) {
            $validTo->setTime(23, 59, 59);
        }

        $now = new DateTime();

        if ($validFrom === null && $validTo === null) {
            return true;
        }

        if ($validFrom !== null && $validTo !== null) {
            return $validFrom < $now && $now < $validTo;
        }

        if ($validFrom !== null && $validTo === null) {
            return $validFrom < $now;
        }

        return $now < $validTo;
    }

    /**
     * @param \App\Model\Order\PromoCode\PromoCode $promoCode
     * @param \App\Model\Customer\User|null $user
     */
    private function checkPromoCodeUserTypeValidity(PromoCode $promoCode, ?User $user = null)
    {
        if ($promoCode->isUserTypeLogged() === true && $user === null) {
            throw new PromoCodeIsOnlyForLoggedCustomers($promoCode->getCode());
        }

        if ($promoCode->isUserTypeBushmanClubMembers() === true && ($user === null || $user->isMemberOfLoyaltyProgram() === false)) {
            throw new PromoCodeIsOnlyForLoggedBushmanClubMembers($promoCode->getCode());
        }
    }

    /**
     * @param string[] $promoCodes
     */
    private function checkExistingPromoCodeIsCombinable(array $promoCodes)
    {
        if (count($promoCodes) === 1) {
            $promoCodeCode = reset($promoCodes);
            $promoCode = $this->promoCodeFacade->findPromoCodeByCode($promoCodeCode);
            if ($promoCode !== null && !$promoCode->isCombinable()) {
                throw new PromoCodeNotCombinableException($promoCode->getCode());
            }
        }
    }

    /**
     * @return string[]
     */
    public function getEnteredCodesFromSession(): array
    {
        $enteredCodes = $this->session->get(static::PROMO_CODE_SESSION_KEY, []);

        // to ensure backward compatibility with existing users that have only string value of promo code in their session
        if (is_string($enteredCodes)) {
            return [$enteredCodes];
        }

        return $enteredCodes;
    }

    /**
     * @param string $code
     */
    public function removeEnteredPromoCodeByCode(string $code): void
    {
        $promoCodes = $this->getEnteredCodesFromSession();
        $key = array_search($code, $promoCodes, true);
        if ($key !== false) {
            unset($promoCodes[$key]);
        }
        $this->session->set(static::PROMO_CODE_SESSION_KEY, $promoCodes);
    }

    /**
     * @param \App\Model\Order\PromoCode\PromoCode $promoCode
     * @param \App\Model\Cart\Cart $cart
     */
    public function checkApplicability(PromoCode $promoCode, Cart $cart): void
    {
        if ($promoCode->getLimitType() === PromoCode::LIMIT_TYPE_ALL) {
            return;
        }

        $applicableProductIds = $this->promoCodeLimitFacade->getAllApplicableProductIdsByLimits($promoCode->getLimits());

        foreach ($cart->getItems() as $cartItem) {
            if (in_array($cartItem->getProduct()->getId(), $applicableProductIds, true)) {
                return;
            }
        }

        throw new PromoCodeNotApplicableException($promoCode->getCode());
    }
}
