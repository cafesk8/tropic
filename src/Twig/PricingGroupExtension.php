<?php

declare(strict_types=1);

namespace App\Twig;

use App\Model\Pricing\Group\PricingGroupFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PricingGroupExtension extends AbstractExtension
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \App\Model\Pricing\Group\PricingGroupFacade
     */
    private $pricingGroupFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     */
    public function __construct(Domain $domain, PricingGroupFacade $pricingGroupFacade)
    {
        $this->pricingGroupFacade = $pricingGroupFacade;
        $this->domain = $domain;
    }

    /**
     * @inheritDoc
     */
    public function getFunctions(): array
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
        $pricingGroup = $this->pricingGroupFacade->getRegisteredCustomerPricingGroup($this->domain->getId());

        return $pricingGroup->getDiscount();
    }
}
