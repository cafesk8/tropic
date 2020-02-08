<?php

declare(strict_types=1);

namespace Tests\App\Functional\Model\Cart;

use DateTime;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Cart\CartFacade;
use Shopsys\FrameworkBundle\Model\Cart\CartFactory;
use Shopsys\FrameworkBundle\Model\Cart\CartRepository;
use Shopsys\FrameworkBundle\Model\Cart\Item\CartItemFactoryInterface;
use Shopsys\FrameworkBundle\Model\Cart\Watcher\CartWatcherFacade;
use Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserFacade;
use  Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserIdentifier;
use  Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserIdentifierFactory;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\CurrentPromoCodeFacade;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForCustomerUser;
use Shopsys\FrameworkBundle\Model\Product\ProductFacade;
use Shopsys\FrameworkBundle\Model\Product\ProductRepository;
use App\Model\Cart\Item\CartItem;
use Tests\App\Test\TransactionFunctionalTestCase;

class CartFacadeDeleteOldCartsTest extends TransactionFunctionalTestCase
{
    public function testOldUnregisteredCustomerCartGetsDeleted()
    {
         $customerUserIdentifier = $this->getCustomerUserIdentifierForUnregisteredCustomer();
        $cartFacade = $this->getCartFacadeForUnregisteredCustomer();
        $cart = $this->createCartWithProduct( $customerUserIdentifier, $cartFacade);

        $cart->setModifiedAt(new DateTime('- 61 days'));

        $em = $this->getEntityManager();
        $em->flush($cart);

        $cartFacade->deleteOldCarts();

        $this->assertCartIsDeleted($cartFacade,  $customerUserIdentifier, 'Cart should be deleted');
    }

    public function testUnregisteredCustomerCartDoesNotGetDeleted()
    {
         $customerUserIdentifier = $this->getCustomerUserIdentifierForUnregisteredCustomer();
        $cartFacade = $this->getCartFacadeForUnregisteredCustomer();
        $cart = $this->createCartWithProduct( $customerUserIdentifier, $cartFacade);

        $cart->setModifiedAt(new DateTime('- 59 days'));

        $em = $this->getEntityManager();
        $em->flush($cart);

        $cartFacade->deleteOldCarts();

        $this->assertCartIsNotDeleted($cartFacade,  $customerUserIdentifier, 'Cart should not be deleted');
    }

    public function testOldRegisteredCustomerCartGetsDeleted()
    {
         $customerUserIdentifier = $this->getCustomerUserIdentifierForRegisteredCustomer();
        $cartFacade = $this->getCartFacadeForRegisteredCustomer();
        $cart = $this->createCartWithProduct( $customerUserIdentifier, $cartFacade);

        $cart->setModifiedAt(new DateTime('- 121 days'));

        $em = $this->getEntityManager();
        $em->flush($cart);

        $cartFacade->deleteOldCarts();

        $this->assertCartIsDeleted($cartFacade,  $customerUserIdentifier, 'Cart should be deleted');
    }

    public function testRegisteredCustomerCartDoesNotGetDeletedIfItContainsRecentlyAddedItem()
    {
         $customerUserIdentifier = $this->getCustomerUserIdentifierForRegisteredCustomer();
        $cartFacade = $this->getCartFacadeForRegisteredCustomer();
        $cart = $this->createCartWithProduct( $customerUserIdentifier, $cartFacade);

        $cart->setModifiedAt(new DateTime('- 119 days'));

        $em = $this->getEntityManager();
        $em->flush($cart);

        $cartFacade->deleteOldCarts();

        $this->assertCartIsNotDeleted($cartFacade,  $customerUserIdentifier, 'Cart should not be deleted');
    }

    /**
     * @param int $productId
     * @return \App\Model\Product\Product
     */
    private function getProductById($productId)
    {
        /** @var \App\Model\Product\ProductFacade $productFacade */
        $productFacade = $this->getContainer()->get(ProductFacade::class);

        return $productFacade->getById($productId);
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Cart\CartFacade
     */
    private function getCartFacadeForRegisteredCustomer()
    {
        /** @var \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserFacade $customerUserFacade */
        $customerUserFacade = $this->getContainer()->get(CustomerUserFacade::class);

        $customerUser = $customerUserFacade->getCustomerUserById(1);

        return $this->getCartFacadeForCustomer($this->getCustomerUserIdentifierForRegisteredCustomer());
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Cart\CartFacade
     */
    private function getCartFacadeForUnregisteredCustomer()
    {
        return $this->getCartFacadeForCustomer($this->getCustomerUserIdentifierForUnregisteredCustomer());
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserIdentifier  $customerUserIdentifier
     * @return \Shopsys\FrameworkBundle\Model\Cart\CartFacade
     */
    private function getCartFacadeForCustomer(CustomerUserIdentifier  $customerUserIdentifier)
    {
        return new CartFacade(
            $this->getEntityManager(),
            $this->getContainer()->get(CartFactory::class),
            $this->getContainer()->get(ProductRepository::class),
            $this->getCustomerUserIdentifierFactoryMock( $customerUserIdentifier),
            $this->getContainer()->get(Domain::class),
            $this->getContainer()->get(CurrentCustomerUser::class),
            $this->getContainer()->get(CurrentPromoCodeFacade::class),
            $this->getContainer()->get(ProductPriceCalculationForCustomerUser::class),
            $this->getContainer()->get(CartItemFactoryInterface::class),
            $this->getContainer()->get(CartRepository::class),
            $this->getContainer()->get(CartWatcherFacade::class)
        );
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserIdentifier  $customerUserIdentifier
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getCustomerUserIdentifierFactoryMock(CustomerUserIdentifier  $customerUserIdentifier)
    {
        $CustomerUserIdentifierFactoryMock = $this->getMockBuilder( CustomerUserIdentifierFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $CustomerUserIdentifierFactoryMock->method('get')->willReturn( $customerUserIdentifier);

        return $CustomerUserIdentifierFactoryMock;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Cart\CartFacade $cartFacade
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserIdentifier  $customerUserIdentifier
     * @param string $message
     */
    private function assertCartIsDeleted(CartFacade $cartFacade, CustomerUserIdentifier  $customerUserIdentifier, $message)
    {
        $cart = $cartFacade->findCartByCustomerUserIdentifier( $customerUserIdentifier);
        $this->assertNull($cart, $message);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Cart\CartFacade $cartFacade
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserIdentifier  $customerUserIdentifier
     * @param string $message
     */
    private function assertCartIsNotDeleted(CartFacade $cartFacade, CustomerUserIdentifier  $customerUserIdentifier, $message)
    {
        $cart = $cartFacade->findCartByCustomerUserIdentifier( $customerUserIdentifier);
        $this->assertNotNull($cart, $message);
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserIdentifier
     */
    private function getCustomerUserIdentifierForRegisteredCustomer()
    {
        $customerUserFacade = $this->getContainer()->get(CustomerUserFacade::class);
        $customerUser = $customerUserFacade->getCustomerUserById(1);

        return new CustomerUserIdentifier('', $customerUser);
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserIdentifier
     */
    private function getCustomerUserIdentifierForUnregisteredCustomer()
    {
        return new CustomerUserIdentifier('randomString');
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserIdentifier  $customerUserIdentifier
     * @param \Shopsys\FrameworkBundle\Model\Cart\CartFacade $cartFacade
     * @return \Shopsys\FrameworkBundle\Model\Cart\Cart
     */
    private function createCartWithProduct(CustomerUserIdentifier  $customerUserIdentifier, CartFacade $cartFacade)
    {
        $em = $this->getEntityManager();

        $product = $this->getProductById(1);
        $cart = $cartFacade->getCartByCustomerUserIdentifierCreateIfNotExists( $customerUserIdentifier);

        $cartItem = new CartItem($cart, $product, 1, Money::zero());

        $em->persist($cartItem);
        $em->flush();

        $cart->addItem($cartItem);

        return $cart;
    }
}
