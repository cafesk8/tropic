<?php

declare(strict_types=1);

namespace Tests\App\Functional\Model\Cart;

use App\DataFixtures\Demo\UnitDataFixture;
use App\Model\Product\Availability\Availability;
use App\Model\Product\Availability\AvailabilityData;
use App\Model\Product\Product;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Cart\Cart;
use Shopsys\FrameworkBundle\Model\Cart\Item\CartItem;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserIdentifier;
use Shopsys\FrameworkBundle\Model\Pricing\Vat\VatFacade;
use Shopsys\FrameworkBundle\Model\Product\ProductDataFactoryInterface;
use Tests\App\Test\TransactionFunctionalTestCase;

class CartItemTest extends TransactionFunctionalTestCase
{
    public function testIsSimilarItemAs()
    {
        $em = $this->getEntityManager();
        $productDataFactory = $this->getContainer()->get(ProductDataFactoryInterface::class);
        $domain = $this->getContainer()->get(Domain::class);
        $vatFacade = $this->getContainer()->get(VatFacade::class);

        $customerUserIdentifier = new CustomerUserIdentifier('randomString');

        $availabilityData = new AvailabilityData();
        $availabilityData->dispatchTime = 0;
        $availability = new Availability($availabilityData);
        $productData = $productDataFactory->create();
        $productData->name = [];
        $productData->availability = $availability;
        $productData->unit = $this->getReference(UnitDataFixture::UNIT_PIECES);

        $productVatsIndexedByDomainId = [];
        foreach ($domain->getAllIds() as $domainId) {
            $productVatsIndexedByDomainId[$domainId] = $vatFacade->getDefaultVatForDomain($domainId);
        }
        $productData->vatsIndexedByDomainId = $productVatsIndexedByDomainId;

        $product1 = Product::create($productData);
        $product2 = Product::create($productData);
        $em->persist($availability);
        $em->persist($product1);
        $em->persist($product2);
        $em->flush();

        $cart = new Cart($customerUserIdentifier->getCartIdentifier());

        $cartItem1 = new CartItem($cart, $product1, 1, Money::zero());
        $cartItem2 = new CartItem($cart, $product1, 3, Money::zero());
        $cartItem3 = new CartItem($cart, $product2, 1, Money::zero());

        $this->assertTrue($cartItem1->isSimilarItemAs($cartItem2));
        $this->assertFalse($cartItem1->isSimilarItemAs($cartItem3));
    }
}
