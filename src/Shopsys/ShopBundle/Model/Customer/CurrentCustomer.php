<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer;

use Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer as BaseCurrentCustomer;

class CurrentCustomer extends BaseCurrentCustomer
{
    /**
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup
     */
    public function getPricingGroupOrDefaultPricingGroup(int $domainId)
    {
        /** @var \Shopsys\FrameworkBundle\Model\Customer\User $user */
        $user = $this->findCurrentUser();
        if ($user === null) {
            return $this->pricingGroupSettingFacade->getDefaultPricingGroupByDomainId($domainId);
        } else {
            return $user->getPricingGroup();
        }
    }
}
