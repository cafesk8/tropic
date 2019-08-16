<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Parameter;

use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterData as BaseParameterData;

class ParameterData extends BaseParameterData
{
    /**
     * @var bool
     */
    public $visibleOnFrontend;

    /**
     * @var string
     */
    public $type;

    public function __construct()
    {
        parent::__construct();

        $this->visibleOnFrontend = true;
    }
}
