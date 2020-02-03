<?php

declare(strict_types=1);

namespace App\Model\Pricing\Currency;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\Currency as BaseCurrency;

/**
 * @ORM\Table(name="currencies")
 * @ORM\Entity
 */
class Currency extends BaseCurrency
{
    public const CZECH_MINIMUM_FRACTION_DIGITS = 0;
}
