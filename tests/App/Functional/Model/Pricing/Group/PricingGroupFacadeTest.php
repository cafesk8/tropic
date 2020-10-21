<?php

declare(strict_types=1);

namespace Tests\App\Functional\Model\Pricing\Group;

use App\DataFixtures\Demo\PricingGroupDataFixture;
use App\DataFixtures\Demo\ProductDataFixture;
use App\Model\Pricing\Group\PricingGroupData;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserFacade;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupFacade;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductCalculatedPrice;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceRecalculator;
use Tests\App\Test\TransactionFunctionalTestCase;

class PricingGroupFacadeTest extends TransactionFunctionalTestCase
{
    public function testCreate()
    {
        $em = $this->getEntityManager();
        /** @var \App\Model\Product\Product $product */
        $product = $this->getReference(ProductDataFixture::PRODUCT_PREFIX . '1');
        /** @var \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade */
        $pricingGroupFacade = $this->getContainer()->get(PricingGroupFacade::class);
        /** @var \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceRecalculator $productPriceRecalculator */
        $productPriceRecalculator = $this->getContainer()->get(ProductPriceRecalculator::class);
        $pricingGroupData = new PricingGroupData();
        $pricingGroupData->name = 'pricing_group_name';
        $domainId = 1;
        $pricingGroup = $pricingGroupFacade->create($pricingGroupData, $domainId);
        $productCalculatedPrice = $em->getRepository(ProductCalculatedPrice::class)->findOneBy([
            'product' => $product,
            'pricingGroup' => $pricingGroup,
        ]);

        $this->assertNotNull($productCalculatedPrice);
    }

    public function testDeleteAndReplace()
    {
        $em = $this->getEntityManager();
        /** @var \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade */
        $pricingGroupFacade = $this->getContainer()->get(PricingGroupFacade::class);
        /** @var \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserFacade $customerUserFacade */
        $customerUserFacade = $this->getContainer()->get(CustomerUserFacade::class);

        $pricingGroupData = new PricingGroupData();
        $pricingGroupData->name = 'name';
        $pricingGroupToDelete = $pricingGroupFacade->create($pricingGroupData, Domain::FIRST_DOMAIN_ID);
        /** @var \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup $pricingGroupToReplaceWith */
        $pricingGroupToReplaceWith = $this->getReferenceForDomain(PricingGroupDataFixture::PRICING_GROUP_BASIC_DOMAIN, Domain::FIRST_DOMAIN_ID);
        /** @var \App\Model\Customer\User\CustomerUser $customerUser */
        $customerUser = $customerUserFacade->getCustomerUserById(1);
        /** @var \App\Model\Customer\User\CustomerUserDataFactory $customerUserDataFactory */
        $customerUserDataFactory = $this->getContainer()->get(CustomerUserDataFactoryInterface::class);
        $customerUserData = $customerUserDataFactory->createFromCustomerUser($customerUser);
        /** @var \App\Model\Customer\User\CustomerUserUpdateDataFactory $customerUserUpdateDataFactory */
        $customerUserUpdateDataFactory = $this->getContainer()->get(CustomerUserUpdateDataFactoryInterface::class);

        $customerUserData->pricingGroup = $pricingGroupToDelete;
        $customerUserUpdateData = $customerUserUpdateDataFactory->create();
        $customerUserUpdateData->customerUserData = $customerUserData;
        $customerUserFacade->editByAdmin($customerUser->getId(), $customerUserUpdateData);

        $pricingGroupFacade->delete($pricingGroupToDelete->getId(), $pricingGroupToReplaceWith->getId());

        $em->refresh($customerUser);

        $this->assertEquals($pricingGroupToReplaceWith, $customerUser->getPricingGroup());
    }
}
