<?php

declare(strict_types=1);

namespace App\Component\Domain;

use App\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Component\Domain\DomainDataCreator as BaseDomainDataCreator;
use Shopsys\FrameworkBundle\Model\Pricing\Vat\Vat;

/**
 * @property \App\Component\Setting\Setting $setting
 * @property \App\Model\Pricing\Group\PricingGroupDataFactory $pricingGroupDataFactory
 * @property \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
 * @property \App\Model\Pricing\Vat\VatDataFactory $vatDataFactory
 * @property \App\Model\Pricing\Vat\VatFacade $vatFacade
 * @method __construct(\Shopsys\FrameworkBundle\Component\Domain\Domain $domain, \App\Component\Setting\Setting $setting, \Shopsys\FrameworkBundle\Component\Setting\SettingValueRepository $settingValueRepository, \Shopsys\FrameworkBundle\Component\Domain\Multidomain\MultidomainEntityDataCreator $multidomainEntityDataCreator, \Shopsys\FrameworkBundle\Component\Translation\TranslatableEntityDataCreator $translatableEntityDataCreator, \App\Model\Pricing\Group\PricingGroupDataFactory $pricingGroupDataFactory, \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade, \App\Model\Pricing\Vat\VatDataFactory $vatDataFactory, \App\Model\Pricing\Vat\VatFacade $vatFacade)
 */
class DomainDataCreator extends BaseDomainDataCreator
{
    /**
     * @param int $domainId
     * @return \App\Model\Pricing\Group\PricingGroup
     */
    protected function createDefaultPricingGroupForNewDomain(int $domainId)
    {
        $pricingGroupData = $this->pricingGroupDataFactory->create();
        $pricingGroupData->name = 'Běžný zákazník';
        $pricingGroupData->internalId = PricingGroup::PRICING_GROUP_ORDINARY_CUSTOMER;

        return $this->pricingGroupFacade->create($pricingGroupData, $domainId);
    }

    /**
     * @param int $domainId
     * @return \App\Model\Pricing\Vat\Vat
     */
    protected function createDefaultVatForNewDomain(int $domainId): Vat
    {
        $vatData = $this->vatDataFactory->create();
        $vatData->name = 'Nulová sazba';
        $vatData->percent = '0';

        return $this->vatFacade->create($vatData, $domainId);
    }
}
