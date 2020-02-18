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

    protected function setUp()
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
            'id',
            'name',
            'catnum',
            'partno',
            'ean',
            'description',
            'selling_from',
            'short_description',
            'action_price',
            'availability',
            'brand',
            'flags',
            'categories',
            'detail_url',
            'in_stock',
            'prices',
            'default_price',
            'parameters',
            'ordering_priority',
            'calculated_selling_denied',
            'selling_denied',
            'main_variant',
            'main_variant_id',
            'main_variant_group_products',
            'visibility',
            'second_distinguishing_parameter_values',
            'is_main_variant',
            'uuid',
            'unit',
            'is_using_stock',
            'stock_quantity',
            'variants',
        ];
    }
}
