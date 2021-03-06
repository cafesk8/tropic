<?php

declare(strict_types=1);

namespace Tests\App\Functional\Model\Cart\Watcher;

use App\DataFixtures\Demo\PricingGroupDataFixture;
use App\DataFixtures\Demo\ProductDataFixture;
use App\Model\Cart\Cart;
use App\Model\Cart\CartWatcher\CartWatcher;
use App\Model\Cart\Item\CartItem;
use App\Model\Pricing\Vat\VatFacade;
use App\Model\Product\Product;
use App\Model\Product\ProductCachedAttributesFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserIdentifier;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductManualInputPriceFacade;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForCustomerUser;
use Shopsys\FrameworkBundle\Model\Product\ProductData;
use Shopsys\FrameworkBundle\Model\Product\ProductDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibility;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibilityRepository;
use Tests\App\Test\TransactionFunctionalTestCase;

class CartWatcherTest extends TransactionFunctionalTestCase
{
    public function testGetModifiedPriceItemsAndUpdatePrices()
    {
        $customerUserIdentifier = new CustomerUserIdentifier('randomString');
        $product = $this->getReference(ProductDataFixture::PRODUCT_PREFIX . '1');

        /** @var \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForCustomerUser $productPriceCalculationForUser */
        $productPriceCalculationForUser = $this->getContainer()->get(ProductPriceCalculationForCustomerUser::class);
        $productPrice = $productPriceCalculationForUser->calculatePriceForCurrentUser($product);
        $cart = new Cart($customerUserIdentifier->getCartIdentifier());
        $cartItem = new CartItem($cart, $product, 1, $productPrice->getPriceWithVat());
        $cart->addItem($cartItem);

        /** @var \App\Model\Cart\CartWatcher\CartWatcher $cartWatcher */
        $cartWatcher = $this->getContainer()->get(CartWatcher::class);

        $modifiedItems1 = $cartWatcher->getModifiedPriceItemsAndUpdatePrices($cart);
        $this->assertEmpty($modifiedItems1);

        $pricingGroup = $this->getReferenceForDomain(PricingGroupDataFixture::PRICING_GROUP_BASIC_DOMAIN, Domain::FIRST_DOMAIN_ID);

        /** @var \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductManualInputPriceFacade $manualInputPriceFacade */
        $manualInputPriceFacade = $this->getContainer()->get(ProductManualInputPriceFacade::class);
        $manualInputPriceFacade->refresh($product, $pricingGroup, Money::create(10));

        $modifiedItems2 = $cartWatcher->getModifiedPriceItemsAndUpdatePrices($cart);
        $this->assertNotEmpty($modifiedItems2);

        $modifiedItems3 = $cartWatcher->getModifiedPriceItemsAndUpdatePrices($cart);
        $this->assertEmpty($modifiedItems3);
    }

    public function testGetNotListableItemsWithItemWithoutProduct()
    {
        $customerUserIdentifier = new CustomerUserIdentifier('randomString');

        $cartItemMock = $this->getMockBuilder(CartItem::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $expectedPricingGroup = $this->getReferenceForDomain(PricingGroupDataFixture::PRICING_GROUP_BASIC_DOMAIN, Domain::FIRST_DOMAIN_ID);
        $currentCustomerMock = $this->getMockBuilder(CurrentCustomerUser::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPricingGroup'])
            ->getMock();
        $currentCustomerMock
            ->expects($this->any())
            ->method('getPricingGroup')
            ->willReturn($expectedPricingGroup);

        $cart = new Cart($customerUserIdentifier->getCartIdentifier());
        $cart->addItem($cartItemMock);

        /** @var \App\Model\Cart\CartWatcher\CartWatcher $cartWatcher */
        $cartWatcher = $this->getContainer()->get(CartWatcher::class);

        $notListableItems = $cartWatcher->getNotListableItems($cart, $currentCustomerMock);
        $this->assertCount(1, $notListableItems);
    }

    public function testGetNotListableItemsWithVisibleButNotSellableProduct()
    {
        $customerUserIdentifier = new CustomerUserIdentifier('randomString');
        $productDataFactory = $this->getContainer()->get(ProductDataFactoryInterface::class);

        $productData = $productDataFactory->create();
        $productData->name = [];
        $this->setVats($productData);
        $product = Product::create($productData);

        $cartItemMock = $this->getMockBuilder(CartItem::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProduct'])
            ->getMock();
        $cartItemMock
            ->expects($this->any())
            ->method('getProduct')
            ->willReturn($product);

        $expectedPricingGroup = $this->getReferenceForDomain(PricingGroupDataFixture::PRICING_GROUP_BASIC_DOMAIN, Domain::FIRST_DOMAIN_ID);
        $currentCustomerMock = $this->getMockBuilder(CurrentCustomerUser::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPricingGroup'])
            ->getMock();
        $currentCustomerMock
            ->expects($this->any())
            ->method('getPricingGroup')
            ->willReturn($expectedPricingGroup);

        $productVisibilityMock = $this->getMockBuilder(ProductVisibility::class)
            ->disableOriginalConstructor()
            ->setMethods(['isVisible'])
            ->getMock();
        $productVisibilityMock
            ->expects($this->any())
            ->method('isVisible')
            ->willReturn(true);

        $productVisibilityRepositoryMock = $this->getMockBuilder(ProductVisibilityRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProductVisibility'])
            ->getMock();
        $productVisibilityRepositoryMock
            ->expects($this->any())
            ->method('getProductVisibility')
            ->willReturn($productVisibilityMock);

        $productPriceCalculationForUser = $this->getContainer()->get(ProductPriceCalculationForCustomerUser::class);
        $domain = $this->getContainer()->get(Domain::class);
        $productCachedAttributesFacade = $this->getContainer()->get(ProductCachedAttributesFacade::class);

        $cartWatcher = new CartWatcher($productPriceCalculationForUser, $productVisibilityRepositoryMock, $domain, $productCachedAttributesFacade);

        $cart = new Cart($customerUserIdentifier->getCartIdentifier());
        $cart->addItem($cartItemMock);

        $notListableItems = $cartWatcher->getNotListableItems($cart, $currentCustomerMock);
        $this->assertCount(1, $notListableItems);
    }

    /**
     * @param \App\Model\Product\ProductData $productData
     */
    private function setVats(ProductData $productData): void
    {
        /** @var \Shopsys\FrameworkBundle\Component\Domain\Domain $domain */
        $domain = $this->getContainer()->get(Domain::class);
        /** @var \App\Model\Pricing\Vat\VatFacade $vatFacade */
        $vatFacade = $this->getContainer()->get(VatFacade::class);
        $productVatsIndexedByDomainId = [];
        foreach ($domain->getAllIds() as $domainId) {
            $productVatsIndexedByDomainId[$domainId] = $vatFacade->getDefaultVatForDomain($domainId);
        }
        $productData->vatsIndexedByDomainId = $productVatsIndexedByDomainId;
    }
}
