<?php

declare(strict_types=1);

namespace App\Model\Product\Transfer;

use App\Component\Transfer\AbstractTransferCronModule;
use App\Component\Transfer\Pohoda\Doctrine\PohodaEntityManager;
use App\Component\Transfer\Pohoda\Product\PohodaProductExportFacade;
use App\Component\Transfer\TransferCronModuleDependency;

class ProductInfoQueueImportCronModule extends AbstractTransferCronModule
{
    public const TRANSFER_IDENTIFIER = 'import_changed_product_ids';

    /**
     * @var \App\Component\Transfer\Pohoda\Product\PohodaProductExportFacade
     */
    private $pohodaProductExportFacade;

    /**
     * @var \App\Model\Product\Transfer\ProductInfoQueueImportFacade
     */
    private $productInfoQueueImportFacade;

    /**
     * @var \App\Component\Transfer\Pohoda\Doctrine\PohodaEntityManager
     */
    private $pohodaEntityManager;

    /**
     * @param \App\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \App\Component\Transfer\Pohoda\Product\PohodaProductExportFacade $pohodaProductExportFacade
     * @param \App\Model\Product\Transfer\ProductInfoQueueImportFacade $productInfoQueueImportFacade
     * @param \App\Component\Transfer\Pohoda\Doctrine\PohodaEntityManager $pohodaEntityManager
     */
    public function __construct(
        TransferCronModuleDependency $transferCronModuleDependency,
        PohodaProductExportFacade $pohodaProductExportFacade,
        ProductInfoQueueImportFacade $productInfoQueueImportFacade,
        PohodaEntityManager $pohodaEntityManager
    ) {
        parent::__construct($transferCronModuleDependency);
        $this->pohodaProductExportFacade = $pohodaProductExportFacade;
        $this->productInfoQueueImportFacade = $productInfoQueueImportFacade;
        $this->pohodaEntityManager = $pohodaEntityManager;
    }

    /**
     * @inheritDoc
     */
    protected function getTransferIdentifier(): string
    {
        return self::TRANSFER_IDENTIFIER;
    }

    /**
     * @inheritDoc
     */
    protected function runTransfer(): bool
    {
        $transfer = $this->transferFacade->getByIdentifier(self::TRANSFER_IDENTIFIER);
        $dateTimeBeforeTransferFromPohodaServer = $this->pohodaEntityManager->getCurrentDateTimeFromPohodaDatabase();
        $this->logger->addInfo('Spuštěn import do fronty produktů', ['transferLastStartAt' => $transfer->getLastStartAt()]);

        $pohodaProductIds = $this->pohodaProductExportFacade->findPohodaProductIdsFromLastModificationDate($transfer->getLastStartAt());
        if (count($pohodaProductIds) === 0) {
            $this->logger->addInfo('Nejsou žádná data ke zpracování');
        } else {
            $this->productInfoQueueImportFacade->insertChangedPohodaProductIds($pohodaProductIds, $dateTimeBeforeTransferFromPohodaServer);
            $this->logger->addInfo('Celkem změněných produktů', ['pohodaProductIdsCount' => count($pohodaProductIds)]);
        }

        return false;
    }
}
