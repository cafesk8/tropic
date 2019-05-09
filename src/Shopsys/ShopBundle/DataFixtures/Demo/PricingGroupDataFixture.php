<?php

namespace Shopsys\ShopBundle\DataFixtures\Demo;

use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupData;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupFacade;

class PricingGroupDataFixture extends AbstractReferenceFixture
{
    const PRICING_GROUP_ORDINARY_DOMAIN = 'pricing_group_ordinary_domain';
    const PRICING_GROUP_VIP_DOMAIN = 'pricing_group_vip_domain';
    const PRICING_GROUP_PARTNER_DOMAIN = 'pricing_group_partner_domain';

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupFacade
     */
    protected $pricingGroupFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupDataFactoryInterface
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
        /** @var $defaultPricingGroup \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup */
        $this->addReferenceForDomain(self::PRICING_GROUP_ORDINARY_DOMAIN, $defaultPricingGroup, Domain::FIRST_DOMAIN_ID);

        $pricingGroupData = $this->pricingGroupDataFactory->create();
        $pricingGroupData->name = 'Partner';
        $this->createPricingGroup($pricingGroupData, Domain::FIRST_DOMAIN_ID, self::PRICING_GROUP_PARTNER_DOMAIN);

        $pricingGroupData->name = 'VIP customer';
        $this->createPricingGroup($pricingGroupData, Domain::FIRST_DOMAIN_ID, self::PRICING_GROUP_VIP_DOMAIN);

        foreach ($this->domain->getAllIdsExcludingFirstDomain() as $domainId) {
            $this->loadForDomain($domainId);
        }
    }

    /**
     * @param int $domainId
     */
    protected function loadForDomain($domainId)
    {
        $pricingGroupData = $this->pricingGroupDataFactory->create();

        $pricingGroupData->name = 'Obyčejný zákazník';

        $alreadyCreatedDemoPricingGroupsByDomain = $this->pricingGroupFacade->getByDomainId($domainId);
        if (count($alreadyCreatedDemoPricingGroupsByDomain) > 0) {
            $pricingGroup = reset($alreadyCreatedDemoPricingGroupsByDomain);

            $this->pricingGroupFacade->edit($pricingGroup->getId(), $pricingGroupData);
            $this->addReferenceForDomain(self::PRICING_GROUP_ORDINARY_DOMAIN, $pricingGroup, $domainId);
        }

        $pricingGroupData->name = 'VIP zákazník';
        $this->createPricingGroup($pricingGroupData, $domainId, self::PRICING_GROUP_VIP_DOMAIN);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupData $pricingGroupData
     * @param int $domainId
     * @param string $referenceName
     */
    protected function createPricingGroup(
        PricingGroupData $pricingGroupData,
        $domainId,
        $referenceName
    ) {
        $pricingGroup = $this->pricingGroupFacade->create($pricingGroupData, $domainId);
        $this->addReferenceForDomain($referenceName, $pricingGroup, $domainId);
    }
}
