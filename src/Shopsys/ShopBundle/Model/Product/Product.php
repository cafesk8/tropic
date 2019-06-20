<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceRecalculationScheduler;
use Shopsys\FrameworkBundle\Model\Product\Product as BaseProduct;
use Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomainFactoryInterface;
use Shopsys\FrameworkBundle\Model\Product\ProductData as BaseProductData;
use Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroup;
use Shopsys\ShopBundle\Model\Product\StoreStock\ProductStoreStock;

/**
 * @ORM\Table(name="products")
 * @ORM\Entity
 */
class Product extends BaseProduct
{
    /**
     * @var \Doctrine\Common\Collections\ArrayCollection|\Shopsys\ShopBundle\Model\Product\StoreStock\ProductStoreStock[]
     *
     * @ORM\OneToMany(
     *   targetEntity="Shopsys\ShopBundle\Model\Product\StoreStock\ProductStoreStock",
     *   mappedBy="product",
     *   orphanRemoval=true,
     *   cascade={"persist", "remove"}
     * )
     */
    protected $storeStocks;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true, unique=true)
     */
    private $transferNumber;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroup|null
     *
     * @ORM\ManyToOne(targetEntity="\Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroup", inversedBy="products", cascade={"persist"})
     * @ORM\JoinColumn(name="maint_variant_group_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $mainVariantGroup;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter|null
     *
     * @ORM\ManyToOne(targetEntity="\Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter")
     * @ORM\JoinColumn(nullable=true)
     */
    protected $distinguishingParameter;

    /**
     * @param \Shopsys\ShopBundle\Model\Product\ProductData $productData
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomainFactoryInterface $productCategoryDomainFactory
     * @param \Shopsys\ShopBundle\Model\Product\Product[]|null $variants
     */
    protected function __construct(BaseProductData $productData, ProductCategoryDomainFactoryInterface $productCategoryDomainFactory, array $variants = null)
    {
        parent::__construct($productData, $productCategoryDomainFactory, $variants);

        $this->storeStocks = new ArrayCollection();
        $this->transferNumber = $productData->transferNumber;
        $this->distinguishingParameter = $productData->distinguishingParameter;
        $this->mainVariantGroup = $productData->mainVariantGroup;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomainFactoryInterface $productCategoryDomainFactory
     * @param \Shopsys\ShopBundle\Model\Product\ProductData $productData
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceRecalculationScheduler $productPriceRecalculationScheduler
     */
    public function edit(
        ProductCategoryDomainFactoryInterface $productCategoryDomainFactory,
        BaseProductData $productData,
        ProductPriceRecalculationScheduler $productPriceRecalculationScheduler
    ) {
        parent::edit($productCategoryDomainFactory, $productData, $productPriceRecalculationScheduler);

        $this->distinguishingParameter = $productData->distinguishingParameter;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroup|null
     */
    public function getMainVariantGroup(): ?MainVariantGroup
    {
        return $this->mainVariantGroup;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter|null
     */
    public function getDistinguishingParameter(): ?Parameter
    {
        if ($this->isVariant() === true && $this->isMainVariant() === false) {
            /** @var \Shopsys\ShopBundle\Model\Product\Product $mainVariant */
            $mainVariant = $this->getMainVariant();
            return $mainVariant->getDistinguishingParameter();
        }

        return $this->distinguishingParameter;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection|\Shopsys\ShopBundle\Model\Product\StoreStock\ProductStoreStock[]
     */
    public function getStoreStocks()
    {
        return $this->storeStocks;
    }

    public function clearStoreStocks():void
    {
        $this->storeStocks->clear();
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\StoreStock\ProductStoreStock $storeStock
     */
    public function addStoreStock(ProductStoreStock $storeStock): void
    {
        $this->storeStocks->add($storeStock);
    }

    /**
     * @return int|null
     */
    public function getTransferNumber(): ?int
    {
        return $this->transferNumber;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter $parameter
     */
    public function setDistinguishingParameter(Parameter $parameter): void
    {
        $this->distinguishingParameter = $parameter;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroup|null $mainVariantGroup
     */
    public function setMainVariantGroup(?MainVariantGroup $mainVariantGroup): void
    {
        $this->mainVariantGroup = $mainVariantGroup;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Product\Product
     */
    public function getProductForCreatingImageAccordingToVariant(): self
    {
        if ($this->isVariant() === true) {
            return $this->getMainVariant();
        }

        return $this;
    }
}
