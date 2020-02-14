<?php

declare(strict_types=1);

namespace App\Model\Product\MainVariantGroup;

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
     * @var \App\Model\Product\Parameter\Parameter|null
     *
     * @ORM\ManyToOne(targetEntity="\App\Model\Product\Parameter\Parameter")
     * @ORM\JoinColumn(nullable=true)
     */
    private $distinguishingParameter;

    /**
     * @var \App\Model\Product\Product[]|\Doctrine\Common\Collections\ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="App\Model\Product\Product", mappedBy="mainVariantGroup")
     */
    private $products;

    /**
     * @param \App\Model\Product\Parameter\Parameter $distinguishingParameter
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
     * @return \App\Model\Product\Parameter\Parameter|null
     */
    public function getDistinguishingParameter(): ?Parameter
    {
        return $this->distinguishingParameter;
    }

    /**
     * @param \App\Model\Product\Parameter\Parameter|null $distinguishingParameter
     */
    public function setDistinguishingParameter(?Parameter $distinguishingParameter): void
    {
        $this->distinguishingParameter = $distinguishingParameter;
    }

    /**
     * @return \App\Model\Product\Product[]
     */
    public function getProducts(): array
    {
        return $this->products->toArray();
    }

    /**
     * @param \App\Model\Product\Product[] $products
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
