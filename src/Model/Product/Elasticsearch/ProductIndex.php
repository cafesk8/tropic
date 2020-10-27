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
     * @param string|null $scope
     * @return array
     */
    public function getExportDataForIds(int $domainId, array $restrictToIds, ?string $scope = null): array
    {
        return $this->productExportRepository->getProductsDataForIds(
            $domainId,
            $this->domain->getDomainConfigById($domainId)->getLocale(),
            $restrictToIds,
            $scope
        );
    }

    /**
     * Copy pasted from vendor, added $scope parameter to define what data should be exported
     *
     * @inheritDoc
     */
    public function getExportDataForBatch(int $domainId, int $lastProcessedId, int $batchSize, ?string $scope = null): array
    {
        return $this->productExportRepository->getProductsData(
            $domainId,
            $this->domain->getDomainConfigById($domainId)->getLocale(),
            $lastProcessedId,
            $batchSize,
            $scope
        );
    }
}
