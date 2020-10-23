<?php

declare(strict_types=1);

namespace App\Model\Product\Elasticsearch;

use Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductIndex as BaseProductIndex;

/**
 * @property \App\Model\Product\Elasticsearch\ProductExportRepository $productExportRepository
 * @method __construct(\Shopsys\FrameworkBundle\Component\Domain\Domain $domain, \App\Model\Product\Elasticsearch\ProductExportRepository $productExportRepository)
 */
class ProductIndex extends BaseProductIndex
{
    /**
     * It is now possible to get products data only for exporting updated stock quantities
     * @param int $domainId
     * @param array $restrictToIds
     * @param bool $stockOnly
     * @return array
     */
    public function getExportDataForIds(int $domainId, array $restrictToIds, bool $stockOnly = false): array
    {
        return $this->productExportRepository->getProductsDataForIds(
            $domainId,
            $this->domain->getDomainConfigById($domainId)->getLocale(),
            $restrictToIds,
            $stockOnly
        );
    }
}
