<?php

declare(strict_types=1);

namespace App\Model\Product\Transfer;

use App\Component\Transfer\Logger\TransferLogger;
use App\Component\Transfer\Logger\TransferLoggerFactory;
use App\Model\Product\ProductFacade;
use DateTime;

class ImageImportQueueFacade
{
    private TransferLogger $logger;

    private ImageImportQueueRepository $imageImportQueueRepository;

    private ProductFacade $productFacade;

    /**
     * @param \App\Component\Transfer\Logger\TransferLoggerFactory $transferLoggerFactory
     * @param \App\Model\Product\Transfer\ImageImportQueueRepository $imageImportQueueRepository
     * @param \App\Model\Product\ProductFacade $productFacade
     */
    public function __construct(TransferLoggerFactory $transferLoggerFactory, ImageImportQueueRepository $imageImportQueueRepository, ProductFacade $productFacade)
    {
        $this->logger = $transferLoggerFactory->getTransferLoggerByIdentifier(ProductImageImportCronModule::TRANSFER_IDENTIFIER);
        $this->imageImportQueueRepository = $imageImportQueueRepository;
        $this->productFacade = $productFacade;
    }

    /**
     * @param \DateTime|null $lastStartAt
     */
    public function updateQueue(?DateTime $lastStartAt): void
    {
        $productIds = $this->productFacade->getPohodaIdsForProductsUpdatedSince($lastStartAt);

        if (count($productIds) > 0) {
            $this->imageImportQueueRepository->insertChangedPohodaProductIds($productIds);
            $this->logger->addInfo('Do fronty pro import obrázků byly přidány nové záznamy', ['pohodaProductIdsCount' => count($productIds)]);
            $this->logger->persistTransferIssues();
        }
    }

    /**
     * @param int $limit
     * @return int[]
     */
    public function getIdsForImport(int $limit): array
    {
        return $this->imageImportQueueRepository->findChangedPohodaProductIds($limit);
    }

    /**
     * @param int[] $updatedPohodaProductIds
     */
    public function removeProductsFromQueue(array $updatedPohodaProductIds): void
    {
        $this->imageImportQueueRepository->removeUpdatedProducts($updatedPohodaProductIds);
    }
}
