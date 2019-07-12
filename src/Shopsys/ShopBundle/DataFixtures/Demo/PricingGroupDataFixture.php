<?php

namespace Shopsys\ShopBundle\DataFixtures\Demo;

use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupData;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupFacade;
use Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup;

class PricingGroupDataFixture extends AbstractReferenceFixture
{
    const PRICING_GROUP_BASIC_DOMAIN = 'pricing_group_basic_domain';
    const PRICING_GROUP_ADEPT_DOMAIN = 'pricing_group_adept_domain';
    const PRICING_GROUP_CLASSIC_DOMAIN = 'pricing_group_classic_domain';
    const PRICING_GROUP_REAL_BUSHMAN_DOMAIN = 'pricing_group_real_bushman_domain';

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupFacade
     */
    protected $pricingGroupFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroupDataFactory
     */
    protected $pricingGroupDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupDataFactoryInterface $pricingGroupDataFactory
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(
        PricingGroupFacade $pricingGroupFacade,
        PricingGroupDataFactoryInterface $pricingGroupDataFactory,
        Domain $domain
    ) {
        $this->pricingGroupFacade = $pricingGroupFacade;
        $this->pricingGroupDataFactory = $pricingGroupDataFactory;
        $this->domain = $domain;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /**
         * The pricing group is created with specific ID in database migration.
         * @see \Shopsys\FrameworkBundle\Migrations\Version20180603135346
         */
        $defaultPricingGroup = $this->pricingGroupFacade->getById(1);

        $defaultPricingGroupData = $this->pricingGroupDataFactory->createFromPricingGroup($defaultPricingGroup);
        $defaultPricingGroupData->name = 'Běžný zákazník';
        $defaultPricingGroupData->internalId = PricingGroup::PRICING_GROUP_ORDINARY_CUSTOMER;

        $this->pricingGroupFacade->edit($defaultPricingGroup->getId(), $defaultPricingGroupData);
        $this->addReferenceForDomain(self::PRICING_GROUP_BASIC_DOMAIN, $defaultPricingGroup, Domain::FIRST_DOMAIN_ID);

        $this->createPricingGroup($defaultPricingGroupData, self::PRICING_GROUP_BASIC_DOMAIN, false);

        $pricingGroupData = $this->pricingGroupDataFactory->create();

        $pricingGroupData->name = 'ADEPT';
        $pricingGroupData->internalId = PricingGroup::PRICING_GROUP_ADEPT;
        $this->createPricingGroup($pricingGroupData, self::PRICING_GROUP_ADEPT_DOMAIN, true, [
            1 => Money::create('1999'),
            2 => Money::create('79.99'),
            3 => Money::create('79.99'),
        ]);

        $pricingGroupData->name = 'CLASSIC';
        $pricingGroupData->internalId = PricingGroup::PRICING_GROUP_CLASSIC;
        $this->createPricingGroup($pricingGroupData, self::PRICING_GROUP_CLASSIC_DOMAIN, true, [
            1 => Money::create('19999'),
            2 => Money::create('799.99'),
            3 => Money::create('799.99'),
        ]);

        $pricingGroupData->name = 'REAL BUSHMAN';
        $pricingGroupData->internalId = PricingGroup::PRICING_GROUP_REAL_BUSHMAN;
        $this->createPricingGroup($pricingGroupData, self::PRICING_GROUP_REAL_BUSHMAN_DOMAIN, true, [
            1 => Money::create('49999'),
            2 => Money::create('1999.99'),
            3 => Money::create('1999.99'),
        ]);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroupData $pricingGroupData
     * @param string $referenceName
     * @param bool $firstDomain
     */
    protected function createPricingGroup(
        PricingGroupData $pricingGroupData,
        $referenceName,
        $firstDomain = true,
        $minimalPrices = null
    ) {
        foreach ($this->domain->getAllIds() as $domainId) {
            if ($firstDomain === false && $domainId === Domain::FIRST_DOMAIN_ID) {
                continue;
            }

            if ($minimalPrices !== null) {
                $pricingGroupData->minimalPrice = $minimalPrices[$domainId];
            }

            $pricingGroup = $this->pricingGroupFacade->create($pricingGroupData, $domainId);
            $this->addReferenceForDomain($referenceName, $pricingGroup, $domainId);
        }
    }
}
