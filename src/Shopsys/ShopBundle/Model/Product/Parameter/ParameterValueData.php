<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Parameter;

use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValueData as BaseParameterValueData;

class ParameterValueData extends BaseParameterValueData
{
    /**
     * @var string
     */
    public $hsFeedId;

    /**
     * @var string|null
     */
    public $rgb;

    /**
     * @var string|null
     */
    public $mallName;
}
