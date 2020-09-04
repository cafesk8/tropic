<?php

declare(strict_types=1);

namespace App\Model\Product\Flag;

use Shopsys\FrameworkBundle\Model\Product\Flag\FlagData as BaseFlagData;

class FlagData extends BaseFlagData
{
    /**
     * @var int|null
     */
    public $position;

    /**
     * @var string|null
     */
    public $pohodaId;
}
