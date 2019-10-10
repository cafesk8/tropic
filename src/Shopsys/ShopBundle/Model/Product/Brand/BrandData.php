<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Brand;

use Shopsys\FrameworkBundle\Model\Product\Brand\BrandData as BaseBrandData;

class BrandData extends BaseBrandData
{
    /**
     * @var string|null
     */
    public $type;

    public function __construct()
    {
        parent::__construct();
    }
}
