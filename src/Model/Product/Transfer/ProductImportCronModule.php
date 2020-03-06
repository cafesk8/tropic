<?php

declare(strict_types=1);

namespace App\Model\Product\Transfer;

use App\Component\Transfer\AbstractTransferCronModule;
use App\Component\Transfer\TransferCronModuleDependency;

class ProductImportCronModule extends AbstractTransferCronModule
{
    public const TRANSFER_IDENTIFIER = 'import_products';

    /**
     * @var \App\Model\Product\Transfer\ProductImportFacade
     */
    private $productImportFacade;

    /**
     * @param \App\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \App\Model\Product\Transfer\ProductImportFacade $productImportFacade
     */
    public function __construct(
        TransferCronModuleDependency $transferCronModuleDependency,
        ProductImportFacade $productImportFacade
    ) {
        parent::__construct($transferCronModuleDependency);
        $this->productImportFacade = $productImportFacade;
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
        return $this->productImportFacade->processImport();
    }
}
