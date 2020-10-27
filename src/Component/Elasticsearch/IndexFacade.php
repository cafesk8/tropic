<?php

declare(strict_types=1);

namespace App\Component\Elasticsearch;

use Shopsys\FrameworkBundle\Component\Elasticsearch\AbstractIndex;
use Shopsys\FrameworkBundle\Component\Elasticsearch\IndexDefinition;
use Shopsys\FrameworkBundle\Component\Elasticsearch\IndexFacade as BaseIndexFacade;

/**
 * @property \App\Component\Doctrine\SqlLoggerFacade $sqlLoggerFacade
 * @method __construct(\Shopsys\FrameworkBundle\Component\Elasticsearch\IndexRepository $indexRepository, \Shopsys\FrameworkBundle\Component\Console\ProgressBarFactory $progressBarFactory, \App\Component\Doctrine\SqlLoggerFacade $sqlLoggerFacade, \Doctrine\ORM\EntityManagerInterface $entityManager)
 */
class IndexFacade extends BaseIndexFacade
{
    /**
     * Method is copy pasted from vendor for exporting only product stocks
     * @param \App\Model\Product\Elasticsearch\ProductIndex $index
     * @param \Shopsys\FrameworkBundle\Component\Elasticsearch\IndexDefinition $indexDefinition
     * @param array $restrictToIds
     * @param string|null $scope
     */
    public function exportIds(AbstractIndex $index, IndexDefinition $indexDefinition, array $restrictToIds, ?string $scope = null): void
    {
        $this->sqlLoggerFacade->temporarilyDisableLogging();

        $indexAlias = $indexDefinition->getIndexAlias();
        $domainId = $indexDefinition->getDomainId();

        $chunkedIdsToExport = array_chunk($restrictToIds, $index->getExportBatchSize());

        foreach ($chunkedIdsToExport as $idsToExport) {
            // detach objects from manager to prevent memory leaks
            $this->entityManager->clear();
            $currentBatchData = $index->getExportDataForIds($domainId, $idsToExport, $scope);

            if (!empty($currentBatchData)) {
                $this->indexRepository->bulkUpdate($indexAlias, $currentBatchData);
            }

            $idsToDelete = array_values(array_diff($idsToExport, array_keys($currentBatchData)));
            if (!empty($idsToDelete)) {
                $this->indexRepository->deleteIds($indexAlias, $idsToDelete);
            }
        }

        $this->sqlLoggerFacade->reenableLogging();
    }
}
