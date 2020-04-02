<?php

declare(strict_types=1);

namespace App\Model\Pricing\Vat;

use Shopsys\FrameworkBundle\Model\Pricing\Vat\VatData as BaseVatData;

class VatData extends BaseVatData
{
    /**
     * @var int|null
     */
    public $pohodaId;
}
