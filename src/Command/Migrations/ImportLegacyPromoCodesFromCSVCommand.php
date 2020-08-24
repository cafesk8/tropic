<?php

declare(strict_types=1);

namespace App\Command\Migrations;

use App\Component\Doctrine\SqlLoggerFacade;
use App\Component\Domain\DomainHelper;
use App\Model\Order\PromoCode\PromoCode;
use App\Model\Order\PromoCode\PromoCodeData;
use App\Model\Order\PromoCode\PromoCodeDataFactory;
use App\Model\Order\PromoCode\PromoCodeFacade;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Csv\CsvReader;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportLegacyPromoCodesFromCSVCommand extends Command
{
    private const CSV_SKIP_N_ROWS = 1;

    private const PROMO_CODE_INDEX_CODE = 0;
    private const PROMO_CODE_INDEX_VALUE = 1;
    private const PROMO_CODE_INDEX_REMAINING_USES = 2;
    private const PROMO_CODE_INDEX_TOTAL_USES = 3;
    private const PROMO_CODE_INDEX_VALID_FROM_TIMESTAMP = 4;
    private const PROMO_CODE_INDEX_VALID_TO_TIMESTAMP = 5;
    private const PROMO_CODE_INDEX_LEGACY_DOMAIN_ID = 6;
    private const PROMO_CODE_INDEX_MINIMUM_ORDER_PRICE = 7;
    private const PROMO_CODE_INDEX_LEGACY_CUSTOMER_ID = 8;
    private const PROMO_CODE_INDEX_TYPE = 9;

    private const GIFT_CERTIFICATE_INDEX_CODE = 0;
    private const GIFT_CERTIFICATE_INDEX_VALUE = 1;
    private const GIFT_CERTIFICATE_INDEX_VALIDITY_DAYS = 2;
    private const GIFT_CERTIFICATE_INDEX_LEGACY_CURRENCY_ID = 3;
    private const GIFT_CERTIFICATE_INDEX_CREATED_TIMESTAMP = 4;
    private const GIFT_CERTIFICATE_INDEX_ACTIVATED_TIMESTAMP = 5;
    private const GIFT_CERTIFICATE_INDEX_NAME = 7;

    private const LEGACY_CURRENCY_ID_CZK = 1;

    private OutputInterface $output;

    private ?string $sourceDirForImportedFiles;

    private SqlLoggerFacade $sqlLoggerFacade;

    private EntityManagerInterface $entityManager;

    private PromoCodeFacade $promoCodeFacade;

    private PromoCodeDataFactory $promoCodeDataFactory;

    /**
     * @param string $shopsysMigrationsDirPath
     * @param \App\Component\Doctrine\SqlLoggerFacade $sqlLoggerFacade
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \App\Model\Order\PromoCode\PromoCodeFacade $promoCodeFacade
     * @param \App\Model\Order\PromoCode\PromoCodeDataFactory $promoCodeDataFactory
     */
    public function __construct(
        string $shopsysMigrationsDirPath,
        SqlLoggerFacade $sqlLoggerFacade,
        EntityManagerInterface $entityManager,
        PromoCodeFacade $promoCodeFacade,
        PromoCodeDataFactory $promoCodeDataFactory
    ) {
        parent::__construct();

        $this->sourceDirForImportedFiles = $shopsysMigrationsDirPath;
        $this->sqlLoggerFacade = $sqlLoggerFacade;
        $this->entityManager = $entityManager;
        $this->promoCodeFacade = $promoCodeFacade;
        $this->promoCodeDataFactory = $promoCodeDataFactory;
    }

    protected function configure(): void
    {
        $this
            ->setName('shopsys:import:legacy-coupons')
            ->setDescription('Migrate promo codes and gift certificates from old shop');
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

        $this->output->writeln('<info>Importing promo codes</info>');
        $this->importPromoCodes();
        $this->output->writeln('<info>Finished.</info>');

        $this->output->writeln('<info>Importing gift certificates</info>');
        $this->importGiftCertificates();
        $this->output->writeln('<info>Finished.</info>');

        $this->sqlLoggerFacade->reenableLogging();

        return 0;
    }

    private function importPromoCodes(): void
    {
        $promoCodesCsv = $this->sourceDirForImportedFiles . '/promo_codes.csv';

        if (!is_readable($promoCodesCsv)) {
            throw new \IOException(sprintf("Can't read file: %s", $promoCodesCsv));
        }

        $csvReader = new CsvReader();
        $csvRows = $csvReader->getRowsFromCsv($promoCodesCsv, ';');

        $pBar = $this->createProgressBar($this->output, count($csvRows) - self::CSV_SKIP_N_ROWS);

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
                $this->entityManager->beginTransaction();
                $promoCode = $this->promoCodeFacade->findPromoCodeByCode($csvRow[self::PROMO_CODE_INDEX_CODE]);
                if ($promoCode !== null) {
                    $promoCodeData = $this->promoCodeDataFactory->createFromPromoCode($promoCode);
                    $this->mapPromoCodeData($promoCodeData, $csvRow);
                    $this->promoCodeFacade->edit($promoCode->getId(), $promoCodeData);
                    $countUpdated++;
                } else {
                    $promoCodeData = $this->promoCodeDataFactory->create();
                    $this->mapPromoCodeData($promoCodeData, $csvRow);
                    $this->promoCodeFacade->create($promoCodeData);
                    $countCreated++;
                }
                $this->entityManager->commit();
            } catch (\Exception $exc) {
                $countSkipped++;

                $this->entityManager->rollback();
            } finally {
                $this->entityManager->clear();
            }
        }

        $this->output->writeln(PHP_EOL . sprintf('Promo codes: Created: %d, Updated: %d, Skipped: %d, Total: %d', $countCreated, $countUpdated, $countSkipped, $countTotal));

        $pBar->finish();
    }

    /**
     * @param \App\Model\Order\PromoCode\PromoCodeData $promoCodeData
     * @param array $promoCodeCsvRow
     */
    private function mapPromoCodeData(PromoCodeData $promoCodeData, array $promoCodeCsvRow): void
    {
        // Legacy domain ids is same as in new e-shop
        $promoCodeData->domainId = (int)$promoCodeCsvRow[self::PROMO_CODE_INDEX_LEGACY_DOMAIN_ID];
        $promoCodeData->code = $promoCodeCsvRow[self::PROMO_CODE_INDEX_CODE];
        $promoCodeData->unlimited = false;
        $promoCodeData->usageLimit = (int)$promoCodeCsvRow[self::PROMO_CODE_INDEX_TOTAL_USES];
        $promoCodeData->numberOfUses = $promoCodeData->usageLimit - (int)$promoCodeCsvRow[self::PROMO_CODE_INDEX_REMAINING_USES];
        $promoCodeData->validFrom = new \DateTime();
        $promoCodeData->validFrom->setTimestamp((int)$promoCodeCsvRow[self::PROMO_CODE_INDEX_VALID_FROM_TIMESTAMP]);
        $promoCodeData->validTo = new \DateTime();
        $promoCodeData->validTo->setTimestamp((int)$promoCodeCsvRow[self::PROMO_CODE_INDEX_VALID_TO_TIMESTAMP]);
        $promoCodeData->type = PromoCodeData::TYPE_PROMO_CODE;
        $promoCodeData->minOrderValue = Money::create($promoCodeCsvRow[self::PROMO_CODE_INDEX_MINIMUM_ORDER_PRICE]);

        if ($promoCodeCsvRow[self::PROMO_CODE_INDEX_LEGACY_CUSTOMER_ID] === '') {
            $promoCodeData->userType = PromoCode::USER_TYPE_ALL;
        } else {
            $promoCodeData->userType = PromoCode::USER_TYPE_LOGGED;
        }

        if ($promoCodeCsvRow[self::PROMO_CODE_INDEX_TYPE] === 'p') {
            $promoCodeData->useNominalDiscount = false;
            $promoCodeData->percent = (float)$promoCodeCsvRow[self::PROMO_CODE_INDEX_VALUE];
        } else {
            $promoCodeData->useNominalDiscount = true;
            $promoCodeData->nominalDiscount = Money::create($promoCodeCsvRow[self::PROMO_CODE_INDEX_VALUE]);
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

    private function importGiftCertificates(): void
    {
        $giftCertificatesCsv = $this->sourceDirForImportedFiles . '/gift_certificates.csv';

        if (!is_readable($giftCertificatesCsv)) {
            throw new \IOException(sprintf("Can't read file: %s", $giftCertificatesCsv));
        }

        $csvReader = new CsvReader();
        $csvRows = $csvReader->getRowsFromCsv($giftCertificatesCsv, ';');

        $pBar = $this->createProgressBar($this->output, count($csvRows) - self::CSV_SKIP_N_ROWS);

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
                $this->entityManager->beginTransaction();
                $giftCertificate = $this->promoCodeFacade->findPromoCodeByCode($csvRow[self::PROMO_CODE_INDEX_CODE]);
                if ($giftCertificate !== null) {
                    $giftCertificateData = $this->promoCodeDataFactory->createFromPromoCode($giftCertificate);
                    $this->mapGiftCertificateData($giftCertificateData, $csvRow);
                    $this->promoCodeFacade->edit($giftCertificate->getId(), $giftCertificateData);
                    $countUpdated++;
                } else {
                    $giftCertificateData = $this->promoCodeDataFactory->create();
                    $this->mapGiftCertificateData($giftCertificateData, $csvRow);
                    $this->promoCodeFacade->create($giftCertificateData);
                    $countCreated++;
                }
                $this->entityManager->commit();
            } catch (\Exception $exc) {
                $countSkipped++;

                $this->entityManager->rollback();
            } finally {
                $this->entityManager->clear();
            }
        }

        $this->output->writeln(PHP_EOL . sprintf('Certificates: Created: %d, Updated: %d, Skipped: %d, Total: %d', $countCreated, $countUpdated, $countSkipped, $countTotal));

        $pBar->finish();
    }

    /**
     * @param \App\Model\Order\PromoCode\PromoCodeData $giftCertificateData
     * @param array $giftCertificateRow
     */
    private function mapGiftCertificateData(PromoCodeData $giftCertificateData, array $giftCertificateRow): void
    {
        $legacyCurrencyId = (int)$giftCertificateRow[self::GIFT_CERTIFICATE_INDEX_LEGACY_CURRENCY_ID];
        $giftCertificateData->domainId = $legacyCurrencyId === self::LEGACY_CURRENCY_ID_CZK ? DomainHelper::CZECH_DOMAIN : DomainHelper::SLOVAK_DOMAIN;
        $giftCertificateData->code = $giftCertificateRow[self::GIFT_CERTIFICATE_INDEX_CODE];
        $giftCertificateData->unlimited = false;
        $giftCertificateData->numberOfUses = 0;
        $giftCertificateData->certificateSku = $giftCertificateRow[self::GIFT_CERTIFICATE_INDEX_NAME];
        $giftCertificateData->type = PromoCodeData::TYPE_CERTIFICATE;
        $giftCertificateData->certificateValue = Money::create($giftCertificateRow[self::GIFT_CERTIFICATE_INDEX_VALUE]);

        $createdTimestamp = (int)$giftCertificateRow[self::GIFT_CERTIFICATE_INDEX_CREATED_TIMESTAMP];
        if ($createdTimestamp > 0) {
            $giftCertificateData->validFrom = new \DateTime();
            $giftCertificateData->validFrom->setTimestamp($createdTimestamp);
        }

        $activatedTimestamp = (int)$giftCertificateRow[self::GIFT_CERTIFICATE_INDEX_ACTIVATED_TIMESTAMP];
        $validityDays = (int)$giftCertificateRow[self::GIFT_CERTIFICATE_INDEX_VALIDITY_DAYS];
        $giftCertificateData->validTo = new \DateTime();
        $giftCertificateData->usageLimit = $activatedTimestamp > 0 ? 1 : 0;
        $validTo = $createdTimestamp + $validityDays * 24 * 60 * 60;
        $giftCertificateData->validTo->setTimestamp($validTo);
    }
}
