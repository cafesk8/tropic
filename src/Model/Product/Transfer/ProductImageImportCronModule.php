<?php

declare(strict_types=1);

namespace App\Model\Product\Transfer;

use App\Component\Transfer\AbstractTransferCronModule;
use App\Component\Transfer\TransferCronModuleDependency;
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

    /**
     * @param \App\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \App\Model\Product\Transfer\ProductImageImportFacade $productImageImportFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductExportSubscriber $productExportSubscriber
     */
    public function __construct(
        TransferCronModuleDependency $transferCronModuleDependency,
        ProductImageImportFacade $productImageImportFacade,
        ProductExportSubscriber $productExportSubscriber
    ) {
        parent::__construct($transferCronModuleDependency);
        $this->productImageImportFacade = $productImageImportFacade;
        $this->productExportSubscriber = $productExportSubscriber;
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
        $lastFinishAt = $this->transferFacade->getByIdentifier(self::TRANSFER_IDENTIFIER)->getLastFinishAt();
        $this->productImageImportFacade->importImagesFromPohoda($lastFinishAt);
        $this->productExportSubscriber->exportScheduledRows();

        return false;
    }
}
