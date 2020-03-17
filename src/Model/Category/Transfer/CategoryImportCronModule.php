<?php

declare(strict_types=1);

namespace App\Model\Category\Transfer;

use App\Component\Transfer\AbstractTransferCronModule;
use App\Component\Transfer\TransferCronModuleDependency;

class CategoryImportCronModule extends AbstractTransferCronModule
{
    public const TRANSFER_IDENTIFIER = 'import_categories';

    /**
     * @var \App\Model\Category\Transfer\CategoryImportFacade
     */
    private $categoryImportFacade;

    /**
     * @param \App\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \App\Model\Category\Transfer\CategoryImportFacade $categoryImportFacade
     */
    public function __construct(
        TransferCronModuleDependency $transferCronModuleDependency,
        CategoryImportFacade $categoryImportFacade
    ) {
        parent::__construct($transferCronModuleDependency);

        $this->categoryImportFacade = $categoryImportFacade;
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
        return $this->categoryImportFacade->processImport($this->logger);
    }
}
