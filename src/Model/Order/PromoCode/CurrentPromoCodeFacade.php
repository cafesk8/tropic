<?php

declare(strict_types=1);

namespace App\Model\Order\PromoCode;

use App\Model\Cart\Cart;
use App\Model\Customer\User\CustomerUser;
use App\Model\Order\Discount\CurrentOrderDiscountLevelFacade;
use App\Model\Order\Discount\OrderDiscountCalculation;
use App\Model\Order\PromoCode\Exception\InactivePromoCodeException;
use App\Model\Order\PromoCode\Exception\PromoCodeAlreadyAppliedException;
use App\Model\Order\PromoCode\Exception\PromoCodeIsNotBetterThanOrderLevelDiscountException;
use App\Model\Order\PromoCode\Exception\PromoCodeIsOnlyForLoggedCustomers;
use App\Model\Order\PromoCode\Exception\PromoCodeNotApplicableException;
use App\Model\Order\PromoCode\Exception\UsageLimitPromoCodeException;
use DateTime;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\CurrentPromoCodeFacade as BaseCurrentPromoCodeFacade;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\Exception\InvalidPromoCodeException;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeFacade;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @method \App\Model\Order\PromoCode\PromoCode|null getValidEnteredPromoCodeOrNull()
 */
class CurrentPromoCodeFacade extends BaseCurrentPromoCodeFacade
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \App\Model\Order\PromoCode\PromoCodeLimitFacade
     */
    private $promoCodeLimitFacade;

    /**
     * @var \App\Model\Order\Discount\CurrentOrderDiscountLevelFacade
     */
    private $currentOrderDiscountLevelFacade;

    /**
     * @var \App\Model\Order\Discount\OrderDiscountCalculation
     */
    private $orderDiscountCalculation;

    /**
     * @param \App\Model\Order\PromoCode\PromoCodeFacade $promoCodeFacade
     * @param \App\Model\Order\PromoCode\PromoCodeLimitFacade $promoCodeLimitFacade
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Order\Discount\CurrentOrderDiscountLevelFacade $currentOrderDiscountLevelFacade
     * @param \App\Model\Order\Discount\OrderDiscountCalculation $orderDiscountCalculation
     */
    public function __construct(
        PromoCodeFacade $promoCodeFacade,
        PromoCodeLimitFacade $promoCodeLimitFacade,
        SessionInterface $session,
        Domain $domain,
        CurrentOrderDiscountLevelFacade $currentOrderDiscountLevelFacade,
        OrderDiscountCalculation $orderDiscountCalculation
    ) {
        parent::__construct($promoCodeFacade, $session);
        $this->domain = $domain;
        $this->promoCodeLimitFacade = $promoCodeLimitFacade;
        $this->currentOrderDiscountLevelFacade = $currentOrderDiscountLevelFacade;
        $this->orderDiscountCalculation = $orderDiscountCalculation;
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
            $validPromoCodes[] = $this->promoCodeFacade->findPromoCodeByCodeAndDomainId($code, $this->domain->getId());
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
     * @param \App\Model\Customer\User\CustomerUser|null $customerUser
     */
    public function setEnteredPromoCode($enteredCode, ?Money $totalWatchedPriceOfProducts = null, ?CustomerUser $customerUser = null): void
    {
        if ($totalWatchedPriceOfProducts == null) {
            $totalWatchedPriceOfProducts = Money::zero();
        }

        $this->checkPromoCodeValidity($enteredCode, $totalWatchedPriceOfProducts, $customerUser);

        $promoCode = $this->promoCodeFacade->findPromoCodeByCodeAndDomainId($enteredCode, $this->domain->getId());
        $codesInSession = $this->getEnteredCodesFromSession();

        if ($promoCode === null) {
            throw new InvalidPromoCodeException($enteredCode);
        } elseif (in_array($enteredCode, $codesInSession, true)) {
            throw new PromoCodeAlreadyAppliedException($enteredCode);
        } else {
            $indexToReplace = $promoCode->isCombinable() ? null : $this->getNotCombinableCodeIndex($codesInSession);

            if ($indexToReplace === null) {
                $codesInSession[] = $enteredCode;
            } else {
                $codesInSession[$indexToReplace] = $enteredCode;
            }

            $this->session->set(static::PROMO_CODE_SESSION_KEY, $codesInSession);
        }
    }

    /**
     * @param string $enteredCode
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $totalWatchedPriceOfProducts
     * @param \App\Model\Customer\User\CustomerUser|null $customerUser
     */
    public function checkPromoCodeValidity(string $enteredCode, Money $totalWatchedPriceOfProducts, ?CustomerUser $customerUser = null): void
    {
        $promoCode = $this->promoCodeFacade->findPromoCodeByCodeAndDomainId($enteredCode, $this->domain->getId());

        if ($promoCode === null || $promoCode->getDomainId() !== $this->domain->getId()) {
            throw new InvalidPromoCodeException($enteredCode);
        } elseif ($promoCode->hasRemainingUses() === false) {
            if ($promoCode->isActive()) {
                throw new UsageLimitPromoCodeException($enteredCode);
            } else {
                throw new InactivePromoCodeException($enteredCode);
            }
        } elseif ($this->isPromoCodeValidInItsValidDates($promoCode) === false) {
            throw new \App\Model\Order\PromoCode\Exception\PromoCodeIsNotValidNow($enteredCode);
        } elseif ($promoCode->getMinOrderValue() !== null && $totalWatchedPriceOfProducts->isLessThan($promoCode->getMinOrderValue())) {
            throw new \App\Model\Order\PromoCode\Exception\MinimalOrderValueException($enteredCode, $promoCode->getMinOrderValue());
        }

        $this->checkPromoCodeUserTypeValidity($promoCode, $customerUser);
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

        $now = new DateTime('Europe/Prague');

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
     * @param \App\Model\Customer\User\CustomerUser|null $customerUser
     */
    private function checkPromoCodeUserTypeValidity(PromoCode $promoCode, ?CustomerUser $customerUser = null)
    {
        if ($promoCode->isUserTypeLogged() === true && $customerUser === null) {
            throw new PromoCodeIsOnlyForLoggedCustomers($promoCode->getCode());
        }
    }

    /**
     * @param string[] $promoCodeCodes
     * @return int|null
     */
    private function getNotCombinableCodeIndex(array $promoCodeCodes): ?int
    {
        foreach ($promoCodeCodes as $index => $promoCodeCode) {
            $promoCode = $this->promoCodeFacade->findPromoCodeByCodeAndDomainId($promoCodeCode, $this->domain->getId());

            if ($promoCode !== null && !$promoCode->isCombinable()) {
                return $index;
            }
        }

        return null;
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
        if ($promoCode->isTypeGiftCertificate()) {
            return;
        }

        $this->checkPromoCodeValueIsHigherThanOrderLevelDiscountValue($promoCode, $cart);

        if ($promoCode->getLimitType() === PromoCode::LIMIT_TYPE_ALL) {
            $applicableProducts = null;
        } else {
            $applicableProducts = $this->promoCodeLimitFacade->getAllApplicableProductsByLimits($promoCode->getLimits());
        }

        foreach ($cart->getItems() as $cartItem) {
            $product = $cartItem->getProduct();

            if ($applicableProducts === null || isset($applicableProducts[$product->getId()])) {
                if (!$product->isPromoDiscountDisabled($promoCode->getDomainId()) && !$product->isInAnySaleStock()) {
                    return;
                }
            }
        }

        throw new PromoCodeNotApplicableException($promoCode->getCode());
    }

    /**
     * @return int
     */
    public function removeAllEnteredPromoCodesThatAreNotGiftCertificates(): int
    {
        $enteredCodesFromSession = $this->getEnteredCodesFromSession();
        $removedPromoCodesCount = 0;
        foreach ($enteredCodesFromSession as $promoCodeCode) {
            $promoCode = $this->promoCodeFacade->findPromoCodeByCodeAndDomainId($promoCodeCode, $this->domain->getId());
            if ($promoCode !== null && $promoCode->isTypeGiftCertificate() === false) {
                $this->removeEnteredPromoCodeByCode($promoCodeCode);
                $removedPromoCodesCount++;
            }
        }

        return $removedPromoCodesCount;
    }

    /**
     * @param \App\Model\Order\PromoCode\PromoCode $promoCode
     * @param \App\Model\Cart\Cart $cart
     * @throws \App\Model\Order\PromoCode\Exception\PromoCodeIsNotBetterThanOrderLevelDiscountException
     */
    private function checkPromoCodeValueIsHigherThanOrderLevelDiscountValue(PromoCode $promoCode, Cart $cart): void
    {
        $activeOrderLevelDiscountId = $this->currentOrderDiscountLevelFacade->getActiveOrderLevelDiscountId();
        if (!$promoCode->isTypeGiftCertificate()
            && $activeOrderLevelDiscountId !== null
            && !$this->orderDiscountCalculation->isDiscountByPromoCodeBetterThanDiscountByOrderDiscountLevel(
                $cart->getQuantifiedProducts(),
                $promoCode,
                $this->domain->getId(),
                $cart->getCustomerUser(),
                $activeOrderLevelDiscountId
            )
        ) {
            throw new PromoCodeIsNotBetterThanOrderLevelDiscountException($promoCode->getCode());
        }
    }
}
