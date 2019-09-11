<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Command\Migrations;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Shopsys\ShopBundle\Command\Migrations\Transfer\CustomerWithPricingGroupsTransferMapper;
use Shopsys\ShopBundle\Component\Domain\DomainHelper;
use Shopsys\ShopBundle\Component\Transfer\Exception\TransferException;
use Shopsys\ShopBundle\Model\Customer\CustomerFacade;
use Shopsys\ShopBundle\Model\Customer\Transfer\CustomerTransferResponseItemData;
use Shopsys\ShopBundle\Model\Customer\Transfer\CustomerTransferValidator;
use Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\CustomerTransferService;
use Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEanFacade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Validator\TraceableValidator;

class MigrateCustomersWithPricingGroupsCommand extends Command
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
     * @var \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\CustomerTransferService
     */
    private $customerTransferService;

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
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\CustomerTransferService $customerTransferService
     * @param \Shopsys\ShopBundle\Model\Customer\CustomerFacade $customerFacade
     * @param \Shopsys\ShopBundle\Model\Customer\Transfer\CustomerTransferValidator $customerTransferValidator
     * @param \Shopsys\ShopBundle\Command\Migrations\Transfer\CustomerWithPricingGroupsTransferMapper $customerWithPricingGroupsTransferMapper
     * @param \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEanFacade $userTransferIdAndEanFacade
     */
    public function __construct(
        EntityManagerInterface $em,
        CustomerTransferService $customerTransferService,
        CustomerFacade $customerFacade,
        CustomerTransferValidator $customerTransferValidator,
        CustomerWithPricingGroupsTransferMapper $customerWithPricingGroupsTransferMapper,
        UserTransferIdAndEanFacade $userTransferIdAndEanFacade
    ) {
        parent::__construct();

        $this->em = $em;
        $this->customerTransferService = $customerTransferService;
        $this->customerFacade = $customerFacade;
        $this->customerTransferValidator = $customerTransferValidator;
        $this->customerWithPricingGroupsTransferMapper = $customerWithPricingGroupsTransferMapper;
        $this->userTransferIdAndEanFacade = $userTransferIdAndEanFacade;
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
        $customersTransferItems = $this->customerTransferService->getCustomersResponse();

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
        $customer = $this->customerFacade->findUserByEmailAndDomain(
            $customersTransferItem->getEmail(),
            DomainHelper::DOMAIN_ID_BY_COUNTRY_CODE[$customersTransferItem->getCountryCode()]
        );

        $isNew = $customer === null;

        $this->customerTransferValidator->validate($customersTransferItem, $isNew);
        $customerData = $this->customerWithPricingGroupsTransferMapper->mapTransferDataToCustomerData($customersTransferItem, $customer);

        if ($isNew === true) {
            /** @var \Shopsys\ShopBundle\Model\Customer\User $customer */
            $customer = $this->customerFacade->create($customerData);
        } else {
            $this->customerFacade->editByCustomer($customer->getId(), $customerData);
        }

        $customer->markAsExported();
        $this->em->flush($customer);

        $this->userTransferIdAndEanFacade->saveTransferIdsAndEans($customer, $customersTransferItem->getEans(), $customersTransferItem->getDataIdentifier());

        unset($customer, $customerData);
    }
}
