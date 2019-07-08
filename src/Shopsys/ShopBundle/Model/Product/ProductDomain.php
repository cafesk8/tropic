<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Product\ProductDomain as BaseProductDomain;

/**
 * @ORM\Table(
 *     name="product_domains",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="product_domain", columns={"product_id", "domain_id"})
 *     }
 * )
 *
 * @ORM\Entity
 */
class ProductDomain extends BaseProductDomain
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Money\Money|null
     *
     * @ORM\Column(type="money", nullable=true)
     */
    private $actionPrice;

    /**
     * @return \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    public function getActionPrice(): ?Money
    {
        return $this->actionPrice;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $actionPrice
     */
    public function setActionPrice(Money $actionPrice): void
    {
        $this->actionPrice = $actionPrice;
    }
}
