<?php

declare(strict_types=1);

namespace Tests\App\Functional\Model\Product\Availability;

use App\DataFixtures\Demo\AvailabilityDataFixture;
use App\Model\Product\Product;
use App\Model\Product\ProductVariantTropicFacade;
use Doctrine\ORM\EntityManager;
use Shopsys\FrameworkBundle\Model\Product\Availability\Availability;
use Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityFacade;
use Shopsys\FrameworkBundle\Model\Product\Availability\ProductAvailabilityCalculation;
use Shopsys\FrameworkBundle\Model\Product\ProductDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Product\ProductRepository;
use Shopsys\FrameworkBundle\Model\Product\ProductSellingDeniedRecalculator;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade;
use Tests\App\Test\FunctionalTestCase;

class ProductAvailabilityCalculationTest extends FunctionalTestCase
{
    /**
     * @dataProvider getTestCalculateAvailabilityData
     * @param mixed $usingStock
     * @param mixed $stockQuantity
     * @param mixed $outOfStockAction
     * @param \Shopsys\FrameworkBundle\Model\Product\Availability\Availability|null $availability
     * @param \Shopsys\FrameworkBundle\Model\Product\Availability\Availability|null $outOfStockAvailability
     * @param \Shopsys\FrameworkBundle\Model\Product\Availability\Availability|null $defaultInStockAvailability
     * @param \Shopsys\FrameworkBundle\Model\Product\Availability\Availability|null $expectedCalculatedAvailability
     */
    public function testCalculateAvailability(
        $usingStock,
        $stockQuantity,
        $outOfStockAction,
        ?Availability $availability = null,
        ?Availability $outOfStockAvailability = null,
        ?Availability $defaultInStockAvailability = null,
        ?Availability $expectedCalculatedAvailability = null
    ) {
        $productDataFactory = $this->getContainer()->get(ProductDataFactoryInterface::class);
        $productData = $productDataFactory->create();
        $productData->usingStock = $usingStock;
        $productData->stockQuantity = $stockQuantity;
        $productData->availability = $availability;
        $productData->outOfStockAction = $outOfStockAction;
        $productData->outOfStockAvailability = $outOfStockAvailability;

        $product = Product::create($productData);

        $availabilityFacadeMock = $this->getMockBuilder(AvailabilityFacade::class)
            ->setMethods(['getDefaultInStockAvailability'])
            ->disableOriginalConstructor()
            ->getMock();
        $availabilityFacadeMock->expects($this->any())->method('getDefaultInStockAvailability')
            ->willReturn($defaultInStockAvailability);

        $productSellingDeniedRecalculatorMock = $this->createMock(ProductSellingDeniedRecalculator::class);
        $productVisibilityFacadeMock = $this->createMock(ProductVisibilityFacade::class);
        $entityManagerMock = $this->createMock(EntityManager::class);
        $productRepositoryMock = $this->createMock(ProductRepository::class);

        $productAvailabilityCalculation = new ProductAvailabilityCalculation(
            $availabilityFacadeMock,
            $productSellingDeniedRecalculatorMock,
            $productVisibilityFacadeMock,
            $entityManagerMock,
            $productRepositoryMock
        );

        $calculatedAvailability = $productAvailabilityCalculation->calculateAvailability($product);

        $this->assertSame($expectedCalculatedAvailability, $calculatedAvailability);
    }

    public function getTestCalculateAvailabilityData()
    {
        return [
            [
                'usingStock' => false,
                'stockQuantity' => null,
                'outOfStockAction' => null,
                'availability' => $this->getReference(AvailabilityDataFixture::AVAILABILITY_IN_STOCK),
                'outOfStockAvailability' => null,
                'defaultInStockAvailability' => $this->getReference(AvailabilityDataFixture::AVAILABILITY_IN_STOCK),
                'calculatedAvailability' => $this->getReference(AvailabilityDataFixture::AVAILABILITY_IN_STOCK),
            ],
            [
                'usingStock' => true,
                'stockQuantity' => null,
                'outOfStockAction' => Product::OUT_OF_STOCK_ACTION_HIDE,
                'availability' => $this->getReference(AvailabilityDataFixture::AVAILABILITY_IN_STOCK),
                'outOfStockAvailability' => null,
                'defaultInStockAvailability' => $this->getReference(AvailabilityDataFixture::AVAILABILITY_IN_STOCK),
                'calculatedAvailability' => $this->getReference(AvailabilityDataFixture::AVAILABILITY_IN_STOCK),
            ],
            [
                'usingStock' => true,
                'stockQuantity' => 5,
                'outOfStockAction' => Product::OUT_OF_STOCK_ACTION_SET_ALTERNATE_AVAILABILITY,
                'availability' => null,
                'outOfStockAvailability' => $this->getReference(AvailabilityDataFixture::AVAILABILITY_OUT_OF_STOCK),
                'defaultInStockAvailability' => $this->getReference(AvailabilityDataFixture::AVAILABILITY_IN_STOCK),
                'calculatedAvailability' => $this->getReference(AvailabilityDataFixture::AVAILABILITY_IN_STOCK),
            ],
            [
                'usingStock' => true,
                'stockQuantity' => 0,
                'outOfStockAction' => Product::OUT_OF_STOCK_ACTION_SET_ALTERNATE_AVAILABILITY,
                'availability' => null,
                'outOfStockAvailability' => $this->getReference(AvailabilityDataFixture::AVAILABILITY_OUT_OF_STOCK),
                'defaultInStockAvailability' => $this->getReference(AvailabilityDataFixture::AVAILABILITY_IN_STOCK),
                'calculatedAvailability' => $this->getReference(AvailabilityDataFixture::AVAILABILITY_OUT_OF_STOCK),
            ],
            [
                'usingStock' => true,
                'stockQuantity' => -1,
                'outOfStockAction' => Product::OUT_OF_STOCK_ACTION_SET_ALTERNATE_AVAILABILITY,
                'availability' => null,
                'outOfStockAvailability' => $this->getReference(AvailabilityDataFixture::AVAILABILITY_OUT_OF_STOCK),
                'defaultInStockAvailability' => $this->getReference(AvailabilityDataFixture::AVAILABILITY_IN_STOCK),
                'calculatedAvailability' => $this->getReference(AvailabilityDataFixture::AVAILABILITY_OUT_OF_STOCK),
            ],
        ];
    }

    public function testCalculateAvailabilityMainVariant()
    {
        $productDataFactory = $this->getContainer()->get(ProductDataFactoryInterface::class);
        $productVariantTropicFacade = $this->getContainer()->get(ProductVariantTropicFacade::class);

        $productData = $productDataFactory->create();

        $productData->variantId = '123';
        $mainVariant = Product::create($productData);
        $productVariantTropicFacade->refreshVariantStatus($mainVariant, $productData->variantId);

        $productData->availability = $this->getReference(AvailabilityDataFixture::AVAILABILITY_IN_STOCK);
        $productData->variantId = '123/1';
        $variant1 = Product::create($productData);

        $productData->availability = $this->getReference(AvailabilityDataFixture::AVAILABILITY_ON_REQUEST);
        $productData->variantId = '123/2';
        $variant2 = Product::create($productData);

        $productData->availability = $this->getReference(AvailabilityDataFixture::AVAILABILITY_OUT_OF_STOCK);
        $productData->variantId = '123/3';
        $variant3 = Product::create($productData);

        $productData->availability = $this->getReference(AvailabilityDataFixture::AVAILABILITY_PREPARING);
        $productData->variantId = '123/4';
        $variant4 = Product::create($productData);

        $variants = [$variant1, $variant2, $variant3, $variant4];
        foreach ($variants as $variant) {
            $mainVariant->addVariant($variant);
        }

        $availabilityFacadeMock = $this->createMock(AvailabilityFacade::class);
        $productSellingDeniedRecalculatorMock = $this->createMock(ProductSellingDeniedRecalculator::class);
        $productVisibilityFacadeMock = $this->createMock(ProductVisibilityFacade::class);
        $entityManagerMock = $this->createMock(EntityManager::class);

        $productRepositoryMock = $this->createMock(ProductRepository::class);
        $productRepositoryMock
            ->expects($this->atLeastOnce())
            ->method('getAtLeastSomewhereSellableVariantsByMainVariant')
            ->with($this->equalTo($mainVariant))
            ->willReturn($variants);

        $productAvailabilityCalculation = new ProductAvailabilityCalculation(
            $availabilityFacadeMock,
            $productSellingDeniedRecalculatorMock,
            $productVisibilityFacadeMock,
            $entityManagerMock,
            $productRepositoryMock
        );

        $variant1->setCalculatedAvailability($productAvailabilityCalculation->calculateAvailability($variant1));
        $variant2->setCalculatedAvailability($productAvailabilityCalculation->calculateAvailability($variant2));
        $variant3->setCalculatedAvailability($productAvailabilityCalculation->calculateAvailability($variant3));
        $variant4->setCalculatedAvailability($productAvailabilityCalculation->calculateAvailability($variant4));

        $mainVariantCalculatedAvailability = $productAvailabilityCalculation->calculateAvailability($mainVariant);

        $this->assertSame($variant1->getCalculatedAvailability(), $mainVariantCalculatedAvailability);
    }

    public function testCalculateAvailabilityMainVariantWithNoSellableVariants()
    {
        $productDataFactory = $this->getContainer()->get(ProductDataFactoryInterface::class);
        /** @var \App\Model\Product\ProductVariantTropicFacade $productTropicVariantFacade */
        $productTropicVariantFacade = $this->getContainer()->get(ProductVariantTropicFacade::class);

        $productData = $productDataFactory->create();
        $productData->variantId = '123';
        $mainVariant = Product::create($productData);
        $productTropicVariantFacade->refreshVariantStatus($mainVariant, $productData->variantId);

        $productData = $productDataFactory->create();
        $productData->availability = $this->getReference(AvailabilityDataFixture::AVAILABILITY_ON_REQUEST);
        $productData->variantId = '123/1';
        $variant = Product::create($productData);

        $availabilityFacadeMock = $this->getMockBuilder(AvailabilityFacade::class)
            ->setMethods(['getDefaultInStockAvailability'])
            ->disableOriginalConstructor()
            ->getMock();
        $defaultInStockAvailability = $this->getReference(AvailabilityDataFixture::AVAILABILITY_IN_STOCK);
        $availabilityFacadeMock
            ->expects($this->any())
            ->method('getDefaultInStockAvailability')
            ->willReturn($defaultInStockAvailability);
        $productSellingDeniedRecalculatorMock = $this->createMock(ProductSellingDeniedRecalculator::class);
        $productVisibilityFacadeMock = $this->createMock(ProductVisibilityFacade::class);
        $entityManagerMock = $this->createMock(EntityManager::class);

        $productRepositoryMock = $this->createMock(ProductRepository::class);
        $productRepositoryMock
            ->expects($this->atLeastOnce())
            ->method('getAtLeastSomewhereSellableVariantsByMainVariant')
            ->with($this->equalTo($mainVariant))
            ->willReturn([]);

        $productAvailabilityCalculation = new ProductAvailabilityCalculation(
            $availabilityFacadeMock,
            $productSellingDeniedRecalculatorMock,
            $productVisibilityFacadeMock,
            $entityManagerMock,
            $productRepositoryMock
        );

        $variant->setCalculatedAvailability($productAvailabilityCalculation->calculateAvailability($variant));

        $mainVariantCalculatedAvailability = $productAvailabilityCalculation->calculateAvailability($mainVariant);

        $this->assertSame($defaultInStockAvailability, $mainVariantCalculatedAvailability);
    }
}
