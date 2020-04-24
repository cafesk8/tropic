<?php

declare(strict_types=1);

namespace App\Model\Customer\Transfer;

use App\Component\Transfer\Logger\TransferLoggerFactory;
use App\Component\Transfer\Pohoda\Exception\PohodaMServerException;
use App\Component\Transfer\Pohoda\MServer\MServerClient;
use App\Model\Transfer\Transfer;

class CustomerExportFacade
{
    /**
     * @var \App\Component\Transfer\Logger\TransferLogger
     */
    private $logger;

    /**
     * @var \App\Component\Transfer\Pohoda\MServer\MServerClient
     */
    private $mServerClient;

    /**
     * @var \App\Model\Customer\Transfer\PohodaCustomerMapper
     */
    private $pohodaCustomerMapper;

    /**
     * @param \App\Component\Transfer\Logger\TransferLoggerFactory $transferLoggerFactory
     * @param \App\Model\Customer\Transfer\PohodaCustomerMapper $pohodaCustomerMapper
     * @param \App\Component\Transfer\Pohoda\MServer\MServerClient $mServerClient
     */
    public function __construct(
        TransferLoggerFactory $transferLoggerFactory,
        PohodaCustomerMapper $pohodaCustomerMapper,
        MServerClient $mServerClient
    ) {
        $this->logger = $transferLoggerFactory->getTransferLoggerByIdentifier(Transfer::IDENTIFIER_EXPORT_CUSTOMERS);

        $this->mServerClient = $mServerClient;
        $this->pohodaCustomerMapper = $pohodaCustomerMapper;
    }

    /**
     * @param \App\Model\Customer\User\CustomerUser[] $customersUsers
     */
    public function processExportCustomersUsers(array $customersUsers)
    {
        $pohodaCustomers = [];

        foreach ($customersUsers as $customerUser) {
            if ($customerUser->getEmail() === 'vitek@shopsys.com') {
                $pohodaCustomers[] = $this->pohodaCustomerMapper->mapCustomerUserToPohodaCustomer($customerUser);
            }
        }

        if (count($pohodaCustomers) > 0) {
            $this->logger->addInfo('Proběhne export zákazníků v počtu: ' . count($pohodaCustomers));

            try {
                $this->mServerClient->exportAddressBook($pohodaCustomers);
            } catch (PohodaMServerException $exc) {
                $errorMessage = 'Při exportu došlo k chybě: ' . $exc->getMessage();
                $this->logger->addError($errorMessage);
            }
        } else {
            $this->logger->addInfo('Nejsou žádná data ke zpracování');
        }

        $this->logger->persistTransferIssues();
    }
}
