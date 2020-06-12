<?php

declare(strict_types=1);

namespace App\Model\Product;

use DateTime;
use Shopsys\FrameworkBundle\Model\Product\ProductData as BaseProductData;

/**
 * @property \App\Model\Category\Category[][] $categoriesByDomainId
 * @property \App\Model\Product\Brand\Brand|null $brand
 * @property \App\Model\Product\Product[] $accessories
 * @property \App\Model\Product\Product[] $variants
 * @property \App\Model\Product\Availability\Availability|null $availability
 * @property \App\Model\Product\Availability\Availability|null $outOfStockAvailability
 * @property \App\Model\Product\Unit\Unit|null $unit
 */
class ProductData extends BaseProductData
{
    /**
     * @var array
     */
    public $stockQuantityByStoreId = [];

    /**
     * @var int|null
     */
    public $pohodaId = null;

    /**
     * @var \App\Model\Product\Product[]
     */
    public $productsInGroup;

    /**
     * @var bool[]
     */
    public $generateToMergadoXmlFeeds;

    /**
     * @var bool
     */
    public $mallExport;

    /**
     * @var \DateTime|null
     */
    public $mallExportedAt;

    /**
     * @var \DateTime
     */
    public $updatedAt;

    /**
     * @var string|null
     */
    public $baseName;

    /**
     * @var bool
     */
    public $giftCertificate;

    /**
     * @var int
     */
    public $minimumAmount;

    /**
     * @var int
     */
    public $amountMultiplier;

    /**
     * @var string[]
     */
    public $youtubeVideoIds;

    /**
     * @var string|null
     */
    public $variantId;

    /**
     * @var bool
     */
    public $registrationDiscountDisabled;

    /**
     * @var bool
     */
    public $promoDiscountDisabled;

    /**
     * @var \DateTime|null
     */
    public $updatedByPohodaAt;

    /**
     * @var array[]
     */
    public $groupItems;

    /**
     * @var int
     */
    public $pohodaProductType;

    /**
     * @var bool
     */
    public $eurCalculatedAutomatically;

    /**
     * @var string|null
     */
    public $deliveryDays;

    /**
     * @var int|null
     */
    public $warranty;

    /**
     * @var \App\Model\Product\Flag\ProductFlagData[]
     */
    public $flags;

    /**
     * @var string[]|null[]
     */
    public $descriptionHashes;

    /**
     * @var string[]|null[]
     */
    public $shortDescriptionHashes;

    /**
     * @var bool
     */
    public $descriptionAutomaticallyTranslated;

    /**
     * @var bool
     */
    public $shortDescriptionAutomaticallyTranslated;

    /**
     * @var bool[]
     */
    public $shown;

    public function __construct()
    {
        parent::__construct();
        $this->productsInGroup = [];
        $this->mallExport = false;
        $this->mallExportedAt = null;
        $this->updatedAt = new DateTime();
        $this->minimumAmount = 1;
        $this->amountMultiplier = 1;
        $this->youtubeVideoIds = [];
        $this->giftCertificate = false;
        $this->registrationDiscountDisabled = false;
        $this->promoDiscountDisabled = false;
        $this->groupItems = [];
        $this->generateToMergadoXmlFeeds = [];
        $this->eurCalculatedAutomatically = true;
        $this->descriptionHashes = [];
        $this->shortDescriptionHashes = [];
        $this->descriptionAutomaticallyTranslated = true;
        $this->shortDescriptionAutomaticallyTranslated = true;
        $this->shown = [];
    }
}
