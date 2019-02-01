<?php

declare(strict_types = 1);

namespace Shopsys\ShopBundle\DataFixtures\Demo;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\ShopBundle\Model\GoPay\PaymentMethod\GoPayPaymentMethod;
use Shopsys\ShopBundle\Model\GoPay\PaymentMethod\GoPayPaymentMethodData;
use Shopsys\ShopBundle\Model\GoPay\PaymentMethod\GoPayPaymentMethodDataFactory;
use Shopsys\ShopBundle\Model\GoPay\PaymentMethod\GoPayPaymentMethodFacade;

class GoPayDataFixture extends AbstractReferenceFixture implements DependentFixtureInterface
{
    const PAYMENT_CARD_METHOD = 'gopay_payment_card_method';

    /**
     * @var \Shopsys\ShopBundle\Model\GoPay\PaymentMethod\GoPayPaymentMethodFacade
     */
    private $goPayPaymentMethodFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\GoPay\PaymentMethod\GoPayPaymentMethodDataFactory
     */
    private $goPayPaymentMethodDataFactory;

    /**
     * @param \Shopsys\ShopBundle\Model\GoPay\PaymentMethod\GoPayPaymentMethodFacade $goPayPaymentMethodFacade
     * @param \Shopsys\ShopBundle\Model\GoPay\PaymentMethod\GoPayPaymentMethodDataFactory $goPayPaymentMethodDataFactory
     */
    public function __construct(
        GoPayPaymentMethodFacade $goPayPaymentMethodFacade,
        GoPayPaymentMethodDataFactory $goPayPaymentMethodDataFactory
    ) {
        $this->goPayPaymentMethodFacade = $goPayPaymentMethodFacade;
        $this->goPayPaymentMethodDataFactory = $goPayPaymentMethodDataFactory;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $goPayPaymentMethodData = $this->goPayPaymentMethodDataFactory->create();
        $goPayPaymentMethodData->identifier = GoPayPaymentMethod::IDENTIFIER_PAYMENT_CARD;
        $goPayPaymentMethodData->name = 'Platební karta';
        $goPayPaymentMethodData->currency = $this->getReference(CurrencyDataFixture::CURRENCY_CZK);
        $goPayPaymentMethodData->imageNormalUrl = 'https://gate.gopay.cz/images/checkout/payment_card.png';
        $goPayPaymentMethodData->imageLargeUrl = 'https://gate.gopay.cz/images/checkout/payment_card@2x.png';
        $goPayPaymentMethodData->paymentGroup = 'card-payment';
        $this->createGoPayPaymentMethod(self::PAYMENT_CARD_METHOD, $goPayPaymentMethodData);
    }

    /**
     * @param string $referenceName
     * @param \Shopsys\ShopBundle\Model\GoPay\PaymentMethod\GoPayPaymentMethodData $goPayPaymentMethodData
     */
    private function createGoPayPaymentMethod(
        string $referenceName,
        GoPayPaymentMethodData $goPayPaymentMethodData
    ) {
        $goPayPaymentMethod = $this->goPayPaymentMethodFacade->create($goPayPaymentMethodData);
        $this->addReference($referenceName, $goPayPaymentMethod);
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            CurrencyDataFixture::class,
        ];
    }
}
