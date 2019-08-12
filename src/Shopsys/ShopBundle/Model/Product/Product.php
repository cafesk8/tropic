<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceRecalculationScheduler;
use Shopsys\FrameworkBundle\Model\Product\Product as BaseProduct;
use Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomainFactoryInterface;
use Shopsys\FrameworkBundle\Model\Product\ProductData;
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
     * @var string|null
     *
     * @ORM\Column(type="string", length=20, nullable=true, unique=true)
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
     * @var \Shopsys\ShopBundle\Model\Product\ProductDomain[]|\Doctrine\Common\Collections\ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Shopsys\ShopBundle\Model\Product\ProductDomain", mappedBy="product", cascade={"persist"}, fetch="EXTRA_LAZY")
     */
    protected $domains;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection|\Shopsys\FrameworkBundle\Model\Product\Flag\Flag[]
     *
     * @ORM\ManyToMany(targetEntity="Shopsys\ShopBundle\Model\Product\Flag\Flag")
     * @ORM\JoinTable(name="product_flags")
     * @ORM\OrderBy({"position" = "ASC"})
     */
    protected $flags;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Product|null
     *
     * @ORM\ManyToOne(targetEntity="Shopsys\FrameworkBundle\Model\Product\Product")
     * @ORM\JoinColumn(name="gift_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $gift;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    protected $generateToHsSportXmlFeed;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $finished;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $youtubeVideoId;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    protected $mallExport;

    /**
     * @param \Shopsys\ShopBundle\Model\Product\ProductData $productData
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomainFactoryInterface $productCategoryDomainFactory
     * @param \Shopsys\ShopBundle\Model\Product\Product[]|null $variants
     */
    protected function __construct(ProductData $productData, ProductCategoryDomainFactoryInterface $productCategoryDomainFactory, ?array $variants = null)
    {
        parent::__construct($productData, $productCategoryDomainFactory, $variants);

        $this->storeStocks = new ArrayCollection();
        $this->transferNumber = $productData->transferNumber;
        $this->distinguishingParameter = $productData->distinguishingParameter;
        $this->mainVariantGroup = $productData->mainVariantGroup;
        $this->gift = $productData->gift;
        $this->generateToHsSportXmlFeed = $productData->generateToHsSportXmlFeed;
        $this->finished = $productData->finished;
        $this->youtubeVideoId = $productData->youtubeVideoId;
        $this->mallExport = $productData->mallExport;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomainFactoryInterface $productCategoryDomainFactory
     * @param \Shopsys\ShopBundle\Model\Product\ProductData $productData
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceRecalculationScheduler $productPriceRecalculationScheduler
     */
    public function edit(
        ProductCategoryDomainFactoryInterface $productCategoryDomainFactory,
        ProductData $productData,
        ProductPriceRecalculationScheduler $productPriceRecalculationScheduler
    ) {
        parent::edit($productCategoryDomainFactory, $productData, $productPriceRecalculationScheduler);

        $this->distinguishingParameter = $productData->distinguishingParameter;
        $this->gift = $productData->gift;
        $this->generateToHsSportXmlFeed = $productData->generateToHsSportXmlFeed;
        $this->finished = $productData->finished;
        $this->youtubeVideoId = $productData->youtubeVideoId;
        $this->mallExport = $productData->mallExport;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\ProductData $productData
     */
    protected function createDomains(ProductData $productData)
    {
        $domainIds = array_keys($productData->seoTitles);

        foreach ($domainIds as $domainId) {
            $productDomain = new ProductDomain($this, $domainId);
            $this->domains[] = $productDomain;
        }

        $this->setDomains($productData);
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

    public function clearStoreStocks(): void
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
     * @return string|null
     */
    public function getTransferNumber(): ?string
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

    /**
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    public function getActionPrice(int $domainId): ?Money
    {
        /** @var \Shopsys\ShopBundle\Model\Product\ProductDomain $productDomain */
        $productDomain = $this->getProductDomain($domainId);
        return $productDomain->getActionPrice();
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $actionPrice
     * @param int $domainId
     */
    public function setActionPrice(?Money $actionPrice, int $domainId): void
    {
        /** @var \Shopsys\ShopBundle\Model\Product\ProductDomain $productDomain */
        $productDomain = $this->getProductDomain($domainId);
        $productDomain->setActionPrice($actionPrice);
    }

    /**
     * @param int|null $limit
     * @return \Doctrine\Common\Collections\ArrayCollection|\Shopsys\FrameworkBundle\Model\Product\Flag\Flag[]
     */
    public function getFlags(?int $limit = null)
    {
        if ($limit !== null) {
            return new ArrayCollection($this->flags->slice(0, $limit));
        }

        return $this->flags;
    }

    /**
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomain[]
     */
    public function getProductCategoriesByDomainId(int $domainId): array
    {
        $productCategories = [];

        foreach ($this->getProductCategoryDomainsByDomainIdIndexedByCategoryId($domainId) as $categoryDomain) {
            $category = $categoryDomain->getCategory();
            if ($category->isVisible($domainId)) {
                $productCategories[$category->getId()] = $category;
            }
        }

        return $productCategories;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\ProductData $productData
     */
    protected function setDomains(ProductData $productData): void
    {
        parent::setDomains($productData);

        /** @var \Shopsys\ShopBundle\Model\Product\ProductDomain $productDomain */
        foreach ($this->domains as $productDomain) {
            $domainId = $productDomain->getDomainId();
            $productDomain->setActionPrice($productData->actionPrices[$domainId]);
        }
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Product\StoreStock\ProductStoreStock[]
     */
    public function getStocksWithoutZeroQuantityOnStore(): array
    {
        return array_filter(
            $this->storeStocks->toArray(),
            function (ProductStoreStock $productStoreStock) {
                return $productStoreStock->getStockQuantity() > 0;
            }
        );
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Product\Product|null
     */
    public function getGift(): ?self
    {
        return $this->gift;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Product\Product
     */
    public function getMainVariant(): BaseProduct
    {
        if (!$this->isVariant()) {
            throw new \Shopsys\FrameworkBundle\Model\Product\Exception\ProductIsNotVariantException();
        }

        return $this->mainVariant;
    }

    /**
     * @return bool
     */
    public function isGenerateToHsSportXmlFeed(): bool
    {
        return $this->generateToHsSportXmlFeed;
    }

    /**
     * @return bool
     */
    public function isFinished(): bool
    {
        return $this->finished;
    }

    /**
     * @return string|null
     */
    public function getYoutubeVideoId(): ?string
    {
        return $this->youtubeVideoId;
    }

    /**
     * @return bool
     */
    public function isMallExport(): bool
    {
        return $this->mallExport;
    }
}
