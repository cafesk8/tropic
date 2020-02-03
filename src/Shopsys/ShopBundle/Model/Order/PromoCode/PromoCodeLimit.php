<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\PromoCode;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="promo_code_limits",
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="promo_code_limit_unique",
 *              columns={"promo_code_id", "object_id", "type"}
 *          )
 *     }
 * )
 * @ORM\Entity
 */
class PromoCodeLimit
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode
     *
     * @ORM\ManyToOne(targetEntity="Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode", inversedBy="limits")
     * @ORM\JoinColumn(name="promo_code_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    private $promoCode;

    /**
     * @var int
     *
     * @ORM\Column(name="object_id", nullable=false, type="integer")
     */
    private $objectId;

    /**
     * @var string
     *
     * @ORM\Column(name="type", nullable=false, type="string", length=50)
     */
    private $type;

    /**
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeLimitData $promoCodeLimitData
     */
    public function __construct(PromoCodeLimitData $promoCodeLimitData)
    {
        $this->promoCode = $promoCodeLimitData->promoCode;
        $this->objectId = $promoCodeLimitData->objectId;
        $this->type = $promoCodeLimitData->type;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode
     */
    public function getPromoCode(): PromoCode
    {
        return $this->promoCode;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode $promoCode
     */
    public function setPromoCode(PromoCode $promoCode): void
    {
        $this->promoCode = $promoCode;
    }

    /**
     * @return int
     */
    public function getObjectId(): int
    {
        return $this->objectId;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
}
