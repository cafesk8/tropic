<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product;

use DateTime;
use Shopsys\FrameworkBundle\Model\Product\ProductData as BaseProductData;

class ProductData extends BaseProductData
{
    /**
     * @var array
     */
    public $stockQuantityByStoreId = [];

    /**
     * @var string|null
     */
    public $transferNumber = null;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter|null
     */
    public $distinguishingParameter;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroup|null
     */
    public $mainVariantGroup;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter|null
     */
    public $distinguishingParameterForMainVariantGroup;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Product[]
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
     * @var string|null
     */
    public $youtubeVideoId;

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
     * @var string|null
     */
    public $productType;

    public function __construct()
    {
        parent::__construct();
        $this->productsInGroup = [];
        $this->actionPrices = [];
        $this->mallExport = false;
        $this->mallExportedAt = null;
        $this->updatedAt = new DateTime();
    }
}
