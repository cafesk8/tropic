<?php

declare(strict_types=1);

namespace App\Model\Product;

use DateTime;
use Shopsys\FrameworkBundle\Model\Product\ProductData as BaseProductData;

/**
 * @property \App\Model\Product\Flag\Flag[] $flags
 * @property \App\Model\Category\Category[][] $categoriesByDomainId
 * @property \App\Model\Product\Brand\Brand|null $brand
 * @property \App\Model\Product\Product[] $accessories
 * @property \App\Model\Product\Product[] $variants
 * @property \App\Model\Product\Availability\Availability|null $availability
 * @property \App\Model\Product\Availability\Availability|null $outOfStockAvailability
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
     * @var \Shopsys\FrameworkBundle\Component\Money\Money[]
     */
    public $actionPrices;

    /**
     * @var bool
     */
    public $generateToHsSportXmlFeed;

    /**
     * @var bool|null
     */
    public $finished;

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

    public function __construct()
    {
        parent::__construct();
        $this->productsInGroup = [];
        $this->actionPrices = [];
        $this->mallExport = false;
        $this->mallExportedAt = null;
        $this->updatedAt = new DateTime();
        $this->minimumAmount = 1;
        $this->amountMultiplier = 1;
        $this->youtubeVideoIds = [];
        $this->giftCertificate = false;
    }
}
