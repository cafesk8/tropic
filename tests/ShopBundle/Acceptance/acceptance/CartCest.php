<?php

namespace Tests\ShopBundle\Acceptance\acceptance;

use Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\CartBoxPage;
use Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\CartPage;
use Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\FloatingWindowPage;
use Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\HomepagePage;
use Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\ProductDetailPage;
use Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\ProductListPage;
use Tests\ShopBundle\Test\Codeception\AcceptanceTester;

class CartCest
{
    /**
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\CartPage $cartPage
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\ProductDetailPage $productDetailPage
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\CartBoxPage $cartBoxPage
     * @param \Tests\ShopBundle\Test\Codeception\AcceptanceTester $me
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\FloatingWindowPage $floatingWindowPage
     */
    public function testAddingSameProductToCartMakesSum(
        CartPage $cartPage,
        ProductDetailPage $productDetailPage,
        CartBoxPage $cartBoxPage,
        AcceptanceTester $me,
        FloatingWindowPage $floatingWindowPage
    ) {
        $me->wantTo('have more pieces of the same product as one item in cart');
        $me->amOnPage('/22-sencor-sle-22f46dm4-hello-kitty/');

        $productDetailPage->addProductIntoCart(3);
        $floatingWindowPage->closeFloatingWindow();
        $cartBoxPage->seeInCartBox('1 položka za 10 497,00 Kč');

        $productDetailPage->addProductIntoCart(3);
        $floatingWindowPage->closeFloatingWindow();
        $cartBoxPage->seeInCartBox('1 položka za 20 994,00 Kč');

        $me->amOnPage('/kosik/');

        $cartPage->assertProductQuantity('22" Sencor SLE 22F46DM4 HELLO KITTY', 6);
    }

    /**
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\CartPage $cartPage
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\ProductListPage $productListPage
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\CartBoxPage $cartBoxPage
     * @param \Tests\ShopBundle\Test\Codeception\AcceptanceTester $me
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\FloatingWindowPage $floatingWindowPage
     */
    public function testAddToCartFromProductListPage(
        CartPage $cartPage,
        ProductListPage $productListPage,
        CartBoxPage $cartBoxPage,
        AcceptanceTester $me,
        FloatingWindowPage $floatingWindowPage
    ) {
        $me->wantTo('add product to cart from product list');
        $me->amOnPage('/televize-audio/');
        $productListPage->addProductToCartByName('Defender 2.0 SPK-480', 1);
        $me->see('Do košíku bylo vloženo zboží Defender 2.0 SPK-480 (1 ks)');
        $floatingWindowPage->closeFloatingWindow();
        $cartBoxPage->seeInCartBox('1 položka');
        $me->amOnPage('/kosik/');
        $cartPage->assertProductPrice('Defender 2.0 SPK-480', '119,00 Kč');
    }

    /**
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\CartPage $cartPage
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\HomepagePage $homepagePage
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\CartBoxPage $cartBoxPage
     * @param \Tests\ShopBundle\Test\Codeception\AcceptanceTester $me
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\FloatingWindowPage $floatingWindowPage
     */
    public function testAddToCartFromHomepage(
        CartPage $cartPage,
        HomepagePage $homepagePage,
        CartBoxPage $cartBoxPage,
        AcceptanceTester $me,
        FloatingWindowPage $floatingWindowPage
    ) {
        $me->wantTo('add product to cart from homepage');
        $me->amOnPage('/');
        $homepagePage->addTopProductToCartByName('22" Sencor SLE 22F46DM4 HELLO KITTY', 1);
        $me->see('Do košíku bylo vloženo zboží');
        $floatingWindowPage->closeFloatingWindow();
        $cartBoxPage->seeInCartBox('1 položka');
        $me->amOnPage('/kosik/');
        $cartPage->assertProductPrice('22" Sencor SLE 22F46DM4 HELLO KITTY', '3 499,00 Kč');
    }

    /**
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\ProductDetailPage $productDetailPage
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\CartBoxPage $cartBoxPage
     * @param \Tests\ShopBundle\Test\Codeception\AcceptanceTester $me
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\FloatingWindowPage $floatingWindowPage
     */
    public function testAddToCartFromProductDetail(
        ProductDetailPage $productDetailPage,
        CartBoxPage $cartBoxPage,
        AcceptanceTester $me,
        FloatingWindowPage $floatingWindowPage
    ) {
        $me->wantTo('add product to cart from product detail');
        $me->amOnPage('/22-sencor-sle-22f46dm4-hello-kitty/');
        $me->see('Vložit do košíku');
        $productDetailPage->addProductIntoCart(3);
        $me->see('Do košíku bylo vloženo zboží');
        $floatingWindowPage->closeFloatingWindow();
        $cartBoxPage->seeInCartBox('1 položka za 10 497,00 Kč');
        $me->amOnPage('/kosik/');
        $me->see('22" Sencor SLE 22F46DM4 HELLO KITTY');
    }

    /**
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\CartPage $cartPage
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\ProductDetailPage $productDetailPage
     * @param \Tests\ShopBundle\Test\Codeception\AcceptanceTester $me
     */
    public function testChangeCartItemAndRecalculatePrice(
        CartPage $cartPage,
        ProductDetailPage $productDetailPage,
        AcceptanceTester $me
    ) {
        $me->wantTo('change items in cart and recalculate price');
        $me->amOnPage('/22-sencor-sle-22f46dm4-hello-kitty/');
        $me->see('Vložit do košíku');
        $productDetailPage->addProductIntoCart(3);
        $me->clickByText('Přejít do košíku');

        $cartPage->changeProductQuantity('22" Sencor SLE 22F46DM4 HELLO KITTY', 10);
        $cartPage->assertTotalPriceWithVat('34 990,00 Kč');
    }

    /**
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\CartPage $cartPage
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\ProductDetailPage $productDetailPage
     * @param \Tests\ShopBundle\Test\Codeception\AcceptanceTester $me
     */
    public function testRemovingItemsFromCart(
        CartPage $cartPage,
        ProductDetailPage $productDetailPage,
        AcceptanceTester $me
    ) {
        $me->wantTo('add some items to cart and remove them');

        $me->amOnPage('/panasonic-dmc-ft5ep/');
        $productDetailPage->addProductIntoCart();
        $me->amOnPage('/jura-impressa-j9-tft-carbon/');
        $productDetailPage->addProductIntoCart();

        $me->amOnPage('/kosik/');
        $cartPage->assertProductIsInCartByName('JURA Impressa J9 TFT Carbon');
        $cartPage->assertProductIsInCartByName('PANASONIC DMC FT5EP');

        $cartPage->removeProductFromCart('JURA Impressa J9 TFT Carbon');
        $cartPage->assertProductIsNotInCartByName('JURA Impressa J9 TFT Carbon');

        $cartPage->removeProductFromCart('PANASONIC DMC FT5EP');
        $me->see('Váš nákupní košík je bohužel prázdný.');
    }

    /**
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\CartPage $cartPage
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\CartBoxPage $cartBoxPage
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\ProductDetailPage $productDetailPage
     * @param \Tests\ShopBundle\Test\Codeception\AcceptanceTester $me
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\FloatingWindowPage $floatingWindowPage
     */
    public function testAddingDistinctProductsToCart(
        CartPage $cartPage,
        CartBoxPage $cartBoxPage,
        ProductDetailPage $productDetailPage,
        AcceptanceTester $me,
        FloatingWindowPage $floatingWindowPage
    ) {
        $me->wantTo('add distinct products to cart');

        $me->amOnPage('/22-sencor-sle-22f46dm4-hello-kitty/');
        $productDetailPage->addProductIntoCart();
        $floatingWindowPage->closeFloatingWindow();
        $cartBoxPage->seeInCartBox('1 položka za 3 499,00 Kč');

        $me->amOnPage('/canon-pixma-ip7250/');
        $productDetailPage->addProductIntoCart();
        $floatingWindowPage->closeFloatingWindow();
        $cartBoxPage->seeInCartBox('2 položky za 27 687,00 Kč');

        $me->amOnPage('/kosik/');
        $cartPage->assertProductIsInCartByName('22" Sencor SLE 22F46DM4 HELLO KITTY');
        $cartPage->assertProductIsInCartByName('Canon PIXMA iP7250');
    }

    /**
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\CartPage $cartPage
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\ProductDetailPage $productDetailPage
     * @param \Tests\ShopBundle\Test\Codeception\AcceptanceTester $me
     */
    public function testPricingInCart(
        CartPage $cartPage,
        ProductDetailPage $productDetailPage,
        AcceptanceTester $me
    ) {
        $me->wantTo('see that prices of products in cart are calculated well');

        $me->amOnPage('/aquila-aquagym-pramenita-voda-neperliva/');
        $productDetailPage->addProductIntoCart(10);
        $me->amOnPage('/stokorunova-poukazka/');
        $productDetailPage->addProductIntoCart(100);
        $me->amOnPage('/premiumcord-micro-usb-a-b-1m/');
        $productDetailPage->addProductIntoCart(75);

        $me->amOnPage('/kosik/');
        $cartPage->assertTotalPriceWithVat('17 350,00 Kč');
    }

    /**
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\CartPage $cartPage
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\ProductDetailPage $productDetailPage
     * @param \Tests\ShopBundle\Test\Codeception\AcceptanceTester $me
     */
    public function testPromoCodeFlowInCart(
        CartPage $cartPage,
        ProductDetailPage $productDetailPage,
        AcceptanceTester $me
    ) {
        $me->wantTo('see that flow of promocode in cart is correct');

        $me->amOnPage('/aquila-aquagym-pramenita-voda-neperliva/');
        $productDetailPage->addProductIntoCart();
        $me->amOnPage('/stokorunova-poukazka/');
        $productDetailPage->addProductIntoCart();

        $me->amOnPage('/kosik/');

        $cartPage->applyPromoCode('test');

        $cartPage->canSeePromoCodeRemoveButtonElement();
        $cartPage->assertTotalPriceWithVat('122,00 Kč');

        $cartPage->removePromoCode();

        $cartPage->canSeePromoCodeSubmitButtonElement();
        $cartPage->assertTotalPriceWithVat('136,00 Kč');
    }
}
