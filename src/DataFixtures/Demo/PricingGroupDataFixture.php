<?php

declare(strict_types=1);

namespace App\DataFixtures\Demo;

use App\Component\Domain\DomainHelper;
use App\Model\Pricing\Group\PricingGroup;
use App\Model\Pricing\Group\PricingGroupFacade;
use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Pricing\Group\Exception\PricingGroupNotFoundException;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupDataFactoryInterface;

class PricingGroupDataFixture extends AbstractReferenceFixture
{
    public const PRICING_GROUP_BASIC_DOMAIN = 'pricing_group_basic_domain';
    public const PRICING_GROUP_REGISTERED_DOMAIN = 'pricing_group_registered_domain';
    public const PRICING_GROUP_PURCHASE_DOMAIN = 'pricing_group_purchase_domain';
    public const PRICING_GROUP_STANDARD_DOMAIN = 'pricing_group_standard_domain';
    public const PRICING_GROUP_SALE_DOMAIN = 'pricing_group_sale_domain';

    /**
     * @var \App\Model\Pricing\Group\PricingGroupFacade
     */
    protected $pricingGroupFacade;

    /**
     * @var \App\Model\Pricing\Group\PricingGroupDataFactory
     */
    protected $pricingGroupDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \App\Model\Pricing\Group\PricingGroupDataFactory $pricingGroupDataFactory
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
        foreach ($this->domain->getAllIds() as $domainId) {
            /** Default pricing groups are created in migration Version20180603135346 and DomainDataCreator */
            $defaultPricingGroup = $this->pricingGroupFacade->getByNameAndDomainId(PricingGroup::PRICING_GROUP_ORDINARY_CUSTOMER, $domainId);
            $this->addReferenceForDomain(self::PRICING_GROUP_BASIC_DOMAIN, $defaultPricingGroup, $domainId);

            try {
                $registeredPricingGroup = $this->pricingGroupFacade->getRegisteredCustomerPricingGroup($domainId);
            } catch (PricingGroupNotFoundException $exception) {
                $registeredPricingGroup = $this->createRegisteredCustomerPricingGroup($domainId);
            }

            $this->addReferenceForDomain(self::PRICING_GROUP_REGISTERED_DOMAIN, $registeredPricingGroup, $domainId);

            try {
                $purchasePricingGroup = $this->pricingGroupFacade->getByNameAndDomainId(PricingGroup::PRICING_GROUP_PURCHASE_PRICE, $domainId);
            } catch (PricingGroupNotFoundException $exception) {
                $purchasePricingGroup = $this->createPurchasePricePricingGroup($domainId);
            }

            $this->addReferenceForDomain(self::PRICING_GROUP_PURCHASE_DOMAIN, $purchasePricingGroup, $domainId);

            try {
                $standardPricingGroup = $this->pricingGroupFacade->getStandardPricePricingGroup($domainId);
            } catch (PricingGroupNotFoundException $exception) {
                $standardPricingGroup = $this->createStandardPricePricingGroup($domainId);
            }

            $this->addReferenceForDomain(self::PRICING_GROUP_STANDARD_DOMAIN, $standardPricingGroup, $domainId);

            try {
                $salePricingGroup = $this->pricingGroupFacade->getSalePricePricingGroup($domainId);
            } catch (PricingGroupNotFoundException $exception) {
                $salePricingGroup = $this->createSalePricePricingGroup($domainId);
            }

            $this->addReferenceForDomain(self::PRICING_GROUP_SALE_DOMAIN, $salePricingGroup, $domainId);
        }
    }

    /**
     * @param int $domainId
     * @return \App\Model\Pricing\Group\PricingGroup
     */
    private function createRegisteredCustomerPricingGroup(int $domainId): PricingGroup
    {
        $pricingGroupData = $this->pricingGroupDataFactory->create();
        $pricingGroupData->name = t('Registrovaný zákazník', [], 'dataFixtures', DomainHelper::DOMAIN_ID_TO_LOCALE[$domainId]);
        $pricingGroupData->discount = 3;
        $pricingGroupData->internalId = PricingGroup::PRICING_GROUP_REGISTERED_CUSTOMER;
        $pricingGroupData->minimalPrice = Money::zero();
        $pricingGroupData->calculatedFromDefault = true;

        return $this->pricingGroupFacade->create($pricingGroupData, $domainId);
    }

    /**
     * @param int $domainId
     * @return \App\Model\Pricing\Group\PricingGroup
     */
    private function createPurchasePricePricingGroup(int $domainId): PricingGroup
    {
        $pricingGroupData = $this->pricingGroupDataFactory->create();
        $pricingGroupData->name = t('Nákupní cena', [], 'dataFixtures', DomainHelper::DOMAIN_ID_TO_LOCALE[$domainId]);
        $pricingGroupData->internalId = PricingGroup::PRICING_GROUP_PURCHASE_PRICE;

        return $this->pricingGroupFacade->create($pricingGroupData, $domainId);
    }

    /**
     * @param int $domainId
     * @return \App\Model\Pricing\Group\PricingGroup
     */
    private function createStandardPricePricingGroup(int $domainId): PricingGroup
    {
        $pricingGroupData = $this->pricingGroupDataFactory->create();
        $pricingGroupData->name = t('Běžná cena', [], 'dataFixtures', DomainHelper::DOMAIN_ID_TO_LOCALE[$domainId]);
        $pricingGroupData->discount = 0;
        $pricingGroupData->internalId = PricingGroup::PRICING_GROUP_STANDARD_PRICE;
        $pricingGroupData->minimalPrice = null;
        $pricingGroupData->calculatedFromDefault = false;

        return $this->pricingGroupFacade->create($pricingGroupData, $domainId);
    }

    /**
     * @param int $domainId
     * @return \App\Model\Pricing\Group\PricingGroup
     */
    private function createSalePricePricingGroup(int $domainId): PricingGroup
    {
        $pricingGroupData = $this->pricingGroupDataFactory->create();
        $pricingGroupData->name = t('Cena pro Výprodej', [], 'dataFixtures', DomainHelper::DOMAIN_ID_TO_LOCALE[$domainId]);
        $pricingGroupData->internalId = PricingGroup::PRICING_GROUP_SALE_PRICE;
        $pricingGroupData->minimalPrice = Money::zero();

        return $this->pricingGroupFacade->create($pricingGroupData, $domainId);
    }
}
