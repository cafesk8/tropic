<?php

declare(strict_types=1);

namespace App\Command\Migrations;

use App\Component\Doctrine\SqlLoggerFacade;
use App\Component\Domain\DomainHelper;
use App\Model\Product\ProductData;
use App\Model\Product\ProductDataFactory;
use App\Model\Product\ProductFacade;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Csv\CsvReader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportLegacySeoAttributesFromCSVCommand extends Command
{
    private const CSV_SKIP_N_ROWS = 1;

    private const SEO_INDEX_CATNUM = 0;
    private const SEO_INDEX_TITLE = 1;
    private const SEO_INDEX_META_DESCRIPTION = 2;
    private const SEO_INDEX_TOTAL_HEUREKA_FEED_NAME = 3;
    private const SEO_INDEX_VALID_LEGACY_LANGUAGE_ID = 4;

    private const LEGACY_LANGUAGE_ID_CZ = 1;
    private const LEGACY_LANGUAGE_ID_SK = 2;

    private const LEGACY_LANGUAGE_ID_TO_DOMAIN_ID = [
        self::LEGACY_LANGUAGE_ID_CZ => DomainHelper::CZECH_DOMAIN,
        self::LEGACY_LANGUAGE_ID_SK => DomainHelper::SLOVAK_DOMAIN,
    ];

    private OutputInterface $output;

    private ?string $sourceDirForImportedFiles;

    private SqlLoggerFacade $sqlLoggerFacade;

    private ProductFacade $productFacade;

    private ProductDataFactory $productDataFactory;

    private EntityManagerInterface $entityManager;

    /**
     * @param string $shopsysMigrationsDirPath
     * @param \App\Component\Doctrine\SqlLoggerFacade $sqlLoggerFacade
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Model\Product\ProductDataFactory $productDataFactory
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     */
    public function __construct(
        string $shopsysMigrationsDirPath,
        SqlLoggerFacade $sqlLoggerFacade,
        ProductFacade $productFacade,
        ProductDataFactory $productDataFactory,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();

        $this->sourceDirForImportedFiles = $shopsysMigrationsDirPath;
        $this->sqlLoggerFacade = $sqlLoggerFacade;
        $this->productFacade = $productFacade;
        $this->productDataFactory = $productDataFactory;
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->setName('shopsys:import:legacy-seo')
            ->setDescription('Migrate seo attributes from old shop');
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

        $this->output->writeln('<info>Importing seo attributes</info>');
        $this->importSeoAttributes();
        $this->output->writeln('<info>Finished.</info>');

        $this->sqlLoggerFacade->reenableLogging();

        return 0;
    }

    private function importSeoAttributes(): void
    {
        $seoAttributesCsv = $this->sourceDirForImportedFiles . '/seo.csv';

        if (!is_readable($seoAttributesCsv)) {
            throw new \IOException(sprintf("Can't read file: %s", $seoAttributesCsv));
        }

        $csvReader = new CsvReader();
        $csvRows = $csvReader->getRowsFromCsv($seoAttributesCsv, ';');

        $pBar = $this->createProgressBar($this->output, count($csvRows) - self::CSV_SKIP_N_ROWS);
        $pBar->start();

        $countTotal = 0;
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
                $products = $this->productFacade->getByCatnum($csvRow[self::SEO_INDEX_CATNUM]);
                if (empty($products)) {
                    $countSkipped++;
                    continue;
                }
                foreach ($products as $product) {
                    $productData = $this->productDataFactory->createFromProduct($product);
                    $this->mapSeoAttributes($productData, $csvRow);
                    $this->productFacade->edit($product->getId(), $productData);
                    $this->entityManager->flush();
                    $countUpdated++;
                }
            } catch (\Exception $exc) {
                $countSkipped++;
            } finally {
                $this->entityManager->clear();
            }
        }

        $this->output->writeln(PHP_EOL . sprintf('Updated: %d, Skipped: %d, Total: %d', $countUpdated, $countSkipped, $countTotal));

        $pBar->finish();
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

    /**
     * @param \App\Model\Product\ProductData $productData
     * @param array $csvRow
     */
    private function mapSeoAttributes(ProductData $productData, array $csvRow): void
    {
        $domainId = self::LEGACY_LANGUAGE_ID_TO_DOMAIN_ID[(int)$csvRow[self::SEO_INDEX_VALID_LEGACY_LANGUAGE_ID]];
        $productData->seoTitles[$domainId] = $csvRow[self::SEO_INDEX_TITLE];
        $productData->seoMetaDescriptions[$domainId] = $csvRow[self::SEO_INDEX_META_DESCRIPTION];
        $productData->namesForMergadoFeed[$domainId] = $csvRow[self::SEO_INDEX_TOTAL_HEUREKA_FEED_NAME];
    }
}
