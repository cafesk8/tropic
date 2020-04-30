<?php

declare(strict_types=1);

namespace App\DataFixtures\Demo;

use App\Model\Product\ProductGift\ProductGiftDataFactory;
use App\Model\Product\ProductGift\ProductGiftFacade;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;

class ProductGiftDataFixture extends AbstractReferenceFixture implements DependentFixtureInterface
{
    /**
     * @var \App\Model\Product\ProductGift\ProductGiftDataFactory
     */
    private $productGiftDataFactory;

    /**
     * @var \App\Model\Product\ProductGift\ProductGiftFacade
     */
    private $productGiftFacade;

    /**
     * @param \App\Model\Product\ProductGift\ProductGiftDataFactory $productGiftDataFactory
     * @param \App\Model\Product\ProductGift\ProductGiftFacade $productGiftFacade
     */
    public function __construct(ProductGiftDataFactory $productGiftDataFactory, ProductGiftFacade $productGiftFacade)
    {
        $this->productGiftDataFactory = $productGiftDataFactory;
        $this->productGiftFacade = $productGiftFacade;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $productGiftData = $this->productGiftDataFactory->create();
        $productGiftData->domainId = 1;
        $productGiftData->gift = $this->getReference(ProductDataFixture::PRODUCT_PREFIX . 1);
        $productGiftData->active = true;
        $productGiftData->products = [
            $this->getReference(ProductDataFixture::PRODUCT_PREFIX . 2),
            $this->getReference(ProductDataFixture::PRODUCT_PREFIX . 3),
            $this->getReference(ProductDataFixture::PRODUCT_PREFIX . 4),
            $this->getReference(ProductDataFixture::PRODUCT_PREFIX . 5),
        ];
        $this->productGiftFacade->create($productGiftData);

        $productGiftData = $this->productGiftDataFactory->create();
        $productGiftData->domainId = 1;
        $productGiftData->gift = $this->getReference(ProductDataFixture::PRODUCT_PREFIX . 1);
        $productGiftData->active = false;
        $productGiftData->products = [
            $this->getReference(ProductDataFixture::PRODUCT_PREFIX . 4),
            $this->getReference(ProductDataFixture::PRODUCT_PREFIX . 5),
            $this->getReference(ProductDataFixture::PRODUCT_PREFIX . 6),
        ];
        $this->productGiftFacade->create($productGiftData);

        $productGiftData = $this->productGiftDataFactory->create();
        $productGiftData->domainId = 1;
        $productGiftData->gift = $this->getReference(ProductDataFixture::PRODUCT_PREFIX . 12);
        $productGiftData->active = true;
        $productGiftData->products = [
            $this->getReference(ProductDataFixture::PRODUCT_PREFIX . 6),
            $this->getReference(ProductDataFixture::PRODUCT_PREFIX . 7),
            $this->getReference(ProductDataFixture::PRODUCT_PREFIX . 8),
        ];
        $this->productGiftFacade->create($productGiftData);

        $productGiftData = $this->productGiftDataFactory->create();
        $productGiftData->domainId = 2;
        $productGiftData->gift = $this->getReference(ProductDataFixture::PRODUCT_PREFIX . 1);
        $productGiftData->active = true;
        $productGiftData->products = [
            $this->getReference(ProductDataFixture::PRODUCT_PREFIX . 7),
            $this->getReference(ProductDataFixture::PRODUCT_PREFIX . 8),
            $this->getReference(ProductDataFixture::PRODUCT_PREFIX . 9),
        ];
        $this->productGiftFacade->create($productGiftData);
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
