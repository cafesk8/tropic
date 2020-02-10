<?php

declare(strict_types=1);

namespace App\Command\Migrations;

use App\Command\Migrations\DataProvider\GermanCustomersWithNewsletterSubscriptionDataProvider;
use App\Command\Migrations\Transfer\CustomerWithPricingGroupsTransferMapper;
use App\Component\Domain\DomainHelper;
use App\Component\Rest\MultidomainRestClient;
use App\Component\Transfer\Exception\TransferException;
use App\Model\Customer\Transfer\CustomerTransferResponseItemData;
use App\Model\Customer\Transfer\CustomerTransferValidator;
use App\Model\Customer\TransferIds\UserTransferIdFacade;
use App\Model\Customer\User\CustomerUserFacade;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Shopsys\FrameworkBundle\Model\Newsletter\NewsletterFacade;
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
     * @var \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator
     */
    private $em;

    /**
     * @var \App\Model\Customer\User\CustomerUserFacade
     */
    private $customerUserFacade;

    /**
     * @var \App\Model\Customer\Transfer\CustomerTransferValidator
     */
    private $customerTransferValidator;

    /**
     * @var \App\Command\Migrations\Transfer\CustomerWithPricingGroupsTransferMapper
     */
    private $customerWithPricingGroupsTransferMapper;

    /**
     * @var \App\Model\Customer\TransferIds\UserTransferIdFacade
     */
    private $userTransferIdFacade;

    /**
     * @var \App\Component\Rest\MultidomainRestClient
     */
    private $multidomainRestClient;

    /**
     * @var string []
     */
    private $germanCustomerEmailsWithNewsletterSubscription;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Newsletter\NewsletterFacade
     */
    private $newsletterFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
     * @param \App\Model\Customer\User\CustomerUserFacade $customerUserFacade
     * @param \App\Model\Customer\Transfer\CustomerTransferValidator $customerTransferValidator
     * @param \App\Command\Migrations\Transfer\CustomerWithPricingGroupsTransferMapper $customerWithPricingGroupsTransferMapper
     * @param \App\Model\Customer\TransferIds\UserTransferIdFacade $userTransferIdFacade
     * @param \App\Component\Rest\MultidomainRestClient $multidomainRestClient
     * @param \Shopsys\FrameworkBundle\Model\Newsletter\NewsletterFacade $newsletterFacade
     */
    public function __construct(
        EntityManagerInterface $em,
        CustomerUserFacade $customerUserFacade,
        CustomerTransferValidator $customerTransferValidator,
        CustomerWithPricingGroupsTransferMapper $customerWithPricingGroupsTransferMapper,
        UserTransferIdFacade $userTransferIdFacade,
        MultidomainRestClient $multidomainRestClient,
        NewsletterFacade $newsletterFacade
    ) {
        parent::__construct();

        $this->em = $em;
        $this->customerUserFacade = $customerUserFacade;
        $this->customerTransferValidator = $customerTransferValidator;
        $this->customerWithPricingGroupsTransferMapper = $customerWithPricingGroupsTransferMapper;
        $this->userTransferIdFacade = $userTransferIdFacade;
        $this->multidomainRestClient = $multidomainRestClient;
        $this->germanCustomerEmailsWithNewsletterSubscription = GermanCustomersWithNewsletterSubscriptionDataProvider::getGermanCustomerEmailsWithNewsletterSubscription();
        $this->newsletterFacade = $newsletterFacade;
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
    protected function execute(InputInterface $input, OutputInterface $output)
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
                if ($this->customerTransferValidator->getValidator() instanceof TraceableValidator) {
                    $this->customerTransferValidator->getValidator()->reset();
                }
            }
        }

        $progressBar->finish();

        return 0;
    }

    /**
     * @param \App\Model\Customer\Transfer\CustomerTransferResponseItemData $customersTransferItem
     */
    private function importCustomer(CustomerTransferResponseItemData $customersTransferItem): void
    {
        if ($customersTransferItem->getEmail() === null) {
            return;
        }

        if (in_array($customersTransferItem->getEmail(), $this->germanCustomerEmailsWithNewsletterSubscription, true) === false) {
            return;
        }

        $customer = $this->customerUserFacade->findCustomerUserByEmailAndDomain(
            $customersTransferItem->getEmail(),
            $customersTransferItem->getDomainId()
        );

        $isNew = $customer === null;

        if ($isNew === false) {
            return;
        }

        $this->customerTransferValidator->validate($customersTransferItem);
        $customerUserUpdateData = $this->customerWithPricingGroupsTransferMapper->mapTransferDataToCustomerData($customersTransferItem, $customer);
        /** @var \App\Model\Customer\User\CustomerUser $customer */
        $customer = $this->customerUserFacade->create($customerUserUpdateData);
        $customer->markAsExported();
        $this->em->flush($customer);

        $this->userTransferIdFacade->saveTransferIds($customer, $customersTransferItem->getEans(), $customersTransferItem->getDataIdentifier());

        $this->newsletterFacade->addSubscribedEmail($customersTransferItem->getEmail(), DomainHelper::GERMAN_DOMAIN);

        unset($customer, $customerUserUpdateData);
    }

    /**
     * @return \App\Model\Customer\Transfer\CustomerTransferResponseItemData[]
     */
    private function getCustomersResponse(): array
    {
        $restResponse = $this->multidomainRestClient->getGermanRestClient()->get('/api/Eshop/Customers');

        $restResponseData = $restResponse->getData();
        $transferDataItems = [];
        foreach ($restResponseData as $restData) {
            $customerUserUpdateData = new CustomerTransferResponseItemData($restData);
            if ($customerUserUpdateData->getDomainId() === DomainHelper::GERMAN_DOMAIN) {
                $transferDataItems[] = $customerUserUpdateData;
            }
        }

        return $transferDataItems;
    }
}
