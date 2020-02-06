<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter;
use Shopsys\FrameworkBundle\Model\Product\Product as BaseProduct;
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
    public const DECREASE_REAL_STOCK_QUANTITY_BY = 2;
    public const PRODUCT_TYPE_GIFT_CERTIFICATE_500 = 'gift_certificate_500';
    public const PRODUCT_TYPE_GIFT_CERTIFICATE_1000 = 'gift_certificate_1000';
    public const PRODUCT_TYPES_GIFT_CERTIFICATES = [
        self::PRODUCT_TYPE_GIFT_CERTIFICATE_500,
        self::PRODUCT_TYPE_GIFT_CERTIFICATE_1000,
    ];

    /**
     * @var \Shopsys\ShopBundle\Model\Product\StoreStock\ProductStoreStock[]|\Doctrine\Common\Collections\Collection
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
     * @var \Shopsys\FrameworkBundle\Model\Product\Flag\Flag[]|\Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Shopsys\ShopBundle\Model\Product\Flag\Flag")
     * @ORM\JoinTable(name="product_flags")
     * @ORM\OrderBy({"position" = "ASC"})
     */
    protected $flags;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductGift\ProductGift[]|\Doctrine\Common\Collections\Collection
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
     * @var string[]
     *
     * @ORM\Column(type="json", nullable=false)
     */
    protected $youtubeVideoIds;

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
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $productType;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $minimumAmount;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $amountMultiplier;

    /**
     * @param \Shopsys\ShopBundle\Model\Product\ProductData $productData
     * @param \Shopsys\ShopBundle\Model\Product\Product[]|null $variants
     */
    protected function __construct(ProductData $productData, ?array $variants = null)
    {
        parent::__construct($productData, $variants);

        $this->storeStocks = new ArrayCollection();
        $this->transferNumber = $productData->transferNumber;
        $this->distinguishingParameter = $productData->distinguishingParameter;
        $this->mainVariantGroup = $productData->mainVariantGroup;
        $this->generateToHsSportXmlFeed = $productData->generateToHsSportXmlFeed;
        $this->finished = $productData->finished;
        $this->mallExport = $productData->mallExport;
        $this->mallExportedAt = $productData->mallExportedAt;
        $this->updatedAt = $productData->updatedAt;
        $this->baseName = $productData->baseName;
        $this->productType = $productData->productType;
        $this->minimumAmount = $productData->minimumAmount;
        $this->amountMultiplier = $productData->amountMultiplier;
        $this->youtubeVideoIds = $productData->youtubeVideoIds;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomain[] $productCategoryDomains
     * @param \Shopsys\ShopBundle\Model\Product\ProductData $productData
     */
    public function edit(
        array $productCategoryDomains,
        ProductData $productData
    ) {
        parent::edit($productCategoryDomains, $productData);

        $this->distinguishingParameter = $productData->distinguishingParameter;
        $this->generateToHsSportXmlFeed = $productData->generateToHsSportXmlFeed;
        $this->finished = $productData->finished;
        $this->mallExport = $productData->mallExport;
        $this->mallExportedAt = $productData->mallExportedAt;
        $this->baseName = $productData->baseName;
        $this->productType = $productData->productType;
        $this->minimumAmount = $productData->minimumAmount;
        $this->amountMultiplier = $productData->amountMultiplier;
        $this->youtubeVideoIds = $productData->youtubeVideoIds;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomain[] $productCategoryDomains
     */
    public function editCategoriesByDomainId(
        array $productCategoryDomains
    ): void {
        if (!$this->isVariant()) {
            $this->setProductCategoryDomains($productCategoryDomains);
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
     * @return string[]
     */
    public function getYoutubeVideoIds(): ?array
    {
        return $this->youtubeVideoIds;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\ProductData $productData
     */
    protected function createDomains(ProductData $productData)
    {
        $domainIds = array_keys($productData->seoTitles);

        foreach ($domainIds as $domainId) {
            $productDomain = new ProductDomain($this, $domainId);
            $this->domains->add($productDomain);
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
     * @return \Shopsys\ShopBundle\Model\Product\StoreStock\ProductStoreStock[]
     */
    public function getStoreStocks(): array
    {
        return $this->storeStocks->toArray();
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
     * @return \Shopsys\FrameworkBundle\Model\Product\Flag\Flag[]
     */
    public function getFlags(?int $limit = null)
    {
        if ($limit !== null) {
            return $this->flags->slice(0, $limit);
        }

        return $this->flags->toArray();
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

    /**
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Model\Product\Product[]
     */
    public function getGifts(int $domainId): array
    {
        $gifts = [];
        $activeProductGiftsByDomainId = $this->getActiveProductGiftsByDomainId($domainId);
        foreach ($activeProductGiftsByDomainId as $activeProductGift) {
            $gifts[] = $activeProductGift->getGift();
        }

        return $gifts;
    }

    /**
     * @param int $domainId
     * @return string|null
     */
    public function getShortDescriptionConsideringVariant(int $domainId): ?string
    {
        if ($this->isVariant()) {
            return $this->getMainVariant()->getShortDescription($domainId);
        }

        return $this->getShortDescription($domainId);
    }

    /**
     * @param int $domainId
     * @return string|null
     */
    public function getDescriptionConsideringVariant(int $domainId): ?string
    {
        if ($this->isVariant()) {
            return $this->getMainVariant()->getDescription($domainId);
        }

        return $this->getDescription($domainId);
    }

    /**
     * @return string|null
     */
    public function getProductType(): ?string
    {
        return $this->productType;
    }

    /**
     * @return bool
     */
    public function isProductTypeGiftCertificate(): bool
    {
        return in_array($this->productType, self::PRODUCT_TYPES_GIFT_CERTIFICATES, true);
    }

    /**
     * @return int
     */
    public function getAmountMultiplier(): int
    {
        if ($this->isVariant()) {
            return $this->getMainVariant()->getAmountMultiplier();
        }

        return $this->amountMultiplier;
    }

    /**
     * @return int
     */
    public function getMinimumAmount(): int
    {
        if ($this->isVariant()) {
            return $this->getMainVariant()->getMinimumAmount();
        }

        return $this->minimumAmount;
    }

    /**
     * @return int
     */
    public function getRealMinimumAmount(): int
    {
        if ($this->getAmountMultiplier() > $this->getMinimumAmount()) {
            return $this->getAmountMultiplier();
        } elseif ($this->getMinimumAmount() % $this->getAmountMultiplier() !== 0) {
            return (int)ceil($this->getMinimumAmount() / $this->getAmountMultiplier()) * $this->getAmountMultiplier();
        }

        return $this->getMinimumAmount();
    }

    /**
     * @return int
     */
    public function getRealStockQuantity(): int
    {
        if (!$this->isUsingStock()) {
            return PHP_INT_MAX;
        } elseif ($this->getStockQuantity() % $this->getAmountMultiplier() !== 0) {
            return (int)floor($this->getStockQuantity() / $this->getAmountMultiplier()) * $this->getAmountMultiplier();
        }

        return $this->getStockQuantity();
    }
}
