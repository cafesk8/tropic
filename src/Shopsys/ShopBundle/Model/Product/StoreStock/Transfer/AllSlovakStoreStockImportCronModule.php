<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\StoreStock\Transfer;

use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse;

class AllSlovakStoreStockImportCronModule extends AbstractAllOnDomainStoreStockImportCronModule
{
    public const TRANSFER_IDENTIFIER = 'import_product_store_stock_slovak';

    /**
     * @return \Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse
     */
    protected function getTransferResponse(): TransferResponse
    {
        $this->logger->addInfo('Downloading stock quantities from IS for Slovak domain');

        return $this->getTransferResponseByRestClient($this->multidomainRestClient->getSlovakRestClient());
    }
}
