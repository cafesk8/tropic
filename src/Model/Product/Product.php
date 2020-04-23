<?php

declare(strict_types=1);

namespace App\Model\Product;

use App\Component\Domain\DomainHelper;
use App\Model\Product\Exception\ProductIsNotMainVariantException;
use App\Model\Product\Mall\ProductMallExportMapper;
use App\Model\Product\StoreStock\ProductStoreStock;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Product\Product as BaseProduct;
use Shopsys\FrameworkBundle\Model\Product\ProductData as BaseProductData;

/**
 * @ORM\Table(name="products")
 * @ORM\Entity
 * @property \App\Model\Product\Brand\Brand|null $brand
 * @property \App\Model\Product\Product[]|\Doctrine\Common\Collections\Collection $variants
 * @property \App\Model\Product\Product|null $mainVariant
 * @method static \App\Model\Product\Product create(\App\Model\Product\ProductData $productData)
 * @method setAvailabilityAndStock(\App\Model\Product\ProductData $productData)
 * @method \App\Model\Product\Availability\Availability|null getAvailability()
 * @method \App\Model\Product\Availability\Availability|null getOutOfStockAvailability()
 * @method \App\Model\Product\Availability\Availability getCalculatedAvailability()
 * @method setAvailability(\App\Model\Product\Availability\Availability $availability)
 * @method setOutOfStockAvailability(\App\Model\Product\Availability\Availability|null $outOfStockAvailability)
 * @method setCalculatedAvailability(\App\Model\Product\Availability\Availability $calculatedAvailability)
 * @method \App\Model\Product\Brand\Brand|null getBrand()
 * @method addVariants(\App\Model\Product\Product[] $variants)
 * @method setMainVariant(\App\Model\Product\Product $mainVariant)
 * @method refreshVariants(\App\Model\Product\Product[] $currentVariants)
 * @method addNewVariants(\App\Model\Product\Product[] $currentVariants)
 * @method unsetRemovedVariants(\App\Model\Product\Product[] $currentVariants)
 * @property \App\Model\Product\Availability\Availability|null $availability
 * @property \App\Model\Product\Availability\Availability|null $outOfStockAvailability
 * @property \App\Model\Product\Availability\Availability $calculatedAvailability
 * @method \App\Model\Category\Category[][] getCategoriesIndexedByDomainId()
 * @method \App\Model\Pricing\Vat\Vat getVatForDomain(int $domainId)
 * @method changeVatForDomain(\App\Model\Pricing\Vat\Vat $vat, int $domainId)
 * @method \App\Model\Product\Product getMainVariant()
 * @property \App\Model\Product\ProductDomain[]|\Doctrine\Common\Collections\Collection $domains
 * @method \App\Model\Product\ProductDomain getProductDomain(int $domainId)
 * @method editFlags(\App\Model\Product\Flag\Flag[] $flags)
 */
class Product extends BaseProduct
{
    public const POHODA_PRODUCT_TYPE_ID_SINGLE_PRODUCT = 1;
    public const POHODA_PRODUCT_TYPE_ID_PRODUCT_GROUP = 5;

    /**
     * @var \App\Model\Product\StoreStock\ProductStoreStock[]|\Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(
     *   targetEntity="App\Model\Product\StoreStock\ProductStoreStock",
     *   mappedBy="product",
     *   orphanRemoval=true,
     *   cascade={"persist", "remove"}
     * )
     */
    private $storeStocks;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true, unique=true)
     */
    private $pohodaId;

    /**
     * @var \App\Model\Product\Flag\Flag[]|\Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="App\Model\Product\Flag\Flag")
     * @ORM\JoinTable(name="product_flags")
     * @ORM\OrderBy({"position" = "ASC"})
     */
    protected $flags;

    /**
     * @var \App\Model\Product\ProductGift\ProductGift[]|\Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="App\Model\Product\ProductGift\ProductGift", mappedBy="products", cascade={"persist"}, fetch="EXTRA_LAZY")
     */
    private $productGifts;

    /**
     * @var string[]
     *
     * @ORM\Column(type="json", nullable=false)
     */
    private $youtubeVideoIds;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $mallExport;

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
    private $baseName;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $giftCertificate;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    private $minimumAmount;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    private $amountMultiplier;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true, unique=true)
     */
    private $variantId;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $registrationDiscountDisabled;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedByPohodaAt;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $pohodaProductType;

    /**
     * @param \App\Model\Product\ProductData $productData
     * @param \App\Model\Product\Product[]|null $variants
     */
    protected function __construct(BaseProductData $productData, ?array $variants = null)
    {
        parent::__construct($productData, $variants);

        $this->storeStocks = new ArrayCollection();
        $this->pohodaId = $productData->pohodaId;
        $this->updatedAt = $productData->updatedAt;
        $this->fillCommonProperties($productData);
    }

    /**
     * @deprecated since US-7741, variants are paired using variantId
     * @see \App\Model\Product\ProductVariantTropicFacade, method refreshVariantStatus
     *
     * @param \App\Model\Product\ProductData $productData
     * @param array $variants
     * @return \App\Model\Product\Product|void
     */
    public static function createMainVariant(BaseProductData $productData, array $variants)
    {
        @trigger_error('Deprecated, you should use Product::variantId to pair variants, see ProductVariantTropicFacade::refreshVariantStatus', E_USER_DEPRECATED);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomain[] $productCategoryDomains
     * @param \App\Model\Product\ProductData $productData
     */
    public function edit(
        array $productCategoryDomains,
        BaseProductData $productData
    ) {
        $this->fillCommonProperties($productData);
        parent::edit($productCategoryDomains, $productData);
    }

    /**
     * @param \App\Model\Product\ProductData $productData
     */
    private function fillCommonProperties(ProductData $productData): void
    {
        $this->catnum = $productData->catnum;
        $this->mallExport = $productData->mallExport;
        $this->mallExportedAt = $productData->mallExportedAt;
        $this->baseName = $productData->baseName;
        $this->giftCertificate = $productData->giftCertificate;
        $this->minimumAmount = $productData->minimumAmount;
        $this->amountMultiplier = (int)$productData->amountMultiplier;
        $this->youtubeVideoIds = $productData->youtubeVideoIds;

        if ($productData->variantId !== null) {
            $this->variantId = trim($productData->variantId);
        } else {
            $this->variantId = null;
        }
        $this->registrationDiscountDisabled = $productData->registrationDiscountDisabled;
        $this->updatedByPohodaAt = $productData->updatedByPohodaAt;
        $this->pohodaProductType = $productData->pohodaProductType;
    }

    /**
     * @param string $variantType
     */
    public function setVariantType(string $variantType): void
    {
        $this->variantType = $variantType;
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
     * @return string[]
     */
    public function getYoutubeVideoIds(): ?array
    {
        return $this->youtubeVideoIds;
    }

    /**
     * @return \App\Model\Product\StoreStock\ProductStoreStock[]
     */
    public function getStoreStocks(): array
    {
        $storeStocks = $this->storeStocks->toArray();
        usort($storeStocks, function (ProductStoreStock $storeStockA, ProductStoreStock $storeStockB) {
            return $storeStockA->getStore()->getPosition() - $storeStockB->getStore()->getPosition();
        });

        return $storeStocks;
    }

    public function clearStoreStocks(): void
    {
        $this->storeStocks->clear();
    }

    /**
     * @param \App\Model\Product\StoreStock\ProductStoreStock $storeStock
     */
    public function addStoreStock(ProductStoreStock $storeStock): void
    {
        $this->storeStocks->add($storeStock);
    }

    /**
     * @return int|null
     */
    public function getPohodaId(): ?int
    {
        return $this->pohodaId;
    }

    /**
     * @return \App\Model\Product\Product
     */
    public function getProductForCreatingImageAccordingToVariant(): self
    {
        if ($this->isVariant() === true) {
            return $this->getMainVariant();
        }

        return $this;
    }

    /**
     * @param int|null $limit
     * @return \App\Model\Product\Flag\Flag[]
     */
    public function getFlags(?int $limit = null)
    {
        return $this->flags->slice(0, $limit);
    }

    /**
     * @param int $limit
     * @return \App\Model\Product\Flag\Flag[]
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
     * @return \App\Model\Category\Category[]
     */
    public function getListableProductCategoriesByDomainId(int $domainId): array
    {
        $productCategories = [];

        foreach ($this->getProductCategoryDomainsByDomainIdIndexedByCategoryId($domainId) as $categoryDomain) {
            /** @var \App\Model\Category\Category $category */
            $category = $categoryDomain->getCategory();
            if ($category->isVisible($domainId) && $category->isListable()) {
                $productCategories[$category->getId()] = $category;
            }
        }

        return $productCategories;
    }

    /**
     * @return \App\Model\Product\StoreStock\ProductStoreStock[]
     */
    public function getStocksWithoutZeroQuantityOnStore(): array
    {
        return array_filter(
            $this->getStoreStocks(),
            function (ProductStoreStock $productStoreStock) {
                return $productStoreStock->getStockQuantity() > 0;
            }
        );
    }

    /**
     * @return \App\Model\Product\StoreStock\ProductStoreStock[]
     */
    public function getStocksWithoutZeroQuantityOnPickupPlaceStore(): array
    {
        $productStoreStocks = array_filter(
            $this->getStoreStocks(),
            function (ProductStoreStock $productStoreStock) {
                return $productStoreStock->getStockQuantity() > 0
                    && $productStoreStock->getStore()->isPickupPlace() === true;
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
     * @return \App\Model\Product\StoreStock\ProductStoreStock[]
     */
    public function getStocksWithoutZeroQuantityOnCentralStore(): array
    {
        return array_filter(
            $this->getStoreStocks(),
            function (ProductStoreStock $productStoreStock) {
                return $productStoreStock->getStockQuantity() > 0
                    && $productStoreStock->getStore()->isCentralStore();
            }
        );
    }

    /**
     * @param int $domainId
     * @return bool
     */
    public function isGenerateToMergadoXmlFeed(int $domainId): bool
    {
        return $this->getProductDomain($domainId)->isGenerateToMergadoXmlFeed();
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
     * @return \App\Model\Product\ProductGift\ProductGift[]
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
     * @return \App\Model\Product\Product[]
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
     * @return bool
     */
    public function isGiftCertificate(): bool
    {
        return $this->giftCertificate;
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

    /**
     * @param int $quantity
     */
    public function subtractStockQuantity($quantity)
    {
        parent::subtractStockQuantity($quantity);
        $remainingQuantity = $quantity;

        foreach ($this->getStoreStocks() as $productStoreStock) {
            $availableQuantity = $productStoreStock->getStockQuantity();

            if ($remainingQuantity > $availableQuantity) {
                $productStoreStock->subtractStockQuantity($availableQuantity);
                $remainingQuantity -= $availableQuantity;
            } else {
                $productStoreStock->subtractStockQuantity($remainingQuantity);
                break;
            }
        }
    }

    /**
     * @return string|null
     */
    public function getVariantId(): ?string
    {
        return $this->variantId;
    }

    /**
     * ProductIsAlreadyVariantException is not thrown in this overridden method
     * @see \App\Model\Product\ProductVariantTropicFacade (refreshVariantStatus method)
     * @param \App\Model\Product\Product $variant
     */
    public function addVariant(BaseProduct $variant)
    {
        if (!$this->isMainVariant()) {
            throw new \Shopsys\FrameworkBundle\Model\Product\Exception\VariantCanBeAddedOnlyToMainVariantException(
                $this->getId(),
                $variant->getId()
            );
        }
        if ($variant->isMainVariant()) {
            throw new \Shopsys\FrameworkBundle\Model\Product\Exception\MainVariantCannotBeVariantException($variant->getId());
        }

        if (!$this->variants->contains($variant)) {
            $this->variants->add($variant);
            $variant->setMainVariant($this);
            $variant->copyProductCategoryDomains($this->productCategoryDomains->toArray());
        }
    }

    /**
     * @return int
     */
    public function getVariantsCount(): int
    {
        return $this->variants->count();
    }

    /**
     * @return \App\Model\Product\Product[]
     */
    public function getVariants()
    {
        $variants = $this->variants->toArray();
        usort($variants, function (self $variant1, self $variant2) {
            return intval(self::getVariantNumber($variant1->getVariantId())) - intval(self::getVariantNumber($variant2->getVariantId()));
        });

        return $variants;
    }

    /**
     * @param string $variantId
     * @return string
     */
    public static function getVariantNumber(string $variantId): string
    {
        return substr($variantId, strpos($variantId, ProductVariantTropicFacade::VARIANT_ID_SEPARATOR) + 1);
    }

    /**
     * @param string $variantId
     * @return string
     */
    public static function getMainVariantVariantIdFromVariantVariantId(string $variantId): string
    {
        return substr($variantId, 0, strpos($variantId, ProductVariantTropicFacade::VARIANT_ID_SEPARATOR));
    }

    /**
     * @return bool
     */
    public function isRegistrationDiscountDisabled(): bool
    {
        return $this->registrationDiscountDisabled;
    }

    /**
     * @return \DateTime|null
     */
    public function getUpdatedByPohodaAt(): ?\DateTime
    {
        return $this->updatedByPohodaAt;
    }

    /**
     * @return int|null
     */
    public function getPohodaProductType(): ?int
    {
        return $this->pohodaProductType;
    }

    /**
     * @return bool
     */
    public function isPohodaProductTypeGroup(): bool
    {
        return $this->pohodaProductType === self::POHODA_PRODUCT_TYPE_ID_PRODUCT_GROUP;
    }

    /**
     * @return bool
     */
    public function isPohodaProductTypeSingle(): bool
    {
        return $this->pohodaProductType === self::POHODA_PRODUCT_TYPE_ID_SINGLE_PRODUCT;
    }

    /**
     * @param \App\Model\Product\ProductData $productData
     */
    protected function createDomains(BaseProductData $productData): void
    {
        $domainIds = array_keys($productData->seoTitles);

        foreach ($domainIds as $domainId) {
            $productDomain = new ProductDomain($this, $domainId);
            $this->domains->add($productDomain);
        }

        $this->setDomains($productData);
    }

    /**
     * @param \App\Model\Product\ProductData $productData
     */
    protected function setDomains(BaseProductData $productData): void
    {
        parent::setDomains($productData);

        foreach ($this->domains as $productDomain) {
            $domainId = $productDomain->getDomainId();
            $productDomain->setGenerateToMergadoXmlFeed($productData->generateToMergadoXmlFeeds[$domainId]);
        }
    }

    /**
     * Temporary fix for empty variant aliases
     *
     * @param \App\Model\Product\ProductData $productData
     */
    protected function setTranslations(BaseProductData $productData)
    {
        parent::setTranslations($productData);

        if ($this->isVariant()) {
            foreach ($productData->variantAlias as $locale => $variantAlias) {
                $this->translation($locale)->setVariantAlias($variantAlias ?? self::getVariantNumber($this->getVariantId()));
            }
        }
    }
}
