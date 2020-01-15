<?php

declare(strict_types=1);

namespace Tests\ShopBundle\Functional\Model\Product\Search;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Product\Search\Export\ProductSearchExportWithFilterRepository;
use Tests\ShopBundle\Test\TransactionFunctionalTestCase;

class ProductSearchExportWithFilterRepositoryTest extends TransactionFunctionalTestCase
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Search\Export\ProductSearchExportWithFilterRepository
     */
    private $repository;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    protected function setUp()
    {
        parent::setUp();
        $this->repository = $this->getContainer()->get(ProductSearchExportWithFilterRepository::class);
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
            'brand',
            'flags',
            'categories',
            'in_stock',
            'prices',
            'parameters',
            'ordering_priority',
            'calculated_selling_denied',
        ];
    }
}
