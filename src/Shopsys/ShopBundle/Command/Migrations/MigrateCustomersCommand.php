<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Command\Migrations;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Shopsys\ShopBundle\Command\Migrations\Transfer\CustomerWithPricingGroupsTransferMapper;
use Shopsys\ShopBundle\Component\Domain\DomainHelper;
use Shopsys\ShopBundle\Component\Rest\RestClient;
use Shopsys\ShopBundle\Component\Transfer\Exception\TransferException;
use Shopsys\ShopBundle\Model\Customer\CustomerFacade;
use Shopsys\ShopBundle\Model\Customer\Transfer\CustomerTransferResponseItemData;
use Shopsys\ShopBundle\Model\Customer\Transfer\CustomerTransferValidator;
use Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEanFacade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Validator\TraceableValidator;

class MigrateCustomersCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'shopsys:migrate:customers-with-pricing-groups';

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @var \Shopsys\ShopBundle\Model\Customer\CustomerFacade
     */
    private $customerFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Customer\Transfer\CustomerTransferValidator
     */
    private $customerTransferValidator;

    /**
     * @var \Shopsys\ShopBundle\Command\Migrations\Transfer\CustomerWithPricingGroupsTransferMapper
     */
    private $customerWithPricingGroupsTransferMapper;

    /**
     * @var \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEanFacade
     */
    private $userTransferIdAndEanFacade;

    /**
     * @var \Shopsys\ShopBundle\Component\Rest\RestClient
     */
    private $restClient;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\ShopBundle\Model\Customer\CustomerFacade $customerFacade
     * @param \Shopsys\ShopBundle\Model\Customer\Transfer\CustomerTransferValidator $customerTransferValidator
     * @param \Shopsys\ShopBundle\Command\Migrations\Transfer\CustomerWithPricingGroupsTransferMapper $customerWithPricingGroupsTransferMapper
     * @param \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEanFacade $userTransferIdAndEanFacade
     * @param \Shopsys\ShopBundle\Component\Rest\RestClient $restClient
     */
    public function __construct(
        EntityManagerInterface $em,
        CustomerFacade $customerFacade,
        CustomerTransferValidator $customerTransferValidator,
        CustomerWithPricingGroupsTransferMapper $customerWithPricingGroupsTransferMapper,
        UserTransferIdAndEanFacade $userTransferIdAndEanFacade,
        RestClient $restClient
    ) {
        parent::__construct();

        $this->em = $em;
        $this->customerFacade = $customerFacade;
        $this->customerTransferValidator = $customerTransferValidator;
        $this->customerWithPricingGroupsTransferMapper = $customerWithPricingGroupsTransferMapper;
        $this->userTransferIdAndEanFacade = $userTransferIdAndEanFacade;
        $this->restClient = $restClient;
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setDescription('Migrate customers with pricing groups');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $customersTransferItems = $this->getCustomersResponse();

        $progressBar = new ProgressBar($output, count($customersTransferItems));
        $progressBar->start();

        foreach ($customersTransferItems as $customersTransferItem) {
            $progressBar->advance();
            $this->em->beginTransaction();
            try {
                $this->importCustomer($customersTransferItem);
                $this->em->commit();
            } catch (TransferException $transferException) {
                $output->writeln('!!! validation error - ' . $transferException->getMessage());
                $this->em->rollback();
            } catch (Exception $ex) {
                $output->writeln('!!! error - ' . $ex->getMessage());

                if ($this->em->isOpen()) {
                    $this->em->rollback();
                }
            } finally {
                $this->em->clear();

                // Application in DEV mode uses TraceableValidator for validation. TraceableValidator saves data from
                // validation in memory, so it can consume quite a lot of memory, which leads to transfer crash
                if ($this->customerTransferValidator instanceof TraceableValidator) {
                    $this->customerTransferValidator->reset();
                }
            }
        }

        $progressBar->finish();
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\Transfer\CustomerTransferResponseItemData $customersTransferItem
     */
    private function importCustomer(CustomerTransferResponseItemData $customersTransferItem): void
    {
        if ($customersTransferItem->getEmail() === null) {
            return;
        }

        $customer = $this->customerFacade->findUserByEmailAndDomain(
            $customersTransferItem->getEmail(),
            DomainHelper::DOMAIN_ID_BY_COUNTRY_CODE[$customersTransferItem->getCountryCode()]
        );

        $isNew = $customer === null;

        if ($isNew === false) {
            return;
        }

        $this->customerTransferValidator->validate($customersTransferItem, $isNew);
        $customerData = $this->customerWithPricingGroupsTransferMapper->mapTransferDataToCustomerData($customersTransferItem, $customer);
        /** @var \Shopsys\ShopBundle\Model\Customer\User $customer */
        $customer = $this->customerFacade->create($customerData);
        $customer->markAsExported();
        $this->em->flush($customer);

        $this->userTransferIdAndEanFacade->saveTransferIdsAndEans($customer, $customersTransferItem->getEans(), $customersTransferItem->getDataIdentifier());

        unset($customer, $customerData);
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Customer\Transfer\CustomerTransferResponseItemData[]
     */
    private function getCustomersResponse(): array
    {
        $restResponse = $this->restClient->get('/api/Eshop/Customers');

        $restResponseData = $restResponse->getData();
        $transferDataItems = [];
        foreach ($restResponseData as $restData) {
            $transferDataItems[] = new CustomerTransferResponseItemData($restData);
        }

        return $transferDataItems;
    }
}
