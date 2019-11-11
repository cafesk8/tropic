<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceRecalculationScheduler;
use Shopsys\FrameworkBundle\Model\Product\Product as BaseProduct;
use Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomainFactoryInterface;
use Shopsys\FrameworkBundle\Model\Product\ProductData;
use Shopsys\ShopBundle\Component\Domain\DomainHelper;
use Shopsys\ShopBundle\Model\Product\Exception\ProductIsNotMainVariantException;
use Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroup;
use Shopsys\ShopBundle\Model\Product\Mall\ProductMallExportMapper;
use Shopsys\ShopBundle\Model\Product\StoreStock\ProductStoreStock;

/**
 * @ORM\Table(name="products")
 * @ORM\Entity
 */
class Product extends BaseProduct
{
    public const DECREASE_REAL_STOCK_QUANTITY_BY = 5;

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
     * @var \Doctrine\Common\Collections\ArrayCollection|\Shopsys\ShopBundle\Model\Product\ProductGift\ProductGift[]
     *
     * @ORM\ManyToMany(targetEntity="Shopsys\ShopBundle\Model\Product\ProductGift\ProductGift", mappedBy="products", cascade={"persist"}, fetch="EXTRA_LAZY")
     */
    protected $productGifts;

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
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $mallExportedAt;

    /**
     * @var \DateTime
     *
     * @Gedmo\Mapping\Annotation\Timestampable(on="update")
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $updatedAt;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $baseName;

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
        $this->mallExportedAt = $productData->mallExportedAt;
        $this->updatedAt = $productData->updatedAt;
        $this->baseName = $productData->baseName;
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
        $this->mallExportedAt = $productData->mallExportedAt;
        $this->baseName = $productData->baseName;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomainFactoryInterface $productCategoryDomainFactory
     * @param \Shopsys\ShopBundle\Model\Category\Category[][] $categoriesByDomainId
     */
    public function editCategoriesByDomainId(
        ProductCategoryDomainFactoryInterface $productCategoryDomainFactory,
        array $categoriesByDomainId
    ): void {
        if (!$this->isVariant()) {
            $this->setCategories($productCategoryDomainFactory, $categoriesByDomainId);
        }
    }

    /**
     * @inheritDoc
     */
    public function editFlags(array $flags): void
    {
        parent::editFlags($flags);
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
     * @return \Shopsys\ShopBundle\Model\Product\Parameter\Parameter|null
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
     * @param int $limit
     * @return \Shopsys\FrameworkBundle\Model\Product\Flag\Flag[]
     */
    public function getFlagsIndexedByPosition(int $limit): array
    {
        $flagsIndexedByPosition = [];
        foreach ($this->getFlags($limit) as $flag) {
            $flagsIndexedByPosition[$flag->getPosition()] = $flag;
        }

        return $flagsIndexedByPosition;
    }

    /**
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomain[]
     */
    public function getListableProductCategoriesByDomainId(int $domainId): array
    {
        $productCategories = [];

        foreach ($this->getProductCategoryDomainsByDomainIdIndexedByCategoryId($domainId) as $categoryDomain) {
            /** @var \Shopsys\ShopBundle\Model\Category\Category $category */
            $category = $categoryDomain->getCategory();
            if ($category->isVisible($domainId) && $category->isListable()) {
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
                return $productStoreStock->getStockQuantity() > 0 && $productStoreStock->getStore()->isFranchisor() === false;
            }
        );
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @return \Shopsys\ShopBundle\Model\Product\StoreStock\ProductStoreStock[]
     */
    public function getStocksWithoutZeroQuantityOnPickupPlaceStore(Domain $domain): array
    {
        $productStoreStocks = array_filter(
            $this->storeStocks->toArray(),
            function (ProductStoreStock $productStoreStock) use ($domain) {
                return $productStoreStock->getStockQuantity() > 0
                    && $productStoreStock->getStore()->isPickupPlace() === true
                    && $productStoreStock->getStore()->getDomainId() === $domain->getId();
            }
        );

        usort($productStoreStocks, function (ProductStoreStock $productStoreStock1, ProductStoreStock $productStoreStock2) {
            $store1Position = $productStoreStock1->getStore()->getPosition();
            $store2Position = $productStoreStock2->getStore()->getPosition();

            if ($store1Position !== null && $store2Position !== null) {
                return $store1Position <=> $store2Position;
            }

            if ($store1Position !== null && $store2Position === null) {
                return -1;
            }

            if ($store1Position === null && $store2Position !== null) {
                return 1;
            }

            $store1Name = $productStoreStock1->getStore()->getName();
            $store2Name = $productStoreStock2->getStore()->getName();

            return $store1Name <=> $store2Name;
        });

        return $productStoreStocks;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Product\StoreStock\ProductStoreStock[]
     */
    public function getStocksWithoutZeroQuantityOnCentralStore(): array
    {
        return array_filter(
            $this->storeStocks->toArray(),
            function (ProductStoreStock $productStoreStock) {
                return $productStoreStock->getStockQuantity() > 0
                    && $productStoreStock->getStore()->isCentralStore();
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

    /**
     * @return \DateTime|null
     */
    public function getMallExportedAt(): ?DateTime
    {
        return $this->mallExportedAt;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function markProductAsExportedToMall(): void
    {
        $this->mallExportedAt = new DateTime();
    }

    /**
     * @param int $stockQuantity
     */
    public function setStockQuantity(int $stockQuantity): void
    {
        $this->stockQuantity = $stockQuantity;
    }

    /**
     * @return int
     */
    public function getTotalStockQuantityOfProductVariants(): int
    {
        if ($this->isMainVariant() === false) {
            throw new ProductIsNotMainVariantException($this->getId());
        }

        $mainVariantTotalStockQuantity = 0;

        foreach ($this->variants as $variant) {
            $mainVariantTotalStockQuantity += $variant->getStockQuantity();
        }

        return $mainVariantTotalStockQuantity;
    }

    /**
     * @return int
     */
    public function getTotalStockQuantityOfProductVariantsForMall(): int
    {
        $totalStockQuantityOfProductVariants = $this->getTotalStockQuantityOfProductVariants();

        $totalStockQuantityOfProductVariants -= count($this->getVariants()) * ProductMallExportMapper::STOCK_QUANTITY_FUSE;

        if ($totalStockQuantityOfProductVariants < 0) {
            return 0;
        }

        return $totalStockQuantityOfProductVariants;
    }

    /**
     * @return bool
     */
    public function isNoneVariant(): bool
    {
        return $this->variantType === self::VARIANT_TYPE_NONE;
    }

    public function setProductAsHidden(): void
    {
        $this->hidden = true;
        $this->markForVisibilityRecalculation();
    }

    /**
     * @return string
     */
    public function getVariantType(): string
    {
        return $this->variantType;
    }

    /**
     * @return string|null
     */
    public function getBaseName(): ?string
    {
        return $this->baseName;
    }

    /**
     * @param string $color
     */
    public function updateCzechNamesWithColor(string $color): void
    {
        $newName = sprintf('%s %s', $this->getBaseName(), $color);
        $this->setName(DomainHelper::CZECH_LOCALE, $newName);
    }

    /**
     * @param string $locale
     * @param string $baseName
     * @param string $size
     * @param string $color
     */
    public function updateNameWithSize(string $locale, string $baseName, string $size): void
    {
        $nameWithSize = sprintf('%s %s', $baseName, $size);
        $this->setName($locale, $nameWithSize);
    }

    /**
     * @param string $locale
     * @param string $name
     */
    private function setName(string $locale, string $name): void
    {
        $this->translation($locale)->setName($name);
    }

    /**
     * @param int $domainId
     * @return \Shopsys\ShopBundle\Model\Product\ProductGift\ProductGift[]
     */
    public function getActiveProductGiftsByDomainId(int $domainId): array
    {
        $productGifts = [];

        foreach ($this->productGifts as $productGift) {
            if ($productGift->getDomainId() === $domainId && $productGift->isActive() === true) {
                $productGifts[] = $productGift;
            }
        }

        return $productGifts;
    }
}
