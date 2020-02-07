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
 * @property \Shopsys\ShopBundle\Model\Product\Product $product
 * @method __construct(\Shopsys\ShopBundle\Model\Product\Product $product, int $domainId)
 */
class ProductDomain extends BaseProductDomain
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Money\Money|null
     *
     * @ORM\Column(type="money", precision=20, scale=6, nullable=true)
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
    public function setActionPrice(?Money $actionPrice): void
    {
        $this->actionPrice = $actionPrice;
    }
}
