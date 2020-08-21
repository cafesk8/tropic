<?php

declare(strict_types=1);

namespace App\Model\Order\GiftCertificate;

use App\Model\Order\GiftCertificate\Mail\OrderGiftCertificateMail;
use App\Model\Order\GiftCertificate\Mail\OrderGiftCertificateMailFacade;
use App\Model\Order\GiftCertificate\Pdf\OrderGiftCertificatePdfFacade;
use App\Model\Order\Order;
use App\Model\Order\PromoCode\PromoCode;
use App\Model\Order\PromoCode\PromoCodeDataFactory;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class OrderGiftCertificateFacade
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator
     */
    private $em;

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
     * @var \App\Model\Order\PromoCode\PromoCodeDataFactory
     */
    private $promoCodeDataFactory;

    /**
     * @param \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
     * @param \App\Model\Order\GiftCertificate\Mail\OrderGiftCertificateMailFacade $orderGiftCertificateMailFacade
     * @param \App\Model\Order\GiftCertificate\OrderGiftCertificateFactory $orderGiftCertificateFactory
     * @param \App\Model\Order\GiftCertificate\Pdf\OrderGiftCertificatePdfFacade $orderGiftCertificatePdfFacade
     * @param \App\Model\Order\PromoCode\PromoCodeDataFactory $promoCodeDataFactory
     */
    public function __construct(
        EntityManagerInterface $em,
        OrderGiftCertificateMailFacade $orderGiftCertificateMailFacade,
        OrderGiftCertificateFactory $orderGiftCertificateFactory,
        OrderGiftCertificatePdfFacade $orderGiftCertificatePdfFacade,
        PromoCodeDataFactory $promoCodeDataFactory
    ) {
        $this->em = $em;
        $this->orderGiftCertificateMailFacade = $orderGiftCertificateMailFacade;
        $this->orderGiftCertificateFactory = $orderGiftCertificateFactory;
        $this->orderGiftCertificatePdfFacade = $orderGiftCertificatePdfFacade;
        $this->promoCodeDataFactory = $promoCodeDataFactory;
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
        $this->orderGiftCertificateMailFacade->sendGiftCertificateEmail($orderGiftCertificate, OrderGiftCertificateMail::MAIL_TEMPLATE_DEFAULT_NAME);
        $this->orderGiftCertificatePdfFacade->delete($orderGiftCertificate);

        return $orderGiftCertificate;
    }

    /**
     * @param \App\Model\Order\GiftCertificate\OrderGiftCertificate[] $orderGiftCertificates
     */
    public function activate(array $orderGiftCertificates): void
    {
        foreach ($orderGiftCertificates as $orderGiftCertificate) {
            $promoCodeData = $this->promoCodeDataFactory->createFromPromoCode($orderGiftCertificate->getGiftCertificate());
            $promoCodeData->usageLimit = 1;
            $promoCodeData->validTo = new DateTime('+365 days');
            $orderGiftCertificate->getGiftCertificate()->edit($promoCodeData);
            $this->orderGiftCertificatePdfFacade->create($orderGiftCertificate);
            $this->orderGiftCertificateMailFacade->sendGiftCertificateEmail($orderGiftCertificate, OrderGiftCertificateMail::MAIL_TEMPLATE_ACTIVATED_NAME);
            $this->orderGiftCertificatePdfFacade->delete($orderGiftCertificate);
        }

        $this->em->flush();
    }
}
