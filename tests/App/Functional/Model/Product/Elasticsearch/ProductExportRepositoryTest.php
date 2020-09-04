<?php

declare(strict_types=1);

namespace Tests\App\Functional\Model\Product\Elasticsearch;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductExportRepository;
use Tests\App\Test\TransactionFunctionalTestCase;

class ProductExportRepositoryTest extends TransactionFunctionalTestCase
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductExportRepository
     */
    private $repository;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->getContainer()->get(ProductExportRepository::class);
        $this->domain = $this->getContainer()->get(Domain::class);
    }

    public function testProductDataHaveExpectedStructure(): void
    {
        $data = $this->repository->getProductsData($this->domain->getId(), $this->domain->getLocale(), 0, 10);
        $this->assertCount(10, $data);

        $structure = array_keys(reset($data));
        sort($structure);

        $expectedStructure = $this->getExpectedStructure();

        sort($expectedStructure);

        $this->assertSame($expectedStructure, $structure);
    }

    /**
     * @return string[]
     */
    private function getExpectedStructure(): array
    {
        return [
            'set_items',
            'id',
            'gifts',
            'name',
            'catnum',
            'partno',
            'ean',
            'description',
            'selling_from',
            'short_description',
            'availability',
            'delivery_days',
            'brand',
            'flags',
            'categories',
            'detail_url',
            'in_stock',
            'prices',
            'parameters',
            'ordering_priority',
            'calculated_selling_denied',
            'selling_denied',
            'main_variant_id',
            'visibility',
            'is_available_in_days',
            'is_main_variant',
            'uuid',
            'unit',
            'is_using_stock',
            'stock_quantity',
            'variants',
            'minimum_amount',
            'amount_multiplier',
            'variants_aliases',
            'variants_count',
            'prices_for_filter',
            'real_sale_stocks_quantity',
            'is_in_any_sale_stock',
            'pohoda_product_type',
            'internal_stocks_quantity',
            'external_stocks_quantity',
            'warranty',
            'variant_type',
            'recommended',
        ];
    }
}
