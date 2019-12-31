<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Cart\CartWatcher;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\FlashMessage\FlashMessageSender;
use Shopsys\FrameworkBundle\Model\Cart\Cart;
use Shopsys\FrameworkBundle\Model\Cart\Watcher\CartWatcher;
use Shopsys\FrameworkBundle\Model\Cart\Watcher\CartWatcherFacade as BaseCartWatcherFacade;
use Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer;
use Shopsys\ShopBundle\Model\Customer\User;
use Shopsys\ShopBundle\Model\Order\PromoCode\CurrentPromoCodeFacade;

/**
 * @property \Shopsys\ShopBundle\Model\Cart\CartWatcher\CartWatcher $cartWatcher
 */
class CartWatcherFacade extends BaseCartWatcherFacade
{
    /**
     * @var \Shopsys\ShopBundle\Model\Order\PromoCode\CurrentPromoCodeFacade
     */
    private $currentPromoCodeFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Component\FlashMessage\FlashMessageSender $flashMessageSender
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Model\Cart\Watcher\CartWatcher $cartWatcher
     * @param \Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer $currentCustomer
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\CurrentPromoCodeFacade $currentPromoCodeFacade
     */
    public function __construct(
        FlashMessageSender $flashMessageSender,
        EntityManagerInterface $em,
        CartWatcher $cartWatcher,
        CurrentCustomer $currentCustomer,
        CurrentPromoCodeFacade $currentPromoCodeFacade
    ) {
        parent::__construct($flashMessageSender, $em, $cartWatcher, $currentCustomer);
        $this->currentPromoCodeFacade = $currentPromoCodeFacade;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Cart\Cart $cart
     * @param \Shopsys\ShopBundle\Model\Customer\User|null $user
     */
    public function checkCartModifications(Cart $cart, ?User $user = null): void
    {
        parent::checkCartModifications($cart);

        $this->checkValidityOfEnteredPromoCodes($cart, $user);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Cart\Cart $cart
     * @param \Shopsys\ShopBundle\Model\Customer\User|null $user
     */
    public function checkValidityOfEnteredPromoCodes(Cart $cart, ?User $user = null): void
    {
        $enteredCodes = $this->currentPromoCodeFacade->getEnteredCodesFromSession();

        foreach ($enteredCodes as $enteredCode) {
            try {
                $this->currentPromoCodeFacade->checkPromoCodeValidity($enteredCode, $cart->getTotalWatchedPriceOfProducts(), $user);
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
}
