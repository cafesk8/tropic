<?php

declare(strict_types=1);

namespace App\Model\Customer\Transfer;

use App\Component\Transfer\Logger\TransferLoggerFactory;
use App\Component\Transfer\Mail\TransferMailFacade;
use App\Component\Transfer\Pohoda\Customer\PohodaCustomerValidator;
use App\Component\Transfer\Pohoda\Exception\PohodaInvalidDataException;
use App\Component\Transfer\Pohoda\Exception\PohodaMServerException;
use App\Component\Transfer\Pohoda\MServer\MServerClient;
use App\Component\Transfer\Pohoda\Response\PohodaResponse;
use App\Model\Customer\User\CustomerUserFacade;
use App\Model\Customer\User\CustomerUserUpdateDataFactory;
use App\Model\Transfer\Transfer;
use Shopsys\FrameworkBundle\Model\Mail\Exception\MailException;

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
     * @var \App\Model\Customer\User\CustomerUserFacade
     */
    private $customerUserFacade;

    /**
     * @var \App\Model\Customer\User\CustomerUserUpdateDataFactory
     */
    private $customerUserUpdateDataFactory;

    /**
     * @var \App\Component\Transfer\Pohoda\Customer\PohodaCustomerValidator
     */
    private $pohodaCustomerValidator;

    private TransferMailFacade $transferMailFacade;

    /**
     * @param \App\Component\Transfer\Logger\TransferLoggerFactory $transferLoggerFactory
     * @param \App\Model\Customer\Transfer\PohodaCustomerMapper $pohodaCustomerMapper
     * @param \App\Component\Transfer\Pohoda\MServer\MServerClient $mServerClient
     * @param \App\Model\Customer\User\CustomerUserFacade $customerUserFacade
     * @param \App\Model\Customer\User\CustomerUserUpdateDataFactory $customerUserUpdateDataFactory
     * @param \App\Component\Transfer\Pohoda\Customer\PohodaCustomerValidator $pohodaCustomerValidator
     * @param \App\Component\Transfer\Mail\TransferMailFacade $transferMailFacade
     */
    public function __construct(
        TransferLoggerFactory $transferLoggerFactory,
        PohodaCustomerMapper $pohodaCustomerMapper,
        MServerClient $mServerClient,
        CustomerUserFacade $customerUserFacade,
        CustomerUserUpdateDataFactory $customerUserUpdateDataFactory,
        PohodaCustomerValidator $pohodaCustomerValidator,
        TransferMailFacade $transferMailFacade
    ) {
        $this->logger = $transferLoggerFactory->getTransferLoggerByIdentifier(Transfer::IDENTIFIER_EXPORT_CUSTOMERS);

        $this->mServerClient = $mServerClient;
        $this->pohodaCustomerMapper = $pohodaCustomerMapper;
        $this->customerUserFacade = $customerUserFacade;
        $this->customerUserUpdateDataFactory = $customerUserUpdateDataFactory;
        $this->pohodaCustomerValidator = $pohodaCustomerValidator;
        $this->transferMailFacade = $transferMailFacade;
    }

    /**
     * @param \App\Model\Customer\User\CustomerUser[] $customersUsers
     */
    public function processExportCustomersUsers(array $customersUsers): void
    {
        $pohodaCustomers = [];

        foreach ($customersUsers as $customerUser) {
            $pohodaCustomers[] = $this->pohodaCustomerMapper->mapCustomerUserToPohodaCustomer($customerUser);
        }

        $validPohodaCustomers = [];
        foreach ($pohodaCustomers as $pohodaCustomer) {
            try {
                $this->pohodaCustomerValidator->validate($pohodaCustomer);
            } catch (PohodaInvalidDataException $exc) {
                $errorMessage = 'Zákazník s ID: ' . $pohodaCustomer->eshopId . ' nebude exportován: '
                    . $exc->getMessage();
                $this->logger->addError($errorMessage);

                continue;
            }
            $validPohodaCustomers[] = $pohodaCustomer;
        }

        if (count($validPohodaCustomers) > 0) {
            $this->logger->addInfo('Proběhne export zákazníků v počtu: ' . count($validPohodaCustomers));

            try {
                $exportedPohodaCustomers = $this->mServerClient->exportAddressBook($validPohodaCustomers);
                $this->saveExportCustomersUsersResponse($exportedPohodaCustomers);
            } catch (PohodaMServerException $exc) {
                $errorMessage = 'Při exportu došlo k chybě: ' . $exc->getMessage();
                $this->logger->addError($errorMessage);
                try {
                    $this->transferMailFacade->sendMailByErrorMessage($exc->getMessage());
                } catch (\Swift_SwiftException | MailException $mailException) {
                    $this->logger->addError('Chyba při odesílání emailové notifikace o chybě mSeveru', [
                        'exceptionMessage' => $mailException->getMessage(),
                    ]);
                }
            }
        } else {
            $this->logger->addInfo('Nejsou žádná data ke zpracování');
        }

        $this->logger->persistTransferIssues();
    }

    /**
     * @param \App\Component\Transfer\Pohoda\Customer\PohodaCustomer[] $pohodaCustomers
     */
    private function saveExportCustomersUsersResponse(array $pohodaCustomers): void
    {
        foreach ($pohodaCustomers as $pohodaCustomer) {
            if (!isset($pohodaCustomer->addressBookResponse)) {
                $errorMessage = 'Při exportu zákazníka s ID: ' . $pohodaCustomer->eshopId . ' došlo k neznámé chybě';
                $this->logger->addError($errorMessage);
                continue;
            }

            if ($pohodaCustomer->addressBookResponse->responsePackItemState !== PohodaResponse::POHODA_XML_RESPONSE_ITEM_STATE_OK) {
                $errorMessage = 'Při exportu zákazníka s ID: ' . $pohodaCustomer->eshopId . ' došlo k chybě: '
                    . $pohodaCustomer->addressBookResponse->responsePackItemNote;
                $this->logger->addError($errorMessage);
                continue;
            }

            $customerUser = $this->customerUserFacade->getCustomerUserById($pohodaCustomer->eshopId);

            $customerUserUpdateData = $this->customerUserUpdateDataFactory->createFromCustomerUser($customerUser);

            /** @var \App\Model\Customer\User\CustomerUserData $customerUserData */
            $customerUserData = $customerUserUpdateData->customerUserData;

            $customerUserData->pohodaId = $pohodaCustomer->addressBookResponse->producedDetailId;

            $this->customerUserFacade->editByCustomerUser($customerUser->getId(), $customerUserUpdateData);
        }
    }
}
