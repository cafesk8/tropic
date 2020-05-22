<?php

declare(strict_types=1);

namespace App\Model\Product\Transfer;

use App\Component\Transfer\AbstractTransferCronModule;
use App\Component\Transfer\TransferCronModuleDependency;

class ProductImageImportCronModule extends AbstractTransferCronModule
{
    public const TRANSFER_IDENTIFIER = 'import_product_images';

    /**
     * @var \App\Model\Product\Transfer\ProductImageImportFacade
     */
    private $productImageImportFacade;

    /**
     * @param \App\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \App\Model\Product\Transfer\ProductImageImportFacade $productImageImportFacade
     */
    public function __construct(
        TransferCronModuleDependency $transferCronModuleDependency,
        ProductImageImportFacade $productImageImportFacade
    ) {
        parent::__construct($transferCronModuleDependency);
        $this->productImageImportFacade = $productImageImportFacade;
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
        $this->productImageImportFacade->importImagesFromPohoda();

        return false;
    }
}
