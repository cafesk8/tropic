<?php

declare(strict_types=1);

namespace App\Model\Order\GiftCertificate;

use App\Model\Order\Order;
use App\Model\Order\PromoCode\PromoCode;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="order_gift_certificates")
 */
class OrderGiftCertificate
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \App\Model\Order\Order
     *
     * @ORM\ManyToOne(targetEntity="App\Model\Order\Order", inversedBy="giftCertificates")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $order;

    /**
     * @var \App\Model\Order\PromoCode\PromoCode
     *
     * @ORM\ManyToOne(targetEntity="App\Model\Order\PromoCode\PromoCode")
     * @ORM\JoinColumn(name="gift_certificate_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $giftCertificate;

    /**
     * @param \App\Model\Order\Order $order
     * @param \App\Model\Order\PromoCode\PromoCode $giftCertificate
     */
    public function __construct(Order $order, PromoCode $giftCertificate)
    {
        $this->order = $order;
        $this->giftCertificate = $giftCertificate;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return \App\Model\Order\Order
     */
    public function getOrder(): Order
    {
        return $this->order;
    }

    /**
     * @return \App\Model\Order\PromoCode\PromoCode
     */
    public function getGiftCertificate(): PromoCode
    {
        return $this->giftCertificate;
    }
}
