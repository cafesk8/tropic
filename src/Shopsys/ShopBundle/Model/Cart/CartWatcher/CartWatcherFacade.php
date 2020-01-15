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
use Symfony\Component\HttpFoundation\Session\SessionInterface;

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
     * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
     */
    private $session;

    /**
     * @param \Shopsys\FrameworkBundle\Component\FlashMessage\FlashMessageSender $flashMessageSender
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Model\Cart\Watcher\CartWatcher $cartWatcher
     * @param \Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer $currentCustomer
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\CurrentPromoCodeFacade $currentPromoCodeFacade
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     */
    public function __construct(
        FlashMessageSender $flashMessageSender,
        EntityManagerInterface $em,
        CartWatcher $cartWatcher,
        CurrentCustomer $currentCustomer,
        CurrentPromoCodeFacade $currentPromoCodeFacade,
        SessionInterface $session
    ) {
        parent::__construct($flashMessageSender, $em, $cartWatcher, $currentCustomer);
        $this->currentPromoCodeFacade = $currentPromoCodeFacade;
        $this->session = $session;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Cart\Cart $cart
     * @param \Shopsys\ShopBundle\Model\Customer\User|null $user
     */
    public function checkCartModifications(Cart $cart, ?User $user = null): void
    {
        parent::checkCartModifications($cart);

        $this->checkValidityOfEnteredPromoCode($cart, $user);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Cart\Cart $cart
     * @param \Shopsys\ShopBundle\Model\Customer\User|null $user
     */
    public function checkValidityOfEnteredPromoCode(Cart $cart, ?User $user = null): void
    {
        $enteredCode = $this->session->get(CurrentPromoCodeFacade::PROMO_CODE_SESSION_KEY);

        if ($enteredCode === null) {
            return;
        }

        try {
            $this->currentPromoCodeFacade->checkPromoCodeValidity($enteredCode, $cart->getTotalWatchedPriceOfProducts(), $user);
        } catch (\Exception $exception) {
            $this->flashMessageSender->addErrorFlash(
                t('Platnost slevového kupónu vypršela. Prosím, zkontrolujte ho.')
            );
            $this->currentPromoCodeFacade->removeEnteredPromoCode();
        }
    }
}
