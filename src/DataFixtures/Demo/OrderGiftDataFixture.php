<?php

declare(strict_types=1);

namespace App\DataFixtures\Demo;

use App\Model\Order\Gift\OrderGiftDataFactory;
use App\Model\Order\Gift\OrderGiftFacade;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;

class OrderGiftDataFixture extends AbstractReferenceFixture implements DependentFixtureInterface
{
    /**
     * @var \App\Model\Order\Gift\OrderGiftFacade
     */
    protected $orderGiftFacade;

    /**
     * @var \App\Model\Order\Gift\OrderGiftDataFactory
     */
    protected $orderGiftDataFactory;

    /**
     * @param \App\Model\Order\Gift\OrderGiftFacade $orderGiftFacade
     * @param \App\Model\Order\Gift\OrderGiftDataFactory $orderGiftDataFactory
     */
    public function __construct(OrderGiftFacade $orderGiftFacade, OrderGiftDataFactory $orderGiftDataFactory)
    {
        $this->orderGiftFacade = $orderGiftFacade;
        $this->orderGiftDataFactory = $orderGiftDataFactory;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $orderGiftData500 = $this->orderGiftDataFactory->createForDomainId(Domain::FIRST_DOMAIN_ID);
        $orderGiftData500->priceLevelWithVat = Money::create(500);
        $orderGiftData500->products = [
            $this->getReference(ProductDataFixture::PRODUCT_PREFIX . 33),
            $this->getReference(ProductDataFixture::PRODUCT_PREFIX . 39),
        ];
        $this->orderGiftFacade->create($orderGiftData500);

        $orderGiftData2000 = $this->orderGiftDataFactory->createForDomainId(Domain::FIRST_DOMAIN_ID);
        $orderGiftData2000->priceLevelWithVat = Money::create(2000);
        $orderGiftData2000->products = [
            $this->getReference(ProductDataFixture::PRODUCT_PREFIX . 5),
            $this->getReference(ProductDataFixture::PRODUCT_PREFIX . 49),
            $this->getReference(ProductDataFixture::PRODUCT_PREFIX . 46),
            $this->getReference(ProductDataFixture::PRODUCT_PREFIX . 48),
        ];
        $this->orderGiftFacade->create($orderGiftData2000);
    }

    /**
     * @inheritDoc
     */
    public function getDependencies()
    {
        return [
            ProductDataFixture::class,
        ];
    }
}
