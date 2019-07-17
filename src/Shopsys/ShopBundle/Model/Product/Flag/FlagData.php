<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Flag;

use Shopsys\FrameworkBundle\Model\Product\Flag\FlagData as BaseFlagData;

class FlagData extends BaseFlagData
{
    /**
     * @var int|null
     */
    public $position;
}
