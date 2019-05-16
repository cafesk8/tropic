<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\MainVariantGroup;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter;

/**
 * @ORM\Table(
 *     name="product_main_variant_groups",
 * )
 * @ORM\Entity
 */
class MainVariantGroup
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
     * @var \Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter|null
     *
     * @ORM\ManyToOne(targetEntity="\Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter")
     * @ORM\JoinColumn(nullable=true)
     */
    private $distinguishingParameter;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Product[]|\Doctrine\Common\Collections\ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Shopsys\ShopBundle\Model\Product\Product", mappedBy="mainVariantGroup")
     */
    private $products;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter $distinguishingParameter
     */
    public function __construct(Parameter $distinguishingParameter)
    {
        $this->distinguishingParameter = $distinguishingParameter;
        $this->products = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter|null
     */
    public function getDistinguishingParameter(): ?Parameter
    {
        return $this->distinguishingParameter;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Product\Product[]
     */
    public function getProducts(): array
    {
        return $this->products->toArray();
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product[] $products
     */
    public function addProducts(array $products)
    {
        $this->clearAllProducts();

        foreach ($products as $product) {
            $this->products->add($product);
            $product->setMainVariantGroup($this);
        }
    }

    private function clearAllProducts(): void
    {
        foreach ($this->products as $product) {
            $product->setMainVariantGroup(null);
        }

        $this->products->clear();
    }
}
