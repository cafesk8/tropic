<?php

declare(strict_types=1);

namespace App\Model\Order\GiftCertificate;

use App\Model\Order\Order;
use App\Model\Order\PromoCode\PromoCode;

class OrderGiftCertificateFactory
{
    /**
     * @param \App\Model\Order\Order $order
     * @param \App\Model\Order\PromoCode\PromoCode $giftCertificate
     * @return \App\Model\Order\GiftCertificate\OrderGiftCertificate
     */
    public function create(Order $order, PromoCode $giftCertificate): OrderGiftCertificate
    {
        return new OrderGiftCertificate($order, $giftCertificate);
    }
}
