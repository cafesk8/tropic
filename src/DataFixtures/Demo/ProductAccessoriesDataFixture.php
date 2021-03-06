<?php

declare(strict_types=1);

namespace App\DataFixtures\Demo;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Model\Product\ProductDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Product\ProductFacade;

class ProductAccessoriesDataFixture extends AbstractReferenceFixture implements DependentFixtureInterface
{
    /** @var \App\Model\Product\ProductDataFactory */
    protected $productDataFactory;

    /** @var \App\Model\Product\ProductFacade */
    protected $productFacade;

    /**
     * @param \App\Model\Product\ProductDataFactory $productDataFactory
     * @param \App\Model\Product\ProductFacade $productFacade
     */
    public function __construct(
        ProductDataFactoryInterface $productDataFactory,
        ProductFacade $productFacade
    ) {
        $this->productDataFactory = $productDataFactory;
        $this->productFacade = $productFacade;
    }

    /**
     * @param \Doctrine\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $product = $this->getReference(ProductDataFixture::PRODUCT_PREFIX . '1');
        /* @var $product \App\Model\Product\Product */

        $productData = $this->productDataFactory->createFromProduct($product);
        $productData->accessories = [
            $this->getReference(ProductDataFixture::PRODUCT_PREFIX . '24'),
            $this->getReference(ProductDataFixture::PRODUCT_PREFIX . '13'),
        ];
        $this->productFacade->edit($product->getId(), $productData);

        $product = $this->getReference(ProductDataFixture::PRODUCT_PREFIX . '82');
        /* @var $product \App\Model\Product\Product */

        $productData = $this->productDataFactory->createFromProduct($product);
        $productData->accessories = [
            $this->getReference(ProductDataFixture::PRODUCT_PREFIX . '77'),
            $this->getReference(ProductDataFixture::PRODUCT_PREFIX . '38'),
            $this->getReference(ProductDataFixture::PRODUCT_PREFIX . '41'),
            $this->getReference(ProductDataFixture::PRODUCT_PREFIX . '51'),
        ];
        $this->productFacade->edit($product->getId(), $productData);
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            ProductDataFixture::class,
        ];
    }
}
