<?php

declare(strict_types=1);

namespace App\Model\Customer\Transfer;

use App\Component\Domain\DomainHelper;
use App\Component\Transfer\Response\TransferResponse;

class CzechCustomerImportCronModule extends AbstractCustomerOnDomainImportCronModule
{
    public const TRANSFER_IDENTIFIER = 'import_customers_czech';

    /**
     * @return \App\Component\Transfer\Response\TransferResponse
     */
    protected function getTransferResponse(): TransferResponse
    {
        $this->logger->addInfo('Downloading customers for domain with ID `' . DomainHelper::CZECH_DOMAIN . '`');

        return $this->getTransferResponseByRestClient($this->multidomainRestClient->getCzechRestClient());
    }
}
