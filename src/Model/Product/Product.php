<?php

declare(strict_types=1);

namespace App\Model\Product;

use App\Component\Domain\DomainHelper;
use App\Model\Cart\Exception\OutOfStockException;
use App\Model\LuigisBox\LuigisBoxExportableInterface;
use App\Model\Product\Exception\ProductIsNotMainVariantException;
use App\Model\Product\Flag\Flag;
use App\Model\Product\Flag\ProductFlag;
use App\Model\Product\Mall\ProductMallExportMapper;
use App\Model\Product\ProductGift\ProductGift;
use App\Model\Product\Set\ProductSet;
use App\Model\Product\StoreStock\ProductStoreStock;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Localization\Exception\ImplicitLocaleNotSetException;
use Shopsys\FrameworkBundle\Model\Product\Product as BaseProduct;
use Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomain;
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
 * @property \App\Model\Product\Unit\Unit $unit
 * @method \App\Model\Product\Unit\Unit getUnit()
 */
class Product extends BaseProduct implements LuigisBoxExportableInterface
{
    public const IMAGE_TYPE_STICKER = 'sticker';
    public const POHODA_PRODUCT_TYPE_ID_SINGLE_PRODUCT = 1;
    public const POHODA_PRODUCT_TYPE_ID_GIFT_CARD = 3;
    public const POHODA_PRODUCT_TYPE_ID_PRODUCT_SET = 5;
    public const SUPPLIER_SET_ITEM_NAME_COUNT_SEPARATOR = '*';
    public const DELIVERY_DAYS_NOT_FILLED = -99;

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
     * @var \App\Model\Product\Flag\ProductFlag[]|\Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="App\Model\Product\Flag\ProductFlag", mappedBy="product", cascade={"remove"})
     * @ORM\JoinTable(name="product_flags")
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
     * @var \App\Model\Product\Set\ProductSet[]|\Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="App\Model\Product\Set\ProductSet", mappedBy="mainProduct")
     */
    private $productSets;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $deliveryDays;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $realStockQuantity;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $refresh;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $warranty;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $descriptionAutomaticallyTranslated;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $shortDescriptionAutomaticallyTranslated;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     * @deprecated Use ProductDomain::shown property instead
     */
    protected $hidden;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private bool $bulky;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private bool $oversized;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $supplierSet;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $foreignSupplier;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private ?float $weight;

    /**
     * @deprecated, we do not work with product_calculated_prices on this project at all
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default" = true})
     */
    protected $recalculatePrice;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $transportFeeMultiplier;

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
        $this->hidden = false;
    }

    /**
     * @deprecated since US-7741, variants are paired using variantId
     * @see \App\Model\Product\ProductVariantTropicFacade, method refreshVariantStatus
     *
     * @param \App\Model\Product\ProductData $productData
     * @param \App\Model\Product\Product[] $variants
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
        $this->hidden = false;
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
        $this->updatedByPohodaAt = $productData->updatedByPohodaAt;
        $this->pohodaProductType = $productData->pohodaProductType;
        $this->warranty = $productData->warranty;
        $this->productSets = new ArrayCollection();
        $this->deliveryDays = $productData->deliveryDays;
        $this->refresh = false;
        $this->flags = new ArrayCollection();
        $this->descriptionAutomaticallyTranslated = $productData->descriptionAutomaticallyTranslated;
        $this->shortDescriptionAutomaticallyTranslated = $productData->shortDescriptionAutomaticallyTranslated;
        $this->bulky = $productData->bulky;
        $this->oversized = $productData->oversized;
        $this->supplierSet = $productData->supplierSet;
        $this->foreignSupplier = $productData->foreignSupplier;
        $this->weight = $productData->weight;
        $this->transportFeeMultiplier = $productData->transportFeeMultiplier;
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
     * Flags are edited through ProductFacade::refreshProductFlags
     * This method does not accept ProductFlag arguments
     *
     * @param \App\Model\Product\Flag\Flag[] $flags
     */
    public function editFlags(array $flags)
    {
        $this->flags = new ArrayCollection();
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
        if ($this->isMainVariant()) {
            throw new \Exception('Don\'t call getStoreStocks from main variant! (ID ' . $this->id . ')');
        }

        $storeStocks = $this->storeStocks->toArray();
        usort($storeStocks, function (ProductStoreStock $storeStockA, ProductStoreStock $storeStockB) {
            return $storeStockA->getStore()->getPosition() - $storeStockB->getStore()->getPosition();
        });

        return $storeStocks;
    }

    /**
     * @return \App\Model\Product\Product
     */
    public function cloneSelfAndStoreStocks(): self
    {
        $productClone = clone $this;
        $storeStocks = $productClone->storeStocks->toArray();

        foreach ($storeStocks as $key => $storeStock) {
            $storeStocks[$key] = clone $storeStock;
        }

        $productClone->storeStocks = new ArrayCollection($storeStocks);

        if ($productClone->isPohodaProductTypeSet()) {
            $clonedSets = [];

            foreach ($productClone->getProductSets() as $productSet) {
                $clonedSets[] = new ProductSet($productClone, $productSet->getItem()->cloneSelfAndStoreStocks(), $productSet->getItemCount());
            }

            $productClone->productSets = new ArrayCollection($clonedSets);
        }

        return $productClone;
    }

    public function clearStoreStocks(): void
    {
        $this->storeStocks->clear();
    }

    /**
     * @return int
     */
    public function getNonExternalStockQuantity(): int
    {
        $nonExternalStockQuantity = 0;
        foreach ($this->storeStocks as $storeStock) {
            if ($storeStock->getStockQuantity() !== null && !$storeStock->getStore()->isExternalStock()) {
                $nonExternalStockQuantity += $storeStock->getStockQuantity();
            }
        }

        return $nonExternalStockQuantity;
    }

    /**
     * @return int
     */
    public function getRealNonExternalGroupedStockQuantity(): int
    {
        return $this->getCalculatedStockQuantity($this->getNonExternalStockQuantity());
    }

    /**
     * @return int
     */
    public function getInternalStocksQuantity(): int
    {
        $internalStocksQuantity = 0;

        foreach ($this->storeStocks as $storeStock) {
            if ($storeStock->getStore()->isInternalStock() && $storeStock->getStockQuantity() !== null) {
                $internalStocksQuantity += $storeStock->getStockQuantity();
            }
        }

        return $internalStocksQuantity;
    }

    /**
     * @return int
     */
    public function getRealInternalStockQuantity(): int
    {
        return $this->getCalculatedStockQuantity($this->getInternalStocksQuantity());
    }

    /**
     * @return int
     */
    private function getStoreStockQuantity(): int
    {
        foreach ($this->storeStocks as $storeStock) {
            if ($storeStock->getStore()->isStoreStock() && $storeStock->getStockQuantity() !== null) {
                return $storeStock->getStockQuantity();
            }
        }

        return 0;
    }

    /**
     * @return int
     */
    public function getRealStoreStockQuantity(): int
    {
        return $this->getCalculatedStockQuantity($this->getStoreStockQuantity());
    }

    /**
     * @return int
     */
    public function getExternalStockQuantity(): int
    {
        if ($this->isMainVariant()) {
            throw new \Exception('Don\'t call getExternalStockQuantity from main variant! (ID ' . $this->id . ')');
        }

        $externalStockQuantity = 0;
        foreach ($this->storeStocks as $storeStock) {
            if ($storeStock->getStockQuantity() !== null && $storeStock->getStore()->isExternalStock()) {
                $externalStockQuantity += $storeStock->getStockQuantity();
            }
        }

        return $externalStockQuantity;
    }

    /**
     * @return int
     */
    public function getRealExternalStockQuantity(): int
    {
        if ($this->isMainVariant()) {
            throw new \Exception('Don\'t call getRealExternalStockQuantity from main variant! (ID ' . $this->id . ')');
        }

        return $this->getCalculatedStockQuantity($this->getExternalStockQuantity());
    }

    /**
     * @return int
     */
    public function getRealExternalStockAndStoreStockQuantity(): int
    {
        if ($this->isMainVariant()) {
            throw new \Exception('Don\'t call getRealExternalStockAndStoreStockQuantity from main variant! (ID ' . $this->id . ')');
        }

        return $this->getCalculatedStockQuantity($this->getExternalStockQuantity() + $this->getStoreStockQuantity());
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
     * @return \App\Model\Product\Flag\ProductFlag[]
     */
    public function getProductFlags(): array
    {
        $productFlags = $this->flags->toArray();
        usort($productFlags, function (ProductFlag $productFlag1, ProductFlag $productFlag2) {
            return $productFlag1->getFlag()->getPosition() - $productFlag2->getFlag()->getPosition();
        });

        return $productFlags;
    }

    /**
     * @return \App\Model\Product\Flag\ProductFlag[]
     */
    public function getActiveProductFlags(): array
    {
        return array_filter($this->getProductFlags(), function (ProductFlag $productFlag) {
            return $productFlag->isActive();
        });
    }

    /**
     * @param int|null $limit
     * @return \App\Model\Product\Flag\Flag[]
     */
    public function getFlags(?int $limit = null): array
    {
        return array_map(function (ProductFlag $productFlag) {
            return $productFlag->getFlag();
        }, array_slice($this->getProductFlags(), 0, $limit));
    }

    /**
     * @param int|null $limit
     * @return \App\Model\Product\Flag\Flag[]
     */
    public function getActiveFlags(?int $limit = null): array
    {
        return array_map(function (ProductFlag $productFlag) {
            return $productFlag->getFlag();
        }, array_slice($this->getActiveProductFlags(), 0, $limit));
    }

    /**
     * @param int $limit
     * @return \App\Model\Product\Flag\Flag[]
     */
    public function getFlagsIndexedByPosition(int $limit): array
    {
        $flagsIndexedByPosition = [];

        foreach ($this->getActiveFlags($limit) as $flag) {
            $flagsIndexedByPosition[$flag->getPosition()] = $flag;
        }

        return $flagsIndexedByPosition;
    }

    /**
     * @return \App\Model\Product\StoreStock\ProductStoreStock[]
     */
    public function getStocksWithoutZeroQuantityOnStore(): array
    {
        if ($this->isMainVariant()) {
            throw new \Exception('Don\'t call getStocksWithoutZeroQuantityOnStore from main variant! (ID ' . $this->id . ')');
        }

        return array_filter(
            $this->getStoreStocks(),
            function (ProductStoreStock $productStoreStock) {
                return $productStoreStock->getStockQuantity() > 0;
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
     * @param int $realStockQuantity
     * @param bool $checkQuantityBefore
     */
    public function setRealStockQuantity(int $realStockQuantity, bool $checkQuantityBefore = false): void
    {
        if ($checkQuantityBefore && $this->getRealStockQuantity() <= 0) {
            throw new OutOfStockException();
        }
        $this->realStockQuantity = $realStockQuantity;
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

        $totalStockQuantityOfProductVariants -= count($this->variants->count()) * ProductMallExportMapper::STOCK_QUANTITY_FUSE;

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

    /**
     * @param int $domainId
     */
    public function setProductAsNotShown(int $domainId): void
    {
        $this->getProductDomain($domainId)->setShown(false);
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
     * @return \App\Model\Product\ProductGift\ProductGift[]
     */
    public function getActiveInStockProductGiftsByDomainId(int $domainId): array
    {
        $productGifts = $this->getActiveProductGiftsByDomainId($domainId);
        $productGiftsInStock = array_filter($productGifts, fn (ProductGift $productGift) => !$productGift->getGift()->isCurrentlyOutOfStock());
        usort($productGiftsInStock, fn (ProductGift $productGift1, ProductGift $productGift2) => $productGift2->getId() - $productGift1->getId());

        return $productGiftsInStock;
    }

    /**
     * @param int $domainId
     * @return \App\Model\Product\ProductGift\ProductGift|null
     */
    public function getFirstActiveInStockProductGiftByDomainId(int $domainId): ?ProductGift
    {
        $productGiftsInStock = $this->getActiveInStockProductGiftsByDomainId($domainId);

        return array_shift($productGiftsInStock);
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
        if ($this->isMainVariant()) {
            throw new \Exception('Don\'t call getRealStockQuantity from main variant! (ID ' . $this->id . ')');
        }

        return (int)$this->realStockQuantity;
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
     * @param int $domainId
     * @return int
     */
    public function getVariantsCount(int $domainId): int
    {
        return $this->variants->filter(fn (Product $variant) => $variant->isShownOnDomain($domainId))->count();
    }

    /**
     * @param string|null $locale
     * @return \App\Model\Product\Product[]
     */
    public function getVariants(?string $locale = null): array
    {
        $variants = $this->variants->toArray();
        usort($variants, function (self $variant1, self $variant2) use ($locale) {
            $sortValue = $variant1->getCalculatedAvailability()->getRating() - $variant2->getCalculatedAvailability()->getRating();

            if ($sortValue === 0) {
                $sortValue = intval(self::getVariantNumber($variant1->getVariantId())) - intval(self::getVariantNumber($variant2->getVariantId()));
            }

            if ($sortValue === 0) {
                try {
                    $sortValue = strcmp($variant1->getName($locale), $variant2->getName($locale));
                } catch (ImplicitLocaleNotSetException $exception) {
                    $sortValue = strcmp($variant1->getName(DomainHelper::CZECH_LOCALE), $variant2->getName(DomainHelper::CZECH_LOCALE));
                }
            }

            return $sortValue;
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
     * @param int $domainId
     * @return bool
     */
    public function isRegistrationDiscountDisabled(int $domainId): bool
    {
        return $this->getProductDomain($domainId)->isRegistrationDiscountDisabled();
    }

    /**
     * @param int $domainId
     * @return bool
     */
    public function isPromoDiscountDisabled(int $domainId): bool
    {
        return $this->getProductDomain($domainId)->isPromoDiscountDisabled();
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
    public function isPohodaProductTypeSet(): bool
    {
        return $this->pohodaProductType === self::POHODA_PRODUCT_TYPE_ID_PRODUCT_SET;
    }

    /**
     * @return bool
     */
    public function isPohodaProductTypeSingle(): bool
    {
        return $this->pohodaProductType === self::POHODA_PRODUCT_TYPE_ID_SINGLE_PRODUCT;
    }

    /**
     * @param \App\Model\Product\Set\ProductSet $setItem
     */
    public function addProductSet(ProductSet $setItem)
    {
        $this->productSets[] = $setItem;
    }

    /**
     * @return int|null
     */
    public function getWarranty(): ?int
    {
        return $this->warranty;
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
            $productDomain->setDescriptionHash($productData->descriptionHashes[$domainId]);
            $productDomain->setShortDescriptionHash($productData->shortDescriptionHashes[$domainId]);
            $productDomain->setShown($productData->shown[$domainId]);
            $productDomain->setNameForMergadoFeed($productData->namesForMergadoFeed[$domainId]);
            $productDomain->setTransportFee($productData->transportFee[$domainId]);
            $productDomain->setExportedToLuigisBox(false);
            $productDomain->setRegistrationDiscountDisabled($productData->registrationDiscountDisabled[$domainId]);
            $productDomain->setPromoDiscountDisabled($productData->promoDiscountDisabled[$domainId]);
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

    /**
     * @return \App\Model\Product\Set\ProductSet[]
     */
    public function getProductSets(): array
    {
        return $this->productSets->toArray();
    }

    /**
     * @return string|null
     */
    public function getDeliveryDays(): ?string
    {
        return $this->deliveryDays;
    }

    /**
     * @return int
     */
    public function getDeliveryDaysAsNumber(): int
    {
        if ($this->deliveryDays === null) {
            return self::DELIVERY_DAYS_NOT_FILLED;
        }

        return (int)(explode('-', $this->deliveryDays)[0]);
    }

    /**
     * @param bool $withoutSaleStock
     * @return bool
     */
    public function isProductOnlyAtExternalStock(bool $withoutSaleStock = false): bool
    {
        if ($this->isMainVariant()) {
            throw new \Exception('Don\'t call isProductOnlyAtExternalStock from main variant! (ID ' . $this->id . ')');
        }
        $stockQuantity = $withoutSaleStock ? $this->getRealNonSaleStocksQuantity() : $this->getRealStockQuantity();

        return $this->getRealExternalStockQuantity() > 0 && $this->getRealExternalStockQuantity() === $stockQuantity;
    }

    /**
     * @param bool $withoutSaleStock
     * @return bool
     */
    public function isProductOnlyAtExternalStockAndStoreStock(bool $withoutSaleStock = false): bool
    {
        if ($this->isMainVariant()) {
            throw new \Exception('Don\'t call isProductOnlyAtExternalStock from main variant! (ID ' . $this->id . ')');
        }

        $stockQuantity = $withoutSaleStock ? $this->getRealNonSaleStocksQuantity() : $this->getRealStockQuantity();
        $realExternalStockAndStoreStockQuantity = $this->getRealExternalStockAndStoreStockQuantity();

        return $realExternalStockAndStoreStockQuantity > 0 && $realExternalStockAndStoreStockQuantity === $stockQuantity;
    }

    /**
     * @param bool $withoutSaleStock
     * @return bool
     */
    public function isProductOnlyAtStoreStock(bool $withoutSaleStock = false): bool
    {
        if ($this->isMainVariant()) {
            throw new \Exception('Don\'t call isProductOnlyAtExternalStock from main variant! (ID ' . $this->id . ')');
        }
        $stockQuantity = $withoutSaleStock ? $this->getRealNonSaleStocksQuantity() : $this->getRealStockQuantity();

        return $this->getRealStoreStockQuantity() > 0 && $this->getRealStoreStockQuantity() === $stockQuantity;
    }

    /**
     * @return bool
     */
    public function isAvailable(): bool
    {
        if ($this->isMainVariant()) {
            throw new \Exception('Don\'t call isAvailable from main variant! (ID ' . $this->id . ')');
        }

        return (!$this->isAvailableInDays() && $this->getRealStockQuantity() > 0)
            || ($this->isProductOnlyAtExternalStock() && $this->getDeliveryDays() === null);
    }

    /**
     * @param bool $withoutSaleStock
     * @return bool
     */
    public function isAvailableInDays(bool $withoutSaleStock = false): bool
    {
        if ($this->isMainVariant()) {
            throw new \Exception('Don\'t call isAvailableInDays from main variant! (ID ' . $this->id . ')');
        }
        if ($this->isProductOnlyAtStoreStock($withoutSaleStock))
        {
            return false;
        }

        return $this->isProductOnlyAtExternalStockAndStoreStock($withoutSaleStock) && $this->getDeliveryDays() !== null;
    }

    /**
     * @return bool
     */
    public function isCurrentlyOutOfStock(): bool
    {
        if ($this->isMainVariant()) {
            throw new \Exception('Don\'t call isCurrentlyOutOfStock from main variant! (ID ' . $this->id . ')');
        }

        return $this->getRealStockQuantity() < 1;
    }

    /**
     * @return bool
     */
    public function isInAnySaleStock(): bool
    {
        if ($this->isMainVariant()) {
            foreach ($this->variants->toArray() as $variant) {
                if ($variant->isInAnySaleStock()) {
                    return true;
                }
            }

            return false;
        }

        return $this->getRealSaleStocksQuantity() > 0;
    }

    /**
     * @return int
     */
    public function getSaleStocksQuantity(): int
    {
        if ($this->isMainVariant()) {
            throw new \Exception('Don\'t call getSaleStocksQuantity from main variant! (ID ' . $this->id . ')');
        }

        $stockQuantity = 0;
        foreach ($this->getStoreStocks() as $productStoreStock) {
            if ($this->isInSaleStock($productStoreStock)) {
                $stockQuantity += $productStoreStock->getStockQuantity();
            }
        }

        return $stockQuantity;
    }

    /**
     * @return int
     */
    public function getRealSaleStocksQuantity(): int
    {
        if ($this->isMainVariant()) {
            throw new \Exception('Don\'t call getRealSaleStocksQuantity from main variant! (ID ' . $this->id . ')');
        }

        return $this->getCalculatedStockQuantity($this->getSaleStocksQuantity());
    }

    /**
     * @return int
     */
    private function getNonSaleStocksQuantity(): int
    {
        if ($this->isMainVariant()) {
            throw new \Exception('Don\'t call getNonSaleStocksQuantity from main variant! (ID ' . $this->id . ')');
        }

        return $this->stockQuantity - $this->getSaleStocksQuantity();
    }

    /**
     * @return int
     */
    public function getRealNonSaleStocksQuantity(): int
    {
        if ($this->isMainVariant()) {
            throw new \Exception('Don\'t call getRealNonSaleStocksQuantity from main variant! (ID ' . $this->id . ')');
        }

        return $this->getCalculatedStockQuantity($this->getNonSaleStocksQuantity());
    }

    /**
     * @param \App\Model\Product\StoreStock\ProductStoreStock $productStoreStock
     * @return bool
     */
    private function isInSaleStock(ProductStoreStock $productStoreStock): bool
    {
        if ($this->isMainVariant()) {
            throw new \Exception('Don\'t call isInSaleStock from main variant! (ID ' . $this->id . ')');
        }

        return $productStoreStock->getStore()->isSaleStock() && $productStoreStock->getStockQuantity() > 0;
    }

    public function markForRefresh(): void
    {
        $this->refresh = true;
    }

    public function clearProductFlags(): void
    {
        $this->flags->clear();
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomain[] $productCategoryDomains
     */
    public function setProductCategoryDomains(array $productCategoryDomains)
    {
        foreach ($this->productCategoryDomains as $productCategoryDomain) {
            if ($this->isProductCategoryDomainInArray($productCategoryDomain, $productCategoryDomains) === false) {
                $this->productCategoryDomains->removeElement($productCategoryDomain);
            }
        }
        foreach ($productCategoryDomains as $productCategoryDomain) {
            if ($this->isProductCategoryDomainInArray($productCategoryDomain, $this->productCategoryDomains->toArray()) === false) {
                $this->productCategoryDomains->add($productCategoryDomain);
            }
        }

        if ($this->isMainVariant()) {
            foreach ($this->variants->toArray() as $variant) {
                $variant->copyProductCategoryDomains($productCategoryDomains);
            }
        }
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomain $searchProductCategoryDomain
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomain[] $productCategoryDomains
     * @return bool
     */
    protected function isProductCategoryDomainInArray(ProductCategoryDomain $searchProductCategoryDomain, array $productCategoryDomains): bool
    {
        foreach ($productCategoryDomains as $productCategoryDomain) {
            if ($productCategoryDomain->getCategory() === $searchProductCategoryDomain->getCategory()
                && $productCategoryDomain->getDomainId() === $searchProductCategoryDomain->getDomainId()
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param int $domainId
     * @return string|null
     */
    public function getDescriptionHash(int $domainId): ?string
    {
        return $this->getProductDomain($domainId)->getDescriptionHash();
    }

    /**
     * @param int $domainId
     * @return string|null
     */
    public function getShortDescriptionHash(int $domainId): ?string
    {
        return $this->getProductDomain($domainId)->getShortDescriptionHash();
    }

    /**
     * @return bool
     */
    public function isDescriptionAutomaticallyTranslated(): bool
    {
        return $this->descriptionAutomaticallyTranslated;
    }

    /**
     * @return bool
     */
    public function isShortDescriptionAutomaticallyTranslated(): bool
    {
        return $this->shortDescriptionAutomaticallyTranslated;
    }

    /**
     * Replaces method Product::isHidden
     *
     * @param int $domainId
     * @return bool
     */
    public function isShownOnDomain(int $domainId): bool
    {
        return $this->getProductDomain($domainId)->isShown();
    }

    /**
     * @return bool
     * @deprecated since TF-124 - use Product::isShownOnDomain instead
     */
    public function isHidden()
    {
        return parent::isHidden();
    }

    /**
     * @return int
     */
    public function getBiggestVariantOrderingPriority(): int
    {
        if (!$this->isMainVariant()) {
            return $this->isSellingDenied() ? 0 : $this->getOrderingPriority();
        }

        $biggestPriority = 0;

        foreach ($this->variants as $variant) {
            $currentPriority = $variant->isSellingDenied() ? 0 : $variant->getOrderingPriority();
            $biggestPriority = $currentPriority > $biggestPriority ? $currentPriority : $biggestPriority;
        }

        return $biggestPriority;
    }

    /**
     * @return int
     */
    public function getBiggestVariantRealInternalStockQuantity(): int
    {
        if (!$this->isMainVariant()) {
            return $this->isSellingDenied() ? 0 : $this->getRealNonExternalGroupedStockQuantity();
        }

        $biggestQuantity = 0;

        foreach ($this->variants as $variant) {
            $currentQuantity = $variant->isSellingDenied() ? 0 : $variant->getRealNonExternalGroupedStockQuantity();
            $biggestQuantity = $currentQuantity > $biggestQuantity ? $currentQuantity : $biggestQuantity;
        }

        return $biggestQuantity;
    }

    /**
     * @return int
     */
    public function getBiggestVariantRealExternalStockQuantity(): int
    {
        if (!$this->isMainVariant()) {
            return $this->isSellingDenied() ? 0 : $this->getRealExternalStockQuantity();
        }

        $biggestQuantity = 0;

        foreach ($this->variants as $variant) {
            $currentQuantity = $variant->isSellingDenied() ? 0 : $variant->getRealExternalStockQuantity();
            $biggestQuantity = $currentQuantity > $biggestQuantity ? $currentQuantity : $biggestQuantity;
        }

        return $biggestQuantity;
    }

    /**
     * @return bool
     */
    public function isBulky(): bool
    {
        return $this->bulky;
    }

    /**
     * @return bool
     */
    public function isOversized(): bool
    {
        return $this->oversized;
    }

    /**
     * @return bool
     */
    public function isSupplierSet(): bool
    {
        return $this->supplierSet;
    }

    /**
     * @return bool
     */
    public function isForeignSupplier(): bool
    {
        return $this->foreignSupplier;
    }

    /**
     * @return float|null
     */
    public function getWeight(): ?float
    {
        return $this->weight;
    }

    /**
     * @param int $domainId
     * @return string|null
     */
    public function getNameForMergadoFeed(int $domainId): ?string
    {
        return $this->getProductDomain($domainId)->getNameForMergadoFeed();
    }

    /**
     * @return bool
     */
    public function isRecommended(): bool
    {
        return count(array_filter($this->getActiveFlags(), fn (Flag $flag) => $flag->isRecommended())) > 0;
    }

    /**
     * @param int|null $quantity
     * @return int
     */
    public function getAvailableQuantity(?int $quantity = null): int
    {
        $realStockQuantity = $this->getRealStockQuantity();

        if ($quantity === null) {
            return $realStockQuantity;
        }

        return $realStockQuantity > $quantity ? $quantity : $realStockQuantity;
    }

    /**
     * @return \App\Model\Category\Category[][]
     */
    public function getCategoriesIndexedByDomainId(): array
    {
        /** @var \App\Model\Category\Category[][] $categoriesByDomainId */
        $categoriesByDomainId = parent::getCategoriesIndexedByDomainId();

        foreach ($this->domains as $productDomain) {
            if (!isset($categoriesByDomainId[$productDomain->getDomainId()])) {
                $categoriesByDomainId[$productDomain->getDomainId()] = [];
            }
        }

        return $categoriesByDomainId;
    }

    /**
     * @param int $baseQuantity
     * @return int
     */
    public function getCalculatedStockQuantity(int $baseQuantity): int
    {
        if ($baseQuantity % $this->getAmountMultiplier() !== 0) {
            return (int)floor($baseQuantity / $this->getAmountMultiplier()) * $this->getAmountMultiplier();
        }

        return $baseQuantity;
    }

    /**
     * @deprecated, we do not work with product_calculated_prices on this project at all
     */
    public function markPriceAsRecalculated()
    {
        parent::markPriceAsRecalculated();
    }

    /**
     * @param int $domainId
     * @return bool
     */
    public function isProductInNews(int $domainId): bool
    {
        if ($this->isMainVariant()) {
            foreach ($this->variants->toArray() as $variant) {
                if ($variant->isProductInNews($domainId)) {
                    return true;
                }
            }
        }

        foreach ($this->getFlags() as $flag) {
            if ($flag->isNews()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isAnyVariantInStock(): bool
    {
        foreach ($this->variants as $variant) {
            if (!$variant->isSellingDenied() && $variant->getRealStockQuantity() > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param int $domainId
     * @return \DateTime|null
     */
    public function productNewsFrom(int $domainId): ?DateTime
    {
        if (!$this->isProductInNews($domainId)) {
            return null;
        }

        $newestProductNews = null;
        foreach ($this->getProductFlags() as $productFlag) {
            if ($productFlag->getFlag()->isNews()) {
                $newestProductNews = $productFlag->getActiveFrom();
            }
        }

        if ($this->isMainVariant()) {
            foreach ($this->variants->toArray() as $variant) {
                foreach ($variant->getProductFlags() as $variantFlag) {
                    if ($variantFlag->getFlag()->isNews()) {
                        $variantNewsFrom = $variantFlag->getActiveFrom();
                        if ($variantNewsFrom != null && $variantNewsFrom > $newestProductNews) {
                            $newestProductNews = $variantNewsFrom;
                        }
                    }
                }
            }
        }

        return $newestProductNews;
    }

    /**
     * @return \App\Model\Product\Product[]
     */
    public function getVisibleVariants(): array
    {
        return $this->variants->filter(fn (Product $variant) => $variant->isVisible())->toArray();
    }

    /**
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    public function getTransportFee(int $domainId): ?Money
    {
        return $this->getProductDomain($domainId)->getTransportFee();
    }

    /**
     * @return int|null
     */
    public function getTransportFeeMultiplier(): ?int
    {
        return $this->transportFeeMultiplier;
    }

    /**
     * @param int $domainId
     * @return bool
     */
    public function hasTransportFee(int $domainId): bool
    {
        return !empty($this->getTransportFee($domainId)) && !empty($this->transportFeeMultiplier);
    }

    /**
     * @param int $domainId
     */
    public function markAsExportedToLuigisBox(int $domainId): void
    {
        $this->getProductDomain($domainId)->setExportedToLuigisBox(true);
    }

    public function markForExportToLuigisBox(): void
    {
        foreach ($this->domains as $productDomain) {
            $productDomain->setExportedToLuigisBox(false);
        }
    }
}
