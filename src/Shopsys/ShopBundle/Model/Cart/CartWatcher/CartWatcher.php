<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Cart\CartWatcher;

use Shopsys\FrameworkBundle\Model\Cart\Cart;
use Shopsys\FrameworkBundle\Model\Cart\Watcher\CartWatcher as BaseCartWatcher;

class CartWatcher extends BaseCartWatcher
{
    /**
     * @param \Shopsys\ShopBundle\Model\Cart\Cart $cart
     * @return string
     */
    public function isEmailTransportCart(Cart $cart): bool
    {
        $noTypeCount = 0;
        $giftCertificateCount = 0;

        foreach ($cart->getItems() as $cartItem) {
            if ($cartItem->getProduct()->isProductTypeGiftCertificate()) {
                $giftCertificateCount++;
            } else {
                $noTypeCount++;
            }

            if ($noTypeCount > 0 && $giftCertificateCount > 0) {
                return false;
            }
        }

        return $giftCertificateCount > 0;
    }
}
