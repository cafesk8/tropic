<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\DataFixtures\Demo;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProductDataFactory;
use Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProductFacade;

class PromoProductDataFixture extends AbstractReferenceFixture implements DependentFixtureInterface
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProductDataFactory
     */
    protected $promoProductDataFactory;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProductFacade
     */
    private $promoProductFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProductDataFactory $promoProductDataFactory
     * @param \Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProductFacade $promoProductFacade
     */
    public function __construct(PromoProductDataFactory $promoProductDataFactory, PromoProductFacade $promoProductFacade)
    {
        $this->promoProductDataFactory = $promoProductDataFactory;
        $this->promoProductFacade = $promoProductFacade;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $promoProductData = $this->promoProductDataFactory->create();
        $promoProductData->domainId = 1;
        $promoProductData->product = $this->getReference(ProductDataFixture::PRODUCT_PREFIX . 1);
        $promoProductData->price = Money::create(123);
        $promoProductData->minimalCartPrice = Money::create(500);
        $this->promoProductFacade->create($promoProductData);

        $promoProductData = $this->promoProductDataFactory->create();
        $promoProductData->domainId = 1;
        $promoProductData->product = $this->getReference(ProductDataFixture::PRODUCT_PREFIX . 2);
        $promoProductData->price = Money::create(256);
        $promoProductData->minimalCartPrice = Money::create(1000);
        $this->promoProductFacade->create($promoProductData);
    }

    /**
     * @inheritDoc
     */
    public function getDependencies(): array
    {
        return [
            ProductDataFixture::class,
        ];
    }
}
