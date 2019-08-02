<?php

declare(strict_types=1);

namespace Tests\ShopBundle\Acceptance\acceptance;

use Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\LayoutPage;
use Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\RegistrationPage;
use Tests\ShopBundle\Test\Codeception\AcceptanceTester;

class CustomerRegistrationCest
{
    const MINIMUM_FORM_SUBMIT_WAIT_TIME = 10;

    /**
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\RegistrationPage $registrationPage
     * @param \Tests\ShopBundle\Test\Codeception\AcceptanceTester $me
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\LayoutPage $layoutPage
     */
    public function testSuccessfulRegistration(
        RegistrationPage $registrationPage,
        AcceptanceTester $me,
        LayoutPage $layoutPage
    ) {
        $me->wantTo('successfully register new customer');
        $me->amOnPage('/');
        $layoutPage->clickOnRegistration();
        $registrationPage->register('Roman', 'Štěpánek', 'no-reply.16@shopsys.com', 'user123', 'user123');
        $me->wait(self::MINIMUM_FORM_SUBMIT_WAIT_TIME);
        $me->see('Byli jste úspěšně zaregistrováni');
        $me->see('Roman Štěpánek');
        $me->see('Odhlásit se');
    }

    /**
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\RegistrationPage $registrationPage
     * @param \Tests\ShopBundle\Test\Codeception\AcceptanceTester $me
     */
    public function testAlreadyUsedEmail(RegistrationPage $registrationPage, AcceptanceTester $me)
    {
        $me->wantTo('use already used email while registration');
        $me->amOnPage('/registrace/');
        $registrationPage->register('Roman', 'Štěpánek', 'no-reply@shopsys.com', 'user123', 'user123');
        $registrationPage->seeEmailError('Tento e-mail je již registrován');
    }

    /**
     * @param \Tests\ShopBundle\Acceptance\acceptance\PageObject\Front\RegistrationPage $registrationPage
     * @param \Tests\ShopBundle\Test\Codeception\AcceptanceTester $me
     */
    public function testPasswordMismatch(RegistrationPage $registrationPage, AcceptanceTester $me)
    {
        $me->wantTo('use mismatching passwords while registration');
        $me->amOnPage('/registrace/');
        $registrationPage->register('Roman', 'Štěpánek', 'no-reply.16@shopsys.com', 'user123', 'missmatchingPassword');
        $registrationPage->seePasswordError('Hesla se neshodují');
    }
}
