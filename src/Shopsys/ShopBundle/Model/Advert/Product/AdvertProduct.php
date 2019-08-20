<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Advert\Product;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\ShopBundle\Model\Advert\Advert;
use Shopsys\ShopBundle\Model\Product\Product;

/**
 * @ORM\Entity
 * @ORM\Table(name="advert_products")
 */
class AdvertProduct
{
    /**
     * @var \Shopsys\ShopBundle\Model\Advert\Advert
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Shopsys\ShopBundle\Model\Advert\Advert")
     * @ORM\JoinColumn(nullable=false, name="advert_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $advert;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Product
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Shopsys\ShopBundle\Model\Product\Product")
     * @ORM\JoinColumn(nullable=false, name="advert_product_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $product;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $position;

    /**
     * @param \Shopsys\ShopBundle\Model\Advert\Advert $advert
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param int $position
     */
    public function __construct(Advert $advert, Product $product, int $position)
    {
        $this->advert = $advert;
        $this->product = $product;
        $this->position = $position;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Advert\Advert
     */
    public function getAdvert(): Advert
    {
        return $this->advert;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Product\Product
     */
    public function getProduct(): Product
    {
        return $this->product;
    }

    /**
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position;
    }
}
