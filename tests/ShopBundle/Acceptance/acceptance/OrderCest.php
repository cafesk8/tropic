<?php

declare(strict_types=1);

namespace Tests\ShopBundle\Acceptance\acceptance;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Script\ScriptFacade;
use Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\OrderPage;
use Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\ProductListPage;
use Tests\ShopBundle\Test\Codeception\AcceptanceTester;
use Tests\ShopBundle\Test\Codeception\Helper\SymfonyHelper;

class OrderCest
{
    /**
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\ProductListPage $productListPage
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\OrderPage $orderPage
     * @param \Tests\ShopBundle\Test\Codeception\AcceptanceTester $me
     */
    public function testFormRemembersPaymentAndTransportWhenClickingBack(
        ProductListPage $productListPage,
        OrderPage $orderPage,
        AcceptanceTester $me
    ) {
        $me->wantTo('have my payment and transport remembered by order');

        $me->amOnPage('/televize-audio/');
        $productListPage->addProductToCartByName('Defender 2.0 SPK-480');
        $me->clickByText('Přejít do košíku');
        $me->clickByText('Objednat');

        $orderPage->assertTransportIsNotSelected('Česká pošta - balík do ruky');
        $orderPage->selectTransport('Česká pošta - balík do ruky');
        $orderPage->assertPaymentIsNotSelected('Dobírka');
        $orderPage->selectPayment('Dobírka');
        $me->waitForAjax();
        $me->clickByText('Pokračovat v objednávce');
        $me->clickByText('Zpět na výběr dopravy a platby');

        $orderPage->assertTransportIsSelected('Česká pošta - balík do ruky');
        $orderPage->assertPaymentIsSelected('Dobírka');
    }

    /**
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\ProductListPage $productListPage
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\OrderPage $orderPage
     * @param \Tests\ShopBundle\Test\Codeception\AcceptanceTester $me
     */
    public function testFormRemembersPaymentAndTransportWhenGoingDirectlyToUrl(
        ProductListPage $productListPage,
        OrderPage $orderPage,
        AcceptanceTester $me
    ) {
        $me->wantTo('have my payment and transport remembered by order');

        $me->amOnPage('/televize-audio/');
        $productListPage->addProductToCartByName('Defender 2.0 SPK-480');
        $me->clickByText('Přejít do košíku');
        $me->clickByText('Objednat');

        $orderPage->assertTransportIsNotSelected('Česká pošta - balík do ruky');
        $orderPage->selectTransport('Česká pošta - balík do ruky');
        $orderPage->assertPaymentIsNotSelected('Dobírka');
        $orderPage->selectPayment('Dobírka');
        $me->waitForAjax();
        $me->clickByText('Pokračovat v objednávce');
        $me->amOnPage('/objednavka/');

        $orderPage->assertTransportIsSelected('Česká pošta - balík do ruky');
        $orderPage->assertPaymentIsSelected('Dobírka');
    }

    /**
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\ProductListPage $productListPage
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\OrderPage $orderPage
     * @param \Tests\ShopBundle\Test\Codeception\AcceptanceTester $me
     */
    public function testFormRemembersFirstName(ProductListPage $productListPage, OrderPage $orderPage, AcceptanceTester $me)
    {
        $me->wantTo('have my first name remembered by order');

        $me->amOnPage('/televize-audio/');
        $productListPage->addProductToCartByName('Defender 2.0 SPK-480');
        $me->clickByText('Přejít do košíku');
        $me->clickByText('Objednat');
        $orderPage->selectTransport('Česká pošta - balík do ruky');
        $orderPage->selectPayment('Dobírka');
        $me->waitForAjax();
        $me->clickByText('Pokračovat v objednávce');

        $orderPage->fillFirstName('Jan');
        $me->clickByText('Zpět na výběr dopravy a platby');
        $me->amOnPage('/objednavka/');
        $me->clickByText('Pokračovat v objednávce');

        $orderPage->assertFirstNameIsFilled('Jan');
    }

    /**
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\ProductListPage $productListPage
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\OrderPage $orderPage
     * @param \Tests\ShopBundle\Test\Codeception\AcceptanceTester $me
     * @param \Tests\ShopBundle\Test\Codeception\Helper\SymfonyHelper $symfonyHelper
     */
    public function testOrderCanBeCompletedAndHasGoogleAnalyticsTrackingIdInSource(
        ProductListPage $productListPage,
        OrderPage $orderPage,
        AcceptanceTester $me,
        SymfonyHelper $symfonyHelper
    ) {
        $scriptFacade = $symfonyHelper->grabServiceFromContainer(ScriptFacade::class);
        $this->setGoogleAnalyticsTrackingId('GA-test', $scriptFacade);

        $this->testOrderCanBeCompleted($productListPage, $orderPage, $me);

        $me->seeInSource('GA-test');
    }

    /**
     * @param string $trackingId
     * @param \Shopsys\FrameworkBundle\Model\Script\ScriptFacade $scriptFacade
     */
    private function setGoogleAnalyticsTrackingId($trackingId, ScriptFacade $scriptFacade)
    {
        $scriptFacade->setGoogleAnalyticsTrackingId($trackingId, Domain::FIRST_DOMAIN_ID);
    }

    /**
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\ProductListPage $productListPage
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\OrderPage $orderPage
     * @param \Tests\ShopBundle\Test\Codeception\AcceptanceTester $me
     */
    private function testOrderCanBeCompleted(
        ProductListPage $productListPage,
        OrderPage $orderPage,
        AcceptanceTester $me
    ) {
        $me->amOnPage('/televize-audio/');
        $productListPage->addProductToCartByName('Defender 2.0 SPK-480');
        $me->clickByText('Přejít do košíku');
        $me->clickByText('Objednat');

        $orderPage->selectTransport('Česká pošta - balík do ruky');
        $orderPage->selectPayment('Dobírka');
        $me->waitForAjax();
        $me->clickByText('Pokračovat v objednávce');

        $orderPage->fillPersonalInfo('Karel', 'Novák', 'no-reply@shopsys.com', '123456789');
        $orderPage->fillBillingAddress('Koksární 10', 'Ostrava', '702 00');
        $orderPage->acceptLegalConditions();

        $me->clickByText('Dokončit objednávku');

        $me->see('Objednávka byla odeslána');
    }
}
