<?php

declare(strict_types=1);

namespace Tests\App\Functional\Model\Cart;

use App\Component\FlashMessage\FlashMessageSender;
use App\DataFixtures\Demo\ProductDataFixture;
use App\Model\Cart\CartFacade;
use App\Model\Order\Discount\CurrentOrderDiscountLevelFacade;
use App\Model\Order\Gift\OrderGiftFacade;
use App\Model\Product\Product;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Cart\CartFactory;
use Shopsys\FrameworkBundle\Model\Cart\CartRepository;
use Shopsys\FrameworkBundle\Model\Cart\Item\CartItemFactoryInterface;
use Shopsys\FrameworkBundle\Model\Cart\Watcher\CartWatcherFacade;
use Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserIdentifier;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserIdentifierFactory;
use Shopsys\FrameworkBundle\Model\Localization\TranslatableListener;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\CurrentPromoCodeFacade;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForCustomerUser;
use Shopsys\FrameworkBundle\Model\Product\ProductRepository;
use Tests\App\Test\TransactionFunctionalTestCase;

class CartFacadeTest extends TransactionFunctionalTestCase
{
    public function testAddProductToCartAddsItemsOnlyToCurrentCart()
    {
        $customerUserIdentifier = new CustomerUserIdentifier('secretSessionHash');
        $anotherCustomerUserIdentifier = new CustomerUserIdentifier('anotherSecretSessionHash');

        /** @var \App\Model\Product\Product $product */
        $product = $this->getReference(ProductDataFixture::PRODUCT_PREFIX . '1');
        $productId = $product->getId();
        $quantity = 10;

        $cartFacade = $this->createCartFacade($customerUserIdentifier);

        $cartFacade->addProduct($product, $quantity);

        $cart = $this->getCartByCustomerUserIdentifier($customerUserIdentifier);
        $cartItems = $cart->getItems();
        $product = array_pop($cartItems)->getProduct();
        $this->assertSame($productId, $product->getId(), 'Add correct product');

        $anotherCart = $this->getCartByCustomerUserIdentifier($anotherCustomerUserIdentifier);
        $this->assertSame(0, $anotherCart->getItemsCount(), 'Add only in their own cart');
    }

    public function testCannotAddUnsellableProductToCart()
    {
        /** @var \App\Model\Product\Product $product */
        $product = $this->getReference(ProductDataFixture::PRODUCT_PREFIX . '6');
        $quantity = 1;

        $customerUserIdentifier = new CustomerUserIdentifier('secretSessionHash');
        $cartFacade = $this->createCartFacade($customerUserIdentifier);

        $this->expectException('\Shopsys\FrameworkBundle\Model\Product\Exception\ProductNotFoundException');
        $cartFacade->addProduct($product, $quantity);

        $cart = $this->getCartByCustomerUserIdentifier($customerUserIdentifier);
        $cartItems = $cart->getItems();

        $this->assertEmpty($cartItems, 'Product add not suppressed');
    }

    public function testCanChangeCartItemsQuantities()
    {
        /** @var \App\Model\Product\Product $product1 */
        $product1 = $this->getReference(ProductDataFixture::PRODUCT_PREFIX . '1');
        /** @var \App\Model\Product\Product $product2 */
        $product2 = $this->getReference(ProductDataFixture::PRODUCT_PREFIX . '3');

        $customerUserIdentifier = new CustomerUserIdentifier('secretSessionHash');
        $cartFacade = $this->createCartFacade($customerUserIdentifier);

        $addProductResults1 = $cartFacade->addProduct($product1, 1);
        $this->assertCount(1, $addProductResults1);
        $cartItem1 = reset($addProductResults1)->getCartItem();
        $addProductResults2 = $cartFacade->addProduct($product2, 2);
        $this->assertCount(1, $addProductResults1);
        $cartItem2 = reset($addProductResults2)->getCartItem();

        $cartFacade->changeQuantities([
            $cartItem1->getId() => 5,
            $cartItem2->getId() => 9,
        ]);

        $cart = $this->getCartByCustomerUserIdentifier($customerUserIdentifier);
        foreach ($cart->getItems() as $cartItem) {
            if ($cartItem->getId() === $cartItem1->getId()) {
                $this->assertSame(5, $cartItem->getQuantity(), 'Correct change quantity product');
            } elseif ($cartItem->getId() === $cartItem2->getId()) {
                $this->assertSame(9, $cartItem->getQuantity(), 'Correct change quantity product');
            } else {
                $this->fail('Unexpected product in cart');
            }
        }
    }

    public function testCannotDeleteNonexistentCartItem()
    {
        $customerUserIdentifier = new CustomerUserIdentifier('secretSessionHash');

        /** @var \App\Model\Product\Product $product */
        $product = $this->getReference(ProductDataFixture::PRODUCT_PREFIX . '1');
        $quantity = 1;

        $cartFacade = $this->createCartFacade($customerUserIdentifier);
        $cartFacade->addProduct($product, $quantity);

        $cart = $this->getCartByCustomerUserIdentifier($customerUserIdentifier);
        $cartItems = $cart->getItems();
        $cartItem = array_pop($cartItems);

        $this->expectException('\Shopsys\FrameworkBundle\Model\Cart\Exception\InvalidCartItemException');
        $cartFacade->deleteCartItem($cartItem->getId() + 1);
    }

    public function testCanDeleteCartItem()
    {
        // Set currentLocale in TranslatableListener as it done in real request
        // because CartWatcherFacade works with entity translations.
        /** @var \Shopsys\FrameworkBundle\Model\Localization\TranslatableListener $translatableListener */
        $translatableListener = $this->getContainer()->get(TranslatableListener::class);
        $translatableListener->setCurrentLocale('cs');

        $customerUserIdentifier = new CustomerUserIdentifier('secretSessionHash');

        /** @var \App\Model\Product\Product $product1 */
        $product1 = $this->getReference(ProductDataFixture::PRODUCT_PREFIX . '1');
        /** @var \App\Model\Product\Product $product2 */
        $product2 = $this->getReference(ProductDataFixture::PRODUCT_PREFIX . '2');
        $quantity = 1;

        $cartFacade = $this->createCartFacade($customerUserIdentifier);
        $addProductResults1 = $cartFacade->addProduct($product1, $quantity);
        $this->assertCount(1, $addProductResults1);
        $cartItem1 = reset($addProductResults1)->getCartItem();
        $addProductResults2 = $cartFacade->addProduct($product2, $quantity);
        $this->assertCount(1, $addProductResults2);
        $cartItem2 = reset($addProductResults2)->getCartItem();

        $cartFacade->deleteCartItem($cartItem1->getId());

        $cart = $this->getCartByCustomerUserIdentifier($customerUserIdentifier);
        $cartItems = $cart->getItems();

        $this->assertArrayHasSameElements([$cartItem2], $cartItems);
    }

    /**
     * @dataProvider productCartDataProvider
     * @param int $productId
     * @param bool $cartShouldBeNull
     */
    public function testCartNotExistIfNoListableProductIsInCart(int $productId, bool $cartShouldBeNull): void
    {
        /** @var \App\Model\Cart\CartFacade $cartFacade */
        $cartFacade = $this->getContainer()->get(CartFacade::class);
        /** @var \Shopsys\FrameworkBundle\Model\Cart\Item\CartItemFactory $cartItemFactory */
        $cartItemFactory = $this->getContainer()->get(CartItemFactoryInterface::class);
        /** @var \App\Model\Product\Product $product */
        $product = $this->getReference(ProductDataFixture::PRODUCT_PREFIX . $productId);

        $cart = $cartFacade->getCartOfCurrentCustomerUserCreateIfNotExists();
        $cartItem = $cartItemFactory->create($cart, $product, 1, Money::create(10));
        $cart->addItem($cartItem);

        $this->getEntityManager()->persist($cartItem);
        $this->getEntityManager()->flush();

        $this->assertFalse($cart->isEmpty(), 'Cart should not be empty');

        $cart = $cartFacade->findCartOfCurrentCustomerUser();
        $cart = $cartFacade->checkCartModificationsAndDeleteCartIfEmpty($cart);

        if ($cartShouldBeNull) {
            $this->assertNull($cart);
        } else {
            $this->assertEquals(1, $cart->getItemsCount());
        }
    }

    public function productCartDataProvider()
    {
        return [
            ['productId' => 1, 'cartShouldBeNull' => false],
            ['productId' => 34, 'cartShouldBeNull' => true], // not listable product

        ];
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserIdentifier $customerUserIdentifier
     * @return \App\Model\Cart\CartFacade
     */
    private function createCartFacade(CustomerUserIdentifier $customerUserIdentifier)
    {
        return new CartFacade(
            $this->getEntityManager(),
            $this->getContainer()->get(CartFactory::class),
            $this->getContainer()->get(ProductRepository::class),
            $this->getCustomerUserIdentifierFactoryMock($customerUserIdentifier),
            $this->getContainer()->get(Domain::class),
            $this->getContainer()->get(CurrentCustomerUser::class),
            $this->getContainer()->get(CurrentPromoCodeFacade::class),
            $this->getContainer()->get(ProductPriceCalculationForCustomerUser::class),
            $this->getContainer()->get(CartItemFactoryInterface::class),
            $this->getContainer()->get(CartRepository::class),
            $this->getContainer()->get(CartWatcherFacade::class),
            $this->getContainer()->get(OrderGiftFacade::class),
            $this->getContainer()->get(CurrentOrderDiscountLevelFacade::class),
            $this->getContainer()->get(FlashMessageSender::class)
        );
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserIdentifier  $customerUserIdentifier
     * @return \Shopsys\FrameworkBundle\Model\Cart\Cart
     */
    private function getCartByCustomerUserIdentifier(CustomerUserIdentifier $customerUserIdentifier)
    {
        $cartFacade = $this->getContainer()->get(CartFacade::class);

        return $cartFacade->getCartByCustomerUserIdentifierCreateIfNotExists($customerUserIdentifier);
    }

    /**
     * @param array $expected
     * @param array $actual
     */
    private function assertArrayHasSameElements(array $expected, array $actual)
    {
        foreach ($expected as $expectedElement) {
            $key = array_search($expectedElement, $actual, true);

            if ($key === false) {
                $this->fail('Actual array does not contain expected element: ' . var_export($expectedElement, true));
            }

            unset($actual[$key]);
        }

        if (!empty($actual)) {
            $this->fail('Actual array contains extra elements: ' . var_export($actual, true));
        }
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserIdentifier  $customerUserIdentifier
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getCustomerUserIdentifierFactoryMock(CustomerUserIdentifier $customerUserIdentifier)
    {
        $customerUserIdentifierFactoryMock = $this->getMockBuilder(CustomerUserIdentifierFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $customerUserIdentifierFactoryMock->method('get')->willReturn($customerUserIdentifier);

        return $customerUserIdentifierFactoryMock;
    }

    /**
     * @return \App\Model\Product\Product
     */
    private function createProduct()
    {
        return $this->getReference(ProductDataFixture::PRODUCT_PREFIX . 1);
    }

    /**
     * @return \App\Model\Cart\CartFacade
     */
    private function getCartFacadeFromContainer()
    {
        return $this->getContainer()->get(CartFacade::class);
    }

    public function testCannotAddProductZeroQuantityToCart()
    {
        $product = $this->createProduct();
        $this->expectException('Shopsys\FrameworkBundle\Model\Cart\Exception\InvalidQuantityException');
        $this->getCartFacadeFromContainer()->addProduct($product, 0);
    }

    public function testCannotAddProductNegativeQuantityToCart()
    {
        $product = $this->createProduct();
        $this->expectException('Shopsys\FrameworkBundle\Model\Cart\Exception\InvalidQuantityException');
        $this->getCartFacadeFromContainer()->addProduct($product, -10);
    }

    public function testAddProductToCartMarksAddedProductAsNew()
    {
        $product = $this->createProduct();
        $results = $this->getCartFacadeFromContainer()->addProduct($product, 2);
        $this->assertCount(1, $results);
        $this->assertTrue(reset($results)->getIsNew());
    }

    public function testAddProductToCartMarksRepeatedlyAddedProductAsNotNew()
    {
        $product = $this->createProduct();
        $this->getCartFacadeFromContainer()->addProduct($product, 1);
        $results = $this->getCartFacadeFromContainer()->addProduct($product, 2);
        $this->assertCount(1, $results);
        $this->assertFalse(reset($results)->getIsNew());
    }

    public function testAddProductResultContainsAddedProductQuantity()
    {
        $product = $this->createProduct();
        $quantity = 2;
        $results = $this->getCartFacadeFromContainer()->addProduct($product, $quantity);
        $this->assertCount(1, $results);
        $this->assertSame($quantity, reset($results)->getAddedQuantity());
    }

    public function testAddProductResultDoesNotContainPreviouslyAddedProductQuantity()
    {
        $product = $this->createProduct();
        $cartFacade = $this->getCartFacadeFromContainer();
        $cartFacade->addProduct($product, 1);
        $quantity = 2;
        $results = $cartFacade->addProduct($product, $quantity);
        $this->assertCount(1, $results);
        $this->assertSame($quantity, reset($results)->getAddedQuantity());
    }

    public function testAddSaleItemIntoCart()
    {
        $saleProduct = $this->getSaleProduct();
        $cartFacade = $this->getCartFacadeFromContainer();

        $results = $cartFacade->addProduct($saleProduct, 1);

        $this->assertCount(1, $results);
        /** @var \App\Model\Cart\Item\CartItem $cartItem */
        $cartItem = reset($results)->getCartItem();
        $this->assertTrue($cartItem->isSaleItem());
    }

    public function testAddSaleItemIntoCartWithQuantityExceedingSaleStocksSplitsProductIntoTwoItems()
    {
        $saleProduct = $this->getSaleProduct();
        $saleProductQuantityInSaleStocks = $saleProduct->getRealSaleStocksQuantity();
        $cartFacade = $this->getCartFacadeFromContainer();

        $results = $cartFacade->addProduct($saleProduct, $saleProductQuantityInSaleStocks + 1);
        $cart = $cartFacade->getCartOfCurrentCustomerUserCreateIfNotExists();
        $cartItems = $cart->getItemsByProductId($saleProduct->getId());

        $this->assertCount(2, $results);
        $this->assertCount(2, $cartItems);
        $saleCartItem = $cartItems[0];
        $nonSaleCartItem = $cartItems[1];
        $this->assertTrue($saleCartItem->isSaleItem());
        $this->assertSame($saleProductQuantityInSaleStocks, $saleCartItem->getQuantity());
        $this->assertEquals(Money::create('150')->getAmount(), $saleCartItem->getWatchedPrice()->getAmount());
        $this->assertFalse($nonSaleCartItem->isSaleItem());
        $this->assertSame(1, $nonSaleCartItem->getQuantity());
        $this->assertEquals(Money::create('264')->getAmount(), $nonSaleCartItem->getWatchedPrice()->getAmount());
    }

    /**
     * @return \App\Model\Product\Product
     */
    private function getSaleProduct(): Product
    {
        /** @var \App\Model\Product\Product $saleProduct */
        $saleProduct = $this->getReference(ProductDataFixture::PRODUCT_PREFIX . 4);

        return $saleProduct;
    }
}
