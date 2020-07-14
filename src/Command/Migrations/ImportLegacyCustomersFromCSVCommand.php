<?php

declare(strict_types=1);

namespace App\Command\Migrations;

use App\Component\Doctrine\SqlLoggerFacade;
use App\Component\String\HashGenerator;
use App\Model\Country\CountryFacade;
use App\Model\Customer\Migration\Issue\CustomerMigrationIssue;
use App\Model\Customer\Migration\LegacyCustomerValidator;
use App\Model\Customer\User\CustomerUserData;
use App\Model\Customer\User\CustomerUserDataFactory;
use App\Model\Customer\User\CustomerUserFacade;
use App\Model\Customer\User\CustomerUserUpdateDataFactory;
use App\Model\Pricing\Group\PricingGroupFacade;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Csv\CsvReader;
use Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressDataFactory;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateData;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportLegacyCustomersFromCSVCommand extends Command
{
    private const USER_COL_INDEX_LEGACY_ID = 0;
    private const USER_COL_INDEX_FIRSTNAME = 1;
    private const USER_COL_INDEX_LASTNAME = 2;
    private const USER_COL_INDEX_EMAIL = 3;
    private const USER_COL_INDEX_PHONE = 4;
    private const USER_COL_INDEX_BILLING_STREET = 5;
    private const USER_COL_INDEX_BILLING_CITY = 6;
    private const USER_COL_INDEX_BILLING_ZIP = 7;
    private const USER_COL_INDEX_BILLING_COUNTRY = 8;
    private const USER_COL_INDEX_BILLING_COMPANY = 9;
    private const USER_COL_INDEX_BILLING_COMPANY_ICO = 10;
    private const USER_COL_INDEX_BILLING_COMPANY_DIC = 11;
    private const USER_COL_INDEX_DELIVERY_FIRSTNAME = 12;
    private const USER_COL_INDEX_DELIVERY_LASTNAME = 13;
    private const USER_COL_INDEX_DELIVERY_STREET = 14;
    private const USER_COL_INDEX_DELIVERY_CITY = 15;
    private const USER_COL_INDEX_DELIVERY_ZIP = 16;
    private const USER_COL_INDEX_DELIVERY_COUNTRY = 17;
    private const USER_COL_INDEX_DELIVERY_COMPANY = 19;
    private const USER_COL_INDEX_DOMAIN = 20;
    private const USER_COL_INDEX_PRICE_CAT = 21;

    private const CSV_SKIP_N_ROWS = 1;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    private $output;

    /**
     * @var string|null
     */
    private $sourceDirForImportedFiles;

    /**
     * @var \App\Component\Doctrine\SqlLoggerFacade
     */
    private $sqlLoggerFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator
     */
    private $entityManager;

    /**
     * @var \App\Model\Customer\User\CustomerUserFacade
     */
    private $customerUserFacade;

    /**
     * @var \App\Model\Customer\User\CustomerUserUpdateDataFactory
     */
    private $customerUserUpdateDataFactory;

    /**
     * @var \App\Model\Customer\User\CustomerUserDataFactory
     */
    private $customerUserDataFactory;

    /**
     * @var \App\Model\Pricing\Group\PricingGroupFacade
     */
    private $pricingGroupFacade;

    /**
     * @var \App\Component\String\HashGenerator
     */
    private $hashGenerator;

    /**
     * @var \App\Model\Country\CountryFacade
     */
    private $countryFacade;

    /**
     * @var \App\Model\Customer\DeliveryAddressDataFactory
     */
    private $deliveryAddressDataFactory;

    /**
     * @var array
     */
    private $skippedInvalidCustomers = [];

    /**
     * @var \App\Model\Customer\Migration\LegacyCustomerValidator
     */
    private $legacyCustomerValidator;

    /**
     * @param string $shopsysMigrationsDirPath
     * @param \App\Component\Doctrine\SqlLoggerFacade $sqlLoggerFacade
     * @param \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $entityManager
     * @param \App\Model\Customer\User\CustomerUserFacade $customerUserFacade
     * @param \App\Model\Customer\User\CustomerUserUpdateDataFactory $customerUserUpdateDataFactory
     * @param \App\Model\Customer\User\CustomerUserDataFactory $customerUserDataFactory
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \App\Component\String\HashGenerator $hashGenerator
     * @param \App\Model\Country\CountryFacade $countryFacade
     * @param \App\Model\Customer\DeliveryAddressDataFactory $deliveryAddressDataFactory
     * @param \App\Model\Customer\Migration\LegacyCustomerValidator $legacyCustomerValidator
     */
    public function __construct(
        string $shopsysMigrationsDirPath,
        SqlLoggerFacade $sqlLoggerFacade,
        EntityManagerInterface $entityManager,
        CustomerUserFacade $customerUserFacade,
        CustomerUserUpdateDataFactory $customerUserUpdateDataFactory,
        CustomerUserDataFactory $customerUserDataFactory,
        PricingGroupFacade $pricingGroupFacade,
        HashGenerator $hashGenerator,
        CountryFacade $countryFacade,
        DeliveryAddressDataFactory $deliveryAddressDataFactory,
        LegacyCustomerValidator $legacyCustomerValidator
    ) {
        parent::__construct();

        $this->sourceDirForImportedFiles = $shopsysMigrationsDirPath;
        $this->sqlLoggerFacade = $sqlLoggerFacade;
        $this->entityManager = $entityManager;
        $this->customerUserFacade = $customerUserFacade;
        $this->customerUserUpdateDataFactory = $customerUserUpdateDataFactory;
        $this->customerUserDataFactory = $customerUserDataFactory;
        $this->pricingGroupFacade = $pricingGroupFacade;
        $this->hashGenerator = $hashGenerator;

        $this->countryFacade = $countryFacade;
        $this->deliveryAddressDataFactory = $deliveryAddressDataFactory;
        $this->legacyCustomerValidator = $legacyCustomerValidator;
    }

    protected function configure(): void
    {
        $this
            ->setName('shopsys:import:legacy-users')
            ->setDescription('Migrate users (clientele) data from old shop');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

        $this->sqlLoggerFacade->temporarilyDisableLogging();

        $this->output->writeln('<info>Importing customers</info>');

        $this->importCustomers();

        $this->output->writeln('<info>Finished.</info>');

        $this->displaySkippedUsers();

        $this->sqlLoggerFacade->reenableLogging();

        return 0;
    }

    private function importCustomers(): void
    {
        $customersCsv = $this->sourceDirForImportedFiles . '/customers.csv';

        if (!is_readable($customersCsv)) {
            throw new \IOException(sprintf("Can't read file: %s", $customersCsv));
        }

        $csvReader = new CsvReader();
        $csvRows = $csvReader->getRowsFromCsv($customersCsv, ';');

        $pBar = $this->createProgressBar($this->output, $this->getNumberOfLinesInFile($customersCsv));
        $pBar->start();

        $countTotal = 0;
        $countCreated = 0;
        $countUpdated = 0;
        $countSkipped = 0;

        foreach ($csvRows as $csvRow) {
            $countTotal++;

            $pBar->advance();

            if ($countTotal <= self::CSV_SKIP_N_ROWS) {
                $countSkipped++;
                continue;
            }

            try {
                $legacyId = (int)$csvRow[self::USER_COL_INDEX_LEGACY_ID];
                $domainId = (int)$csvRow[self::USER_COL_INDEX_DOMAIN];

                $this->entityManager->beginTransaction();

                $customerUser = $this->customerUserFacade->findByLegacyId($legacyId);

                if ($customerUser !== null) {
                    $customerUserUpdateData = $this->customerUserUpdateDataFactory->createFromCustomerUser($customerUser);
                    $customerUserData = $this->customerUserDataFactory->createFromCustomerUser($customerUser);
                } else {
                    $customerUserData = $this->customerUserDataFactory->createForDomainId($domainId);
                    $customerUserData->password = $this->hashGenerator->generateHash(6);

                    $customerUserUpdateData = $this->customerUserUpdateDataFactory->create();
                    $customerUserData->pricingGroup = $this->pricingGroupFacade->getById($csvRow[self::USER_COL_INDEX_PRICE_CAT]);
                }

                $customerUserUpdateData = $this->mapCustomerUserCsvRowToCustomerUserData(
                    $csvRow,
                    $customerUserData,
                    $customerUserUpdateData
                );

                $this->legacyCustomerValidator->validate($customerUserUpdateData);

                if ($customerUser === null) {
                    $customerUser = $this->customerUserFacade->create($customerUserUpdateData);
                    $countCreated++;
                } else {
                    $customerUser = $this->customerUserFacade->editByAdmin($customerUser->getId(), $customerUserUpdateData);
                    $countUpdated++;
                }

                $this->entityManager->flush($customerUser);

                $this->entityManager->commit();
            } catch (\Exception $exc) {
                $this->setInvalidCustomer($csvRow, $exc->getMessage());
                $countSkipped++;

                $this->entityManager->rollback();
            } finally {
                $this->entityManager->clear();
            }
        }

        $this->output->writeln(PHP_EOL . sprintf('Created: %d, Updated: %d, Skipped: %d, Total: %d', $countCreated, $countUpdated, $countSkipped, $countTotal));

        $pBar->finish();
    }

    /**
     * @param array $csvRow
     * @param \App\Model\Customer\User\CustomerUserData $customerUserData
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateData $customerUserUpdateData
     * @return \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateData
     */
    private function mapCustomerUserCsvRowToCustomerUserData(
        array $csvRow,
        CustomerUserData $customerUserData,
        CustomerUserUpdateData $customerUserUpdateData
    ): CustomerUserUpdateData {
        $customerUserData->firstName = trim($csvRow[self::USER_COL_INDEX_FIRSTNAME]);
        $customerUserData->lastName = trim($csvRow[self::USER_COL_INDEX_LASTNAME]);
        $customerUserData->email = trim($csvRow[self::USER_COL_INDEX_EMAIL]);
        $customerUserData->telephone = trim($csvRow[self::USER_COL_INDEX_PHONE]);
        $customerUserData->legacyId = (int)trim($csvRow[self::USER_COL_INDEX_LEGACY_ID]);
        $customerUserData->customer = $customerUserUpdateData->customerUserData->customer;

        $this->mapBillingAddress($csvRow, $customerUserUpdateData);
        $this->mapDeliveryAddress($csvRow, $customerUserUpdateData);

        $customerUserUpdateData->customerUserData = $customerUserData;

        return $customerUserUpdateData;
    }

    /**
     * @param array $csvRow
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateData $customerUserUpdateData
     */
    private function mapBillingAddress(
        array $csvRow,
        CustomerUserUpdateData $customerUserUpdateData
    ): void {
        $billingAddressData = $customerUserUpdateData->billingAddressData;

        $billingAddressData->companyName = trim($csvRow[self::USER_COL_INDEX_BILLING_COMPANY]);
        $billingAddressData->companyNumber = trim($csvRow[self::USER_COL_INDEX_BILLING_COMPANY_ICO]);
        $billingAddressData->companyTaxNumber = trim($csvRow[self::USER_COL_INDEX_BILLING_COMPANY_DIC]);
        $billingAddressData->city = trim($csvRow[self::USER_COL_INDEX_BILLING_CITY]);
        $billingAddressData->street = trim($csvRow[self::USER_COL_INDEX_BILLING_STREET]);
        $billingAddressData->postcode = trim($csvRow[self::USER_COL_INDEX_BILLING_ZIP]);

        $billingAddressData->country = $this->countryFacade->findByCode($csvRow[self::USER_COL_INDEX_BILLING_COUNTRY]);
        $billingAddressData->companyCustomer = !empty($csvRow[self::USER_COL_INDEX_BILLING_COMPANY]);
    }

    /**
     * @param array $csvRow
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateData $customerUserUpdateData
     */
    private function mapDeliveryAddress(
        array $csvRow,
        CustomerUserUpdateData $customerUserUpdateData
    ): void {
        $deliveryAddress = $customerUserUpdateData->customerUserData->defaultDeliveryAddress;

        if ($deliveryAddress !== null) {
            $deliveryAddressData = $this->deliveryAddressDataFactory->createFromDeliveryAddress($deliveryAddress);
        } else {
            $deliveryAddressData = $customerUserUpdateData->deliveryAddressData;
        }

        $deliveryAddressData->addressFilled = true;
        $deliveryAddressData->companyName = trim($csvRow[self::USER_COL_INDEX_DELIVERY_COMPANY]);
        $deliveryAddressData->firstName = trim($csvRow[self::USER_COL_INDEX_DELIVERY_FIRSTNAME]);
        $deliveryAddressData->city = trim($csvRow[self::USER_COL_INDEX_DELIVERY_CITY]);
        $deliveryAddressData->postcode = trim($csvRow[self::USER_COL_INDEX_DELIVERY_ZIP]);
        $deliveryAddressData->lastName = trim($csvRow[self::USER_COL_INDEX_DELIVERY_LASTNAME]);

        $deliveryAddressData->street = trim($csvRow[self::USER_COL_INDEX_DELIVERY_STREET]);
        $deliveryAddressData->country = $this->countryFacade->findByCode($csvRow[self::USER_COL_INDEX_DELIVERY_COUNTRY]);

        if ($deliveryAddress !== null) {
            $deliveryAddress->edit($deliveryAddressData);
        }
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param int $max
     * @return \Symfony\Component\Console\Helper\ProgressBar
     */
    private function createProgressBar(OutputInterface $output, int $max): ProgressBar
    {
        $progressBar = new ProgressBar($output, $max);
        $progressBar->setBarCharacter('<fg=magenta>=</>');
        $progressBar->setRedrawFrequency(100);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% ');

        return $progressBar;
    }

    private function displaySkippedUsers(): void
    {
        foreach ($this->skippedInvalidCustomers as $skippedCustomer) {
            $this->output->writeln(
                PHP_EOL . sprintf(
                    'Skipped user with legacyId: %d, email: %s, reason: %s',
                    $skippedCustomer['legacyId'],
                    $skippedCustomer['email'],
                    $skippedCustomer['reason for skip']
                )
            );

            $customerMigrationIssue = new CustomerMigrationIssue();
            $customerMigrationIssue->setCustomerEmail($skippedCustomer['email']);
            $customerMigrationIssue->setCustomerLegacyId($skippedCustomer['legacyId']);
            $customerMigrationIssue->setMessage($skippedCustomer['reason for skip']);

            $this->entityManager->persist($customerMigrationIssue);
            $this->entityManager->flush($customerMigrationIssue);
        }
    }

    /**
     * @param array $csvRow
     * @param string $skipReason
     */
    private function setInvalidCustomer(array $csvRow, string $skipReason): void
    {
        $this->skippedInvalidCustomers[] = [
            'legacyId' => $csvRow[self::USER_COL_INDEX_LEGACY_ID],
            'email' => $csvRow[self::USER_COL_INDEX_EMAIL],
            'reason for skip' => $skipReason,
        ];
    }

    /**
     * @param string $csvFilePath
     * @return int
     */
    private function getNumberOfLinesInFile($csvFilePath): int
    {
        $fileHandle = fopen($csvFilePath, 'r');

        $lineCount = 0;

        rewind($fileHandle);
        while (!feof($fileHandle)) {
            fgets($fileHandle);
            $lineCount++;
        }
        rewind($fileHandle);

        return $lineCount;
    }
}
