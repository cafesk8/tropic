<?php

declare(strict_types=1);

namespace App\Model\Order\GiftCertificate;

use App\Model\Order\GiftCertificate\Mail\OrderGiftCertificateMailFacade;
use App\Model\Order\PromoCode\PromoCodeFacade;

class OrderGiftCertificateFacade
{
    /**
     * @var \App\Model\Order\PromoCode\PromoCodeFacade
     */
    private $promoCodeFacade;

    /**
     * @var \App\Model\Order\GiftCertificate\Mail\OrderGiftCertificateMailFacade
     */
    private $orderGiftCertificateMailFacade;

    /**
     * @param \App\Model\Order\PromoCode\PromoCodeFacade $promoCodeFacade
     * @param \App\Model\Order\GiftCertificate\Mail\OrderGiftCertificateMailFacade $orderGiftCertificateMailFacade
     */
    public function __construct(PromoCodeFacade $promoCodeFacade, OrderGiftCertificateMailFacade $orderGiftCertificateMailFacade)
    {
        $this->promoCodeFacade = $promoCodeFacade;
        $this->orderGiftCertificateMailFacade = $orderGiftCertificateMailFacade;
    }

    /**
     * @param \App\Model\Order\GiftCertificate\OrderGiftCertificate[] $orderGiftCertificates
     */
    public function activate(array $orderGiftCertificates): void
    {
        $giftCertificates = array_map(function (OrderGiftCertificate $orderGiftCertificate) {
            $this->orderGiftCertificateMailFacade->sendGiftCertificateEmail($orderGiftCertificate);
            return $orderGiftCertificate->getGiftCertificate();
        }, $orderGiftCertificates);
        $this->promoCodeFacade->activate($giftCertificates);
    }
}
