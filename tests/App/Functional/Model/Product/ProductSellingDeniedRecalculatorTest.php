<?php

declare(strict_types=1);

namespace Tests\App\Functional\Model\Product;

use App\DataFixtures\Demo\ProductDataFixture;
use Shopsys\FrameworkBundle\Model\Product\ProductDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Product\ProductFacade;
use Shopsys\FrameworkBundle\Model\Product\ProductSellingDeniedRecalculator;
use Tests\App\Test\TransactionFunctionalTestCase;

class ProductSellingDeniedRecalculatorTest extends TransactionFunctionalTestCase
{
    public function testCalculateSellingDeniedForProductSellableVariant()
    {
        $em = $this->getEntityManager();
        /** @var \Shopsys\FrameworkBundle\Model\Product\ProductSellingDeniedRecalculator $productSellingDeniedRecalculator */
        $productSellingDeniedRecalculator = $this->getContainer()->get(ProductSellingDeniedRecalculator::class);
        /** @var \Shopsys\FrameworkBundle\Model\Product\ProductFacade $productFacade */
        $productFacade = $this->getContainer()->get(ProductFacade::class);
        /** @var \App\Model\Product\ProductDataFactory $productDataFactory */
        $productDataFactory = $this->getContainer()->get(ProductDataFactoryInterface::class);

        /** @var \App\Model\Product\Product $variant1 */
        $variant1 = $this->getReference(ProductDataFixture::PRODUCT_PREFIX . '53');
        /** @var \App\Model\Product\Product $variant2 */
        $variant2 = $this->getReference(ProductDataFixture::PRODUCT_PREFIX . '54');
        /** @var \App\Model\Product\Product $mainVariant */
        $mainVariant = $this->getReference(ProductDataFixture::PRODUCT_PREFIX . '69');

        $variant1productData = $productDataFactory->createFromProduct($variant1);
        $variant1productData->sellingDenied = true;
        $productFacade->edit($variant1->getId(), $variant1productData);

        $productSellingDeniedRecalculator->calculateSellingDeniedForProduct($variant1);

        $em->refresh($variant1);
        $em->refresh($variant2);
        $em->refresh($mainVariant);

        $this->assertTrue($variant1->getCalculatedSellingDenied());
        $this->assertFalse($variant2->getCalculatedSellingDenied());
        $this->assertFalse($mainVariant->getCalculatedSellingDenied());
    }

    public function testCalculateSellingDeniedForProductNotSellableVariants()
    {
        $em = $this->getEntityManager();
        /** @var \Shopsys\FrameworkBundle\Model\Product\ProductSellingDeniedRecalculator $productSellingDeniedRecalculator */
        $productSellingDeniedRecalculator = $this->getContainer()->get(ProductSellingDeniedRecalculator::class);
        /** @var \Shopsys\FrameworkBundle\Model\Product\ProductFacade $productFacade */
        $productFacade = $this->getContainer()->get(ProductFacade::class);
        /** @var \App\Model\Product\ProductDataFactory $productDataFactory */
        $productDataFactory = $this->getContainer()->get(ProductDataFactoryInterface::class);

        /** @var \App\Model\Product\Product $variant1 */
        $variant1 = $this->getReference(ProductDataFixture::PRODUCT_PREFIX . '53');
        /** @var \App\Model\Product\Product $variant2 */
        $variant2 = $this->getReference(ProductDataFixture::PRODUCT_PREFIX . '54');
        /** @var \App\Model\Product\Product $mainVariant */
        $mainVariant = $this->getReference(ProductDataFixture::PRODUCT_PREFIX . '69');

        $variant1productData = $productDataFactory->createFromProduct($variant1);
        $variant1productData->sellingDenied = true;
        $productFacade->edit($variant1->getId(), $variant1productData);
        $variant2productData = $productDataFactory->createFromProduct($variant2);
        $variant2productData->sellingDenied = true;
        $productFacade->edit($variant2->getId(), $variant2productData);

        $productSellingDeniedRecalculator->calculateSellingDeniedForProduct($mainVariant);

        $em->refresh($variant1);
        $em->refresh($variant2);
        $em->refresh($mainVariant);

        $this->assertTrue($variant1->getCalculatedSellingDenied());
        $this->assertTrue($variant2->getCalculatedSellingDenied());
        $this->assertTrue($mainVariant->getCalculatedSellingDenied());
    }

    public function testCalculateSellingDeniedForProductNotSellableMainVariant()
    {
        $em = $this->getEntityManager();
        /** @var \Shopsys\FrameworkBundle\Model\Product\ProductSellingDeniedRecalculator $productSellingDeniedRecalculator */
        $productSellingDeniedRecalculator = $this->getContainer()->get(ProductSellingDeniedRecalculator::class);
        /** @var \Shopsys\FrameworkBundle\Model\Product\ProductFacade $productFacade */
        $productFacade = $this->getContainer()->get(ProductFacade::class);
        /** @var \App\Model\Product\ProductDataFactory $productDataFactory */
        $productDataFactory = $this->getContainer()->get(ProductDataFactoryInterface::class);

        /** @var \App\Model\Product\Product $variant1 */
        $variant1 = $this->getReference(ProductDataFixture::PRODUCT_PREFIX . '53');
        /** @var \App\Model\Product\Product $variant2 */
        $variant2 = $this->getReference(ProductDataFixture::PRODUCT_PREFIX . '54');
        /** @var \App\Model\Product\Product $mainVariant */
        $mainVariant = $this->getReference(ProductDataFixture::PRODUCT_PREFIX . '69');

        $mainVariantproductData = $productDataFactory->createFromProduct($mainVariant);
        $mainVariantproductData->sellingDenied = true;
        $productFacade->edit($mainVariant->getId(), $mainVariantproductData);

        $productSellingDeniedRecalculator->calculateSellingDeniedForProduct($mainVariant);

        $em->refresh($variant1);
        $em->refresh($variant2);
        $em->refresh($mainVariant);

        $this->assertTrue($variant1->getCalculatedSellingDenied());
        $this->assertTrue($variant2->getCalculatedSellingDenied());
        $this->assertTrue($mainVariant->getCalculatedSellingDenied());
    }
}
