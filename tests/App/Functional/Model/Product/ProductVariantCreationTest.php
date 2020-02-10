<?php

declare(strict_types=1);

namespace Tests\App\Functional\Model\Product;

use App\DataFixtures\Demo\AvailabilityDataFixture;
use App\Model\Product\ProductVariantFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Pricing\Vat\VatFacade;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\ProductData;
use Shopsys\FrameworkBundle\Model\Product\ProductDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Product\ProductFacade;
use Tests\App\Test\TransactionFunctionalTestCase;

final class ProductVariantCreationTest extends TransactionFunctionalTestCase
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\ProductVariantFacade
     */
    private $productVariantFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\ProductDataFactory
     */
    private $productDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Vat\VatFacade
     */
    private $vatFacade;

    private $domain;

    protected function setUp(): void
    {
        parent::setUp();
        $container = $this->getContainer();
        $this->productFacade = $container->get(ProductFacade::class);
        $this->productVariantFacade = $container->get(ProductVariantFacade::class);
        $this->productDataFactory = $container->get(ProductDataFactoryInterface::class);
        $this->vatFacade = $container->get(VatFacade::class);
        $this->domain = $container->get(Domain::class);
    }

    /**
     * @return array
     */
    public function variantsWithAvailabilitiesCanBeCreatedProvider(): array
    {
        return [
            [AvailabilityDataFixture::AVAILABILITY_IN_STOCK],
            [AvailabilityDataFixture::AVAILABILITY_ON_REQUEST],
            [AvailabilityDataFixture::AVAILABILITY_OUT_OF_STOCK],
            [AvailabilityDataFixture::AVAILABILITY_PREPARING],
        ];
    }

    /**
     * @dataProvider variantsWithAvailabilitiesCanBeCreatedProvider
     * @param string $availabilityReference
     */
    public function testVariantsWithAvailabilitiesCanBeCreated(string $availabilityReference): void
    {
        $productData = $this->productDataFactory->create();
        $productData->availability = $this->getReference($availabilityReference);
        $this->setVats($productData);
        $mainProduct = $this->productFacade->create($productData);
        $secondProduct = $this->productFacade->create($productData);
        $thirdProduct = $this->productFacade->create($productData);
        $mainVariant = $this->productVariantFacade->createVariant($mainProduct, [$secondProduct, $thirdProduct]);
        $this->assertTrue($mainVariant->isMainVariant());
        $this->assertContainsAllVariants([$mainProduct, $secondProduct, $thirdProduct], $mainVariant);
    }

    /**
     * @return array
     */
    public function variantsWithStockCanBeCreatedProvider(): array
    {
        return [
            [0, Product::OUT_OF_STOCK_ACTION_EXCLUDE_FROM_SALE, null],
            [100, Product::OUT_OF_STOCK_ACTION_EXCLUDE_FROM_SALE, null],
            [0, Product::OUT_OF_STOCK_ACTION_HIDE, null],
            [100, Product::OUT_OF_STOCK_ACTION_HIDE, null],
            [0, Product::OUT_OF_STOCK_ACTION_SET_ALTERNATE_AVAILABILITY, AvailabilityDataFixture::AVAILABILITY_IN_STOCK],
            [100, Product::OUT_OF_STOCK_ACTION_SET_ALTERNATE_AVAILABILITY, AvailabilityDataFixture::AVAILABILITY_IN_STOCK],
            [0, Product::OUT_OF_STOCK_ACTION_SET_ALTERNATE_AVAILABILITY, AvailabilityDataFixture::AVAILABILITY_ON_REQUEST],
            [100, Product::OUT_OF_STOCK_ACTION_SET_ALTERNATE_AVAILABILITY, AvailabilityDataFixture::AVAILABILITY_ON_REQUEST],
            [0, Product::OUT_OF_STOCK_ACTION_SET_ALTERNATE_AVAILABILITY, AvailabilityDataFixture::AVAILABILITY_OUT_OF_STOCK],
            [100, Product::OUT_OF_STOCK_ACTION_SET_ALTERNATE_AVAILABILITY, AvailabilityDataFixture::AVAILABILITY_OUT_OF_STOCK],
            [0, Product::OUT_OF_STOCK_ACTION_SET_ALTERNATE_AVAILABILITY, AvailabilityDataFixture::AVAILABILITY_PREPARING],
            [100, Product::OUT_OF_STOCK_ACTION_SET_ALTERNATE_AVAILABILITY, AvailabilityDataFixture::AVAILABILITY_PREPARING],
        ];
    }

    /**
     * @dataProvider variantsWithStockCanBeCreatedProvider
     * @param int $quantity
     * @param string $outOfStockAction
     * @param string|null $outOfStockAvailabilityReference
     */
    public function testVariantsWithStockCanBeCreated(int $quantity, string $outOfStockAction, ?string $outOfStockAvailabilityReference): void
    {
        $productData = $this->productDataFactory->create();
        $productData->usingStock = true;
        $productData->stockQuantity = $quantity;
        $productData->outOfStockAction = $outOfStockAction;
        if ($outOfStockAvailabilityReference !== null) {
            $productData->outOfStockAvailability = $this->getReference($outOfStockAvailabilityReference);
        }
        $this->setVats($productData);
        $mainProduct = $this->productFacade->create($productData);
        $secondProduct = $this->productFacade->create($productData);
        $thirdProduct = $this->productFacade->create($productData);
        $mainVariant = $this->productVariantFacade->createVariant($mainProduct, [$secondProduct, $thirdProduct]);
        $this->assertTrue($mainVariant->isMainVariant());
        $this->assertContainsAllVariants([$mainProduct, $secondProduct, $thirdProduct], $mainVariant);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product[] $expectedVariants
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $mainVariant
     */
    private function assertContainsAllVariants(array $expectedVariants, Product $mainVariant): void
    {
        $actualVariants = $mainVariant->getVariants();
        $this->assertCount(count($expectedVariants), $actualVariants);
        foreach ($expectedVariants as $expectedVariant) {
            $this->assertContains($expectedVariant, $actualVariants);
        }
        foreach ($actualVariants as $actualVariant) {
            $this->assertTrue($actualVariant->isVariant());
        }
    }

    /**
     * @param \App\Model\Product\ProductData $productData
     */
    private function setVats(ProductData $productData): void
    {
        $productVatsIndexedByDomainId = [];
        foreach ($this->domain->getAllIds() as $domainId) {
            $productVatsIndexedByDomainId[$domainId] = $this->vatFacade->getDefaultVatForDomain($domainId);
        }
        $productData->vatsIndexedByDomainId = $productVatsIndexedByDomainId;
    }
}
