<?php

declare(strict_types=1);

namespace App\Model\Order\GiftCertificate;

use App\Model\Order\PromoCode\PromoCodeFacade;

class OrderGiftCertificateFacade
{
    /**
     * @var \App\Model\Order\PromoCode\PromoCodeFacade
     */
    private $promoCodeFacade;

    /**
     * @param \App\Model\Order\PromoCode\PromoCodeFacade $promoCodeFacade
     */
    public function __construct(PromoCodeFacade $promoCodeFacade)
    {
        $this->promoCodeFacade = $promoCodeFacade;
    }

    /**
     * @param \App\Model\Order\GiftCertificate\OrderGiftCertificate[] $orderGiftCertificates
     */
    public function activate(array $orderGiftCertificates): void
    {
        $giftCertificates = array_map(function (OrderGiftCertificate $orderGiftCertificate) {
            return $orderGiftCertificate->getGiftCertificate();
        }, $orderGiftCertificates);
        $this->promoCodeFacade->activate($giftCertificates);
    }
}
