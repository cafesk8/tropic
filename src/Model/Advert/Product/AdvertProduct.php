<?php

declare(strict_types=1);

namespace App\Model\Advert\Product;

use Doctrine\ORM\Mapping as ORM;
use App\Model\Advert\Advert;
use App\Model\Product\Product;

/**
 * @ORM\Entity
 * @ORM\Table(name="advert_products")
 */
class AdvertProduct
{
    /**
     * @var \App\Model\Advert\Advert
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\Model\Advert\Advert")
     * @ORM\JoinColumn(nullable=false, name="advert_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $advert;

    /**
     * @var \App\Model\Product\Product
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\Model\Product\Product")
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
     * @param \App\Model\Advert\Advert $advert
     * @param \App\Model\Product\Product $product
     * @param int $position
     */
    public function __construct(Advert $advert, Product $product, int $position)
    {
        $this->advert = $advert;
        $this->product = $product;
        $this->position = $position;
    }

    /**
     * @return \App\Model\Advert\Advert
     */
    public function getAdvert(): Advert
    {
        return $this->advert;
    }

    /**
     * @return \App\Model\Product\Product
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
