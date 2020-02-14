<?php

declare(strict_types=1);

namespace App\DataFixtures\Demo;

use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeFacade;

class PromoCodeDataFixture extends AbstractReferenceFixture
{
    public const PROMO_CODE_PREFIX_SUMMER = 'summer_';

    /**
     * @var \App\Model\Order\PromoCode\PromoCodeFacade
     */
    protected $promoCodeFacade;

    /**
     * @var \App\Model\Order\PromoCode\PromoCodeDataFactory
     */
    protected $promoCodeDataFactory;

    /**
     * @param \App\Model\Order\PromoCode\PromoCodeFacade $promoCodeFacade
     * @param \App\Model\Order\PromoCode\PromoCodeDataFactory $promoCodeDataFactory
     */
    public function __construct(
        PromoCodeFacade $promoCodeFacade,
        PromoCodeDataFactoryInterface $promoCodeDataFactory
    ) {
        $this->promoCodeFacade = $promoCodeFacade;
        $this->promoCodeDataFactory = $promoCodeDataFactory;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /** @var \App\Model\Order\PromoCode\PromoCodeData $promoCodeData */
        $promoCodeData = $this->promoCodeDataFactory->create();
        $promoCodeData->unlimited = false;
        $promoCodeData->domainId = 1;
        $promoCodeData->usageLimit = 100;
        $promoCodeData->code = 'promo10';
        $promoCodeData->percent = 10.0;
        $promoCodeData->useNominalDiscount = false;
        $promoCodeData->combinable = true;
        $this->promoCodeFacade->create($promoCodeData);

        $promoCodeData->unlimited = true;
        $promoCodeData->code = 'promo4';
        $promoCodeData->percent = 4.0;
        $promoCodeData->useNominalDiscount = false;
        $promoCodeData->combinable = false;
        $this->promoCodeFacade->create($promoCodeData);

        $promoCodeData->unlimited = false;
        $promoCodeData->usageLimit = 10;
        $promoCodeData->numberOfUses = 9;
        $promoCodeData->code = 'promo15';
        $promoCodeData->percent = 15.0;
        $promoCodeData->useNominalDiscount = false;
        $promoCodeData->combinable = true;
        $this->promoCodeFacade->create($promoCodeData);

        $promoCodeData->usageLimit = 1;
        $promoCodeData->quantity = 10;
        $promoCodeData->prefix = 'spring_';
        $promoCodeData->combinable = false;
        $this->promoCodeFacade->massCreate($promoCodeData);
        $promoCodeData->prefix = self::PROMO_CODE_PREFIX_SUMMER;
        $this->promoCodeFacade->massCreate($promoCodeData);
    }
}
