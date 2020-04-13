<?php

declare(strict_types=1);

namespace App\Model\Cart\CartWatcher;

use App\Model\Customer\User\CustomerUser;
use App\Model\Order\Discount\CurrentOrderDiscountLevelFacade;
use App\Model\Order\Discount\OrderDiscountLevelFacade;
use App\Model\Order\PromoCode\CurrentPromoCodeFacade;
use App\Model\Order\PromoCode\Exception\PromoCodeNotApplicableException;
use App\Model\Order\PromoCode\PromoCodeFacade;
use App\Model\Product\ProductFacade;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\FlashMessage\FlashMessageSender;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Component\FlashMessage\FlashMessage;
use Shopsys\FrameworkBundle\Component\FlashMessage\FlashMessageTrait;
use Shopsys\FrameworkBundle\Model\Cart\Cart;
use Shopsys\FrameworkBundle\Model\Cart\Watcher\CartWatcher;
use Shopsys\FrameworkBundle\Model\Cart\Watcher\CartWatcherFacade as BaseCartWatcherFacade;
use Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Twig\Environment;

/**
 * @property \App\Model\Cart\CartWatcher\CartWatcher $cartWatcher
 * @property \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
 */
class CartWatcherFacade extends BaseCartWatcherFacade
{
    use FlashMessageTrait;

    /**
     * @var \App\Model\Order\PromoCode\CurrentPromoCodeFacade
     */
    private $currentPromoCodeFacade;

    /**
     * @var \App\Model\Order\PromoCode\PromoCodeFacade
     */
    private $promoCodeFacade;

    /**
     * @var \App\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \App\Model\Order\Discount\OrderDiscountLevelFacade
     */
    private $orderDiscountLevelFacade;

    /**
     * @var \App\Model\Order\Discount\CurrentOrderDiscountLevelFacade
     */
    private $currentOrderDiscountLevelFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface $flashBag
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Model\Cart\Watcher\CartWatcher $cartWatcher
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser $currentCustomerUser
     * @param \App\Model\Order\PromoCode\CurrentPromoCodeFacade $currentPromoCodeFacade
     * @param \App\Model\Order\PromoCode\PromoCodeFacade $promoCodeFacade
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Model\Order\Discount\OrderDiscountLevelFacade $orderDiscountLevelFacade
     * @param \App\Model\Order\Discount\CurrentOrderDiscountLevelFacade $currentOrderDiscountLevelFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Twig\Environment $twigEnvironment
     */
    public function __construct(
        FlashBagInterface $flashBag,
        EntityManagerInterface $em,
        CartWatcher $cartWatcher,
        CurrentCustomerUser $currentCustomerUser,
        CurrentPromoCodeFacade $currentPromoCodeFacade,
        PromoCodeFacade $promoCodeFacade,
        ProductFacade $productFacade,
        OrderDiscountLevelFacade $orderDiscountLevelFacade,
        CurrentOrderDiscountLevelFacade $currentOrderDiscountLevelFacade,
        Domain $domain,
        Environment $twigEnvironment
    ) {
        parent::__construct($flashBag, $em, $cartWatcher, $currentCustomerUser, $twigEnvironment);
        $this->currentPromoCodeFacade = $currentPromoCodeFacade;
        $this->promoCodeFacade = $promoCodeFacade;
        $this->productFacade = $productFacade;
        $this->orderDiscountLevelFacade = $orderDiscountLevelFacade;
        $this->currentOrderDiscountLevelFacade = $currentOrderDiscountLevelFacade;
        $this->domain = $domain;
    }

    /**
     * @param \App\Model\Cart\Cart $cart
     * @param \App\Model\Customer\User\CustomerUser|null $customerUser
     */
    public function checkCartModifications(Cart $cart, ?CustomerUser $customerUser = null): void
    {
        $this->checkOrderDiscountLevel($cart->getTotalWatchedPriceOfProducts());
        $this->checkValidityOfEnteredPromoCodes($cart, $customerUser);
        $this->checkValidityOfGifts($cart);
        // parent method must be called after our checks because we need to perform checks with the previous watched price
        // that might change during checkModifiedPrices() call
        parent::checkCartModifications($cart);
    }

    /**
     * @param \App\Model\Cart\Cart $cart
     * @param \App\Model\Customer\User\CustomerUser|null $customerUser
     */
    public function checkValidityOfEnteredPromoCodes(Cart $cart, ?CustomerUser $customerUser = null): void
    {
        $enteredCodes = $this->currentPromoCodeFacade->getEnteredCodesFromSession();

        foreach ($enteredCodes as $enteredCode) {
            try {
                $this->currentPromoCodeFacade->checkPromoCodeValidity($enteredCode, $cart->getTotalWatchedPriceOfProducts(), $customerUser);
                $this->currentPromoCodeFacade->checkApplicability($this->promoCodeFacade->findPromoCodeByCode($enteredCode), $cart);
            } catch (PromoCodeNotApplicableException $exception) {
                $this->flashBag->add(FlashMessage::KEY_ERROR, t('Slevový kupón nelze aplikovat na žádný produkt v košíku.'));
                $this->currentPromoCodeFacade->removeEnteredPromoCodeByCode($enteredCode);
            } catch (\Exception $exception) {
                $this->flashBag->add(
                    FlashMessage::KEY_ERROR,
                    t('Platnost slevového kupónu "%code%" vypršela. Prosím, zkontrolujte ho.', [
                        '%code%' => $enteredCode,
                    ])
                );
                $this->currentPromoCodeFacade->removeEnteredPromoCodeByCode($enteredCode);
            }
        }
    }

    /**
     * @param \App\Model\Cart\Cart $cart
     */
    private function checkValidityOfGifts(Cart $cart): void
    {
        $giftCartItems = $cart->getGifts();

        $toFlush = [];
        foreach ($giftCartItems as $giftCartItem) {
            $product = $giftCartItem->getProduct();
            try {
                if (!$this->productFacade->isProductMarketable($product)) {
                    $this->addErrorFlashTwig(
                        t('Product <strong>{{ name }}</strong> you had in cart is no longer available. Please check your order.'),
                        ['name' => $product->getName()]
                    );

                    $cart->removeItemById($giftCartItem->getId());
                    $this->em->remove($giftCartItem);
                    $toFlush[] = $giftCartItem;
                }
            } catch (\Shopsys\FrameworkBundle\Model\Product\Exception\ProductNotFoundException $e) {
                $this->flashBag->add(
                    FlashMessage::KEY_ERROR,
                    t('Product you had in cart is no longer in available. Please check your order.')
                );
            }
        }

        if (count($toFlush) > 0) {
            $this->em->flush($toFlush);
        }
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $productsPrice
     */
    private function checkOrderDiscountLevel(Money $productsPrice): void
    {
        $activeOrderDiscountLevel = $this->orderDiscountLevelFacade->findMatchingLevel($this->domain->getId(), $productsPrice);
        if ($this->currentOrderDiscountLevelFacade->getActiveOrderLevelDiscountId() !== null && $activeOrderDiscountLevel === null) {
            $this->currentOrderDiscountLevelFacade->unsetActiveOrderLevelDiscount();
            $this->flashMessageSender->addInfoFlash(t('Automatická sleva na celý nákup byla deaktivována, protože cena produktů v košíku není dostatečná'));
        }
    }
}
