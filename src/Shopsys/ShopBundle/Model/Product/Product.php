<?php

namespace Shopsys\ShopBundle\Model\Product;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceRecalculationScheduler;
use Shopsys\FrameworkBundle\Model\Product\Product as BaseProduct;
use Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomainFactoryInterface;
use Shopsys\FrameworkBundle\Model\Product\ProductData as BaseProductData;
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
     * @param \Shopsys\ShopBundle\Model\Product\ProductData $productData
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomainFactoryInterface $productCategoryDomainFactory
     * @param \Shopsys\ShopBundle\Model\Product\Product[]|null $variants
     */
    protected function __construct(BaseProductData $productData, ProductCategoryDomainFactoryInterface $productCategoryDomainFactory, array $variants = null)
    {
        parent::__construct($productData, $productCategoryDomainFactory, $variants);

        $this->storeStocks = new ArrayCollection();
        $this->transferNumber = $productData->transferNumber;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomainFactoryInterface $productCategoryDomainFactory
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductData $productData
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceRecalculationScheduler $productPriceRecalculationScheduler
     */
    public function edit(
        ProductCategoryDomainFactoryInterface $productCategoryDomainFactory,
        BaseProductData $productData,
        ProductPriceRecalculationScheduler $productPriceRecalculationScheduler
    ) {
        parent::edit($productCategoryDomainFactory, $productData, $productPriceRecalculationScheduler);
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
}
