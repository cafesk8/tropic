<?php

declare(strict_types=1);

namespace App\Model\Product\Gift;

use App\Component\Domain\DomainHelper;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;

class ProductGiftPriceCalculation
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(Domain $domain)
    {
        $this->domain = $domain;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Money\Money
     */
    public function getGiftPrice(): Money
    {
        if ($this->domain->getId() === DomainHelper::CZECH_DOMAIN) {
            return Money::create(1);
        } else {
            return Money::create('0.1');
        }
    }
}
