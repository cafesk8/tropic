<?php

declare(strict_types=1);

namespace App\Model\Cart\CartWatcher;

use App\Model\Customer\User\CustomerUser;
use App\Model\Order\PromoCode\CurrentPromoCodeFacade;
use App\Model\Order\PromoCode\Exception\PromoCodeNotApplicableException;
use App\Model\Order\PromoCode\PromoCodeFacade;
use App\Model\Product\ProductFacade;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\FlashMessage\FlashMessageSender;
use Shopsys\FrameworkBundle\Model\Cart\Cart;
use Shopsys\FrameworkBundle\Model\Cart\Watcher\CartWatcher;
use Shopsys\FrameworkBundle\Model\Cart\Watcher\CartWatcherFacade as BaseCartWatcherFacade;
use Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser;

/**
 * @property \App\Model\Cart\CartWatcher\CartWatcher $cartWatcher
 * @property \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
 */
class CartWatcherFacade extends BaseCartWatcherFacade
{
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
     * @param \Shopsys\FrameworkBundle\Component\FlashMessage\FlashMessageSender $flashMessageSender
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Model\Cart\Watcher\CartWatcher $cartWatcher
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser $currentCustomerUser
     * @param \App\Model\Order\PromoCode\CurrentPromoCodeFacade $currentPromoCodeFacade
     * @param \App\Model\Order\PromoCode\PromoCodeFacade $promoCodeFacade
     * @param \App\Model\Product\ProductFacade $productFacade
     */
    public function __construct(
        FlashMessageSender $flashMessageSender,
        EntityManagerInterface $em,
        CartWatcher $cartWatcher,
        CurrentCustomerUser $currentCustomerUser,
        CurrentPromoCodeFacade $currentPromoCodeFacade,
        PromoCodeFacade $promoCodeFacade,
        ProductFacade $productFacade
    ) {
        parent::__construct($flashMessageSender, $em, $cartWatcher, $currentCustomerUser);
        $this->currentPromoCodeFacade = $currentPromoCodeFacade;
        $this->promoCodeFacade = $promoCodeFacade;
        $this->productFacade = $productFacade;
    }

    /**
     * @param \App\Model\Cart\Cart $cart
     * @param \App\Model\Customer\User\CustomerUser|null $customerUser
     */
    public function checkCartModifications(Cart $cart, ?CustomerUser $customerUser = null): void
    {
        parent::checkCartModifications($cart);

        $this->checkValidityOfEnteredPromoCodes($cart, $customerUser);
        $this->checkValidityOfGifts($cart);
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
                $this->flashMessageSender->addErrorFlash(t('Slevový kupón nelze aplikovat na žádný produkt v košíku.'));
                $this->currentPromoCodeFacade->removeEnteredPromoCodeByCode($enteredCode);
            } catch (\Exception $exception) {
                $this->flashMessageSender->addErrorFlash(
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
                    $this->flashMessageSender->addErrorFlashTwig(
                        t('Product <strong>{{ name }}</strong> you had in cart is no longer available. Please check your order.'),
                        ['name' => $product->getName()]
                    );

                    $cart->removeItemById($giftCartItem->getId());
                    $this->em->remove($giftCartItem);
                    $toFlush[] = $giftCartItem;
                }
            } catch (\Shopsys\FrameworkBundle\Model\Product\Exception\ProductNotFoundException $e) {
                $this->flashMessageSender->addErrorFlash(
                    t('Product you had in cart is no longer in available. Please check your order.')
                );
            }
        }

        if (count($toFlush) > 0) {
            $this->em->flush($toFlush);
        }
    }
}
