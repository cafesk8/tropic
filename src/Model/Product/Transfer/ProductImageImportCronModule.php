<?php

declare(strict_types=1);

namespace App\Model\Product\Transfer;

use App\Component\Cron\CronModuleFacade;
use App\Component\Transfer\AbstractTransferCronModule;
use App\Component\Transfer\TransferCronModuleDependency;
use App\Model\Order\Transfer\OrderExportCronModule;
use Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductExportSubscriber;

class ProductImageImportCronModule extends AbstractTransferCronModule
{
    public const TRANSFER_IDENTIFIER = 'import_product_images';

    /**
     * @var \App\Model\Product\Transfer\ProductImageImportFacade
     */
    private $productImageImportFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductExportSubscriber
     */
    private $productExportSubscriber;

    private CronModuleFacade $cronModuleFacade;

    /**
     * @param \App\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \App\Model\Product\Transfer\ProductImageImportFacade $productImageImportFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductExportSubscriber $productExportSubscriber
     * @param \App\Component\Cron\CronModuleFacade $cronModuleFacade
     */
    public function __construct(
        TransferCronModuleDependency $transferCronModuleDependency,
        ProductImageImportFacade $productImageImportFacade,
        ProductExportSubscriber $productExportSubscriber,
        CronModuleFacade $cronModuleFacade
    ) {
        parent::__construct($transferCronModuleDependency);
        $this->productImageImportFacade = $productImageImportFacade;
        $this->productExportSubscriber = $productExportSubscriber;
        $this->cronModuleFacade = $cronModuleFacade;
    }

    /**
     * @return string
     */
    protected function getTransferIdentifier(): string
    {
        return self::TRANSFER_IDENTIFIER;
    }

    /**
     * @return bool
     */
    protected function runTransfer(): bool
    {
        if ($this->cronModuleFacade->isCronModuleRunning(OrderExportCronModule::class)) {
            $this->logger->addInfo('Images transfer suspended because the orders export to Pohoda is in progress');
            $this->logger->persistTransferIssues();
            return false;
        }
        $lastStartAt = $this->transferFacade->getByIdentifier(self::TRANSFER_IDENTIFIER)->getLastStartAt();
        $this->productImageImportFacade->importImagesFromPohoda($lastStartAt);
        $this->productExportSubscriber->exportScheduledRows();

        return false;
    }
}
