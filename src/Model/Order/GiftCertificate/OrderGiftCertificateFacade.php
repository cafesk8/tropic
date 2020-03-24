<?php

declare(strict_types=1);

namespace App\Model\Order\GiftCertificate;

use App\Model\Mail\AllMailTemplatesData;
use App\Model\Order\GiftCertificate\Mail\OrderGiftCertificateMailFacade;
use App\Model\Order\GiftCertificate\Pdf\OrderGiftCertificatePdfFacade;
use App\Model\Order\Order;
use App\Model\Order\PromoCode\PromoCode;
use App\Model\Order\PromoCode\PromoCodeFacade;
use Doctrine\ORM\EntityManagerInterface;

class OrderGiftCertificateFacade
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator
     */
    private $em;

    /**
     * @var \App\Model\Order\PromoCode\PromoCodeFacade
     */
    private $promoCodeFacade;

    /**
     * @var \App\Model\Order\GiftCertificate\Mail\OrderGiftCertificateMailFacade
     */
    private $orderGiftCertificateMailFacade;

    /**
     * @var \App\Model\Order\GiftCertificate\OrderGiftCertificateFactory
     */
    private $orderGiftCertificateFactory;

    /**
     * @var \App\Model\Order\GiftCertificate\Pdf\OrderGiftCertificatePdfFacade
     */
    private $orderGiftCertificatePdfFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
     * @param \App\Model\Order\PromoCode\PromoCodeFacade $promoCodeFacade
     * @param \App\Model\Order\GiftCertificate\Mail\OrderGiftCertificateMailFacade $orderGiftCertificateMailFacade
     * @param \App\Model\Order\GiftCertificate\OrderGiftCertificateFactory $orderGiftCertificateFactory
     * @param \App\Model\Order\GiftCertificate\Pdf\OrderGiftCertificatePdfFacade $orderGiftCertificatePdfFacade
     */
    public function __construct(
        EntityManagerInterface $em,
        PromoCodeFacade $promoCodeFacade,
        OrderGiftCertificateMailFacade $orderGiftCertificateMailFacade,
        OrderGiftCertificateFactory $orderGiftCertificateFactory,
        OrderGiftCertificatePdfFacade $orderGiftCertificatePdfFacade
    ) {
        $this->em = $em;
        $this->promoCodeFacade = $promoCodeFacade;
        $this->orderGiftCertificateMailFacade = $orderGiftCertificateMailFacade;
        $this->orderGiftCertificateFactory = $orderGiftCertificateFactory;
        $this->orderGiftCertificatePdfFacade = $orderGiftCertificatePdfFacade;
    }

    /**
     * @param \App\Model\Order\Order $order
     * @param \App\Model\Order\PromoCode\PromoCode $giftCertificate
     * @return \App\Model\Order\GiftCertificate\OrderGiftCertificate
     */
    public function create(Order $order, PromoCode $giftCertificate): OrderGiftCertificate
    {
        $orderGiftCertificate = $this->orderGiftCertificateFactory->create($order, $giftCertificate);
        $this->em->persist($orderGiftCertificate);
        $this->em->flush($orderGiftCertificate);
        $this->orderGiftCertificatePdfFacade->create($orderGiftCertificate);
        $this->orderGiftCertificateMailFacade->sendGiftCertificateEmail($orderGiftCertificate, AllMailTemplatesData::GIFT_CERTIFICATE);

        return $orderGiftCertificate;
    }

    /**
     * @param \App\Model\Order\GiftCertificate\OrderGiftCertificate[] $orderGiftCertificates
     */
    public function activate(array $orderGiftCertificates): void
    {
        $giftCertificates = array_map(function (OrderGiftCertificate $orderGiftCertificate) {
            $this->orderGiftCertificateMailFacade->sendGiftCertificateEmail($orderGiftCertificate, AllMailTemplatesData::GIFT_CERTIFICATE_ACTIVATED);
            return $orderGiftCertificate->getGiftCertificate();
        }, $orderGiftCertificates);
        $this->promoCodeFacade->activate($giftCertificates);
    }
}
