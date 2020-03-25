<?php

declare(strict_types=1);

namespace App\Twig;

use App\Model\Pricing\Group\PricingGroupFacade;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PricingGroupExtension extends AbstractExtension
{
    /**
     * @var \App\Model\Pricing\Group\PricingGroupFacade
     */
    private $pricingGroupFacade;

    /**
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     */
    public function __construct(PricingGroupFacade $pricingGroupFacade)
    {
        $this->pricingGroupFacade = $pricingGroupFacade;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('getRegisteredCustomerPricingGroupDiscount', [$this, 'getRegisteredCustomerPricingGroupDiscount']),
        ];
    }

    /**
     * @return float
     */
    public function getRegisteredCustomerPricingGroupDiscount(): float
    {
        $pricingGroup = $this->pricingGroupFacade->getForRegisteredCustomer();

        return $pricingGroup->getDiscount();
    }
}
