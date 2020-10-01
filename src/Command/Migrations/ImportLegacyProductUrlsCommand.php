<?php

declare(strict_types=1);

namespace App\Command\Migrations;

use App\Component\Doctrine\SqlLoggerFacade;
use App\Component\Domain\DomainHelper;
use App\Model\Product\Migration\ProductUrlMigrationIssue;
use App\Model\Product\ProductFacade;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Shopsys\FrameworkBundle\Component\Csv\CsvReader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportLegacyProductUrlsCommand extends Command
{
    private const COL_INDEX_CATNUM = 0;
    private const COL_INDEX_URL = 1;
    private const COL_INDEX_DOMAIN = 2;

    private const CSV_SKIP_N_ROWS = 1;

    private const LEGACY_DOMAIN_ID_CZ = 1;
    private const LEGACY_DOMAIN_ID_SK = 2;

    private const LEGACY_DOMAIN_ID_ID_TO_DOMAIN_ID = [
        self::LEGACY_DOMAIN_ID_CZ => DomainHelper::CZECH_DOMAIN,
        self::LEGACY_DOMAIN_ID_SK => DomainHelper::SLOVAK_DOMAIN,
    ];

    private OutputInterface $output;

    private ?string $sourceDirForImportedFiles;

    private SqlLoggerFacade $sqlLoggerFacade;

    private EntityManagerInterface $entityManager;

    private ProductFacade $productFacade;

    private array $skippedProducts;

    /**
     * @param string $shopsysMigrationsDirPath
     * @param \App\Component\Doctrine\SqlLoggerFacade $sqlLoggerFacade
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \App\Model\Product\ProductFacade $productFacade
     */
    public function __construct(
        string $shopsysMigrationsDirPath,
        SqlLoggerFacade $sqlLoggerFacade,
        EntityManagerInterface $entityManager,
        ProductFacade $productFacade
    ) {
        parent::__construct();

        $this->sourceDirForImportedFiles = $shopsysMigrationsDirPath;
        $this->sqlLoggerFacade = $sqlLoggerFacade;
        $this->entityManager = $entityManager;
        $this->productFacade = $productFacade;
    }

    protected function configure(): void
    {
        $this
            ->setName('shopsys:import:legacy-product-urls')
            ->setDescription('Migrate product urls from old eshop');
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

        $this->output->writeln('<info>Importing product URLs</info>');
        $this->importProductUrls();
        $this->output->writeln('<info>Finished.</info>');

        $this->sqlLoggerFacade->reenableLogging();

        return 0;
    }

    private function importProductUrls(): void
    {
        $productUrlsCsv = $this->sourceDirForImportedFiles . '/product_urls.csv';
        if (!is_readable($productUrlsCsv)) {
            throw new \IOException(sprintf("Can't read file: %s", $productUrlsCsv));
        }

        $csvReader = new CsvReader();
        $csvRows = $csvReader->getRowsFromCsv($productUrlsCsv, ';');

        $pBar = $this->createProgressBar($this->output, count($csvRows));
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
                $products = $this->productFacade->getByCatnum($csvRow[self::COL_INDEX_CATNUM]);
                if (empty($products)) {
                    throw new \Exception('Product doesn´t exist.');
                }
                if (count($products) > 1) {
                    throw new \Exception('Product doesn´t have unique catnum.');
                }

                $product = reset($products);
                $urlPath = trim(parse_url($csvRow[self::COL_INDEX_URL])['path'], '/');
                $this->entityManager->createNativeQuery(
                    'INSERT INTO friendly_urls
                     VALUES(:domainId, :slug, :routeName, :entityId, false)', new ResultSetMapping())
                    ->execute([
                        'domainId' => (int)self::LEGACY_DOMAIN_ID_ID_TO_DOMAIN_ID[$csvRow[self::COL_INDEX_DOMAIN]],
                        'slug' => $urlPath,
                        'routeName' => 'front_product_detail',
                        'entityId' => $product->getId(),
                    ]);
                $countUpdated++;
            } catch (UniqueConstraintViolationException $exc) {
                $this->setSkippedProduct($csvRow, 'URL already exists.');
                $countSkipped++;
            } catch (\Exception $exc) {
                $this->setSkippedProduct($csvRow, $exc->getMessage());
                $countSkipped++;
            } finally {
                $this->entityManager->clear();
            }
        }

        $this->displaySkippedProducts();
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
     * @param array $csvRow
     * @param string $message
     */
    private function setSkippedProduct(array $csvRow, string $message): void
    {
        $this->skippedProducts[] = [
            'catnum' => $csvRow[self::COL_INDEX_CATNUM],
            'domain' => (int)$csvRow[self::COL_INDEX_DOMAIN],
            'url' => $csvRow[self::COL_INDEX_URL],
            'message' => $message,
        ];
    }

    private function displaySkippedProducts(): void
    {
        foreach ($this->skippedProducts as $skippedProduct) {
            $this->output->writeln(
                sprintf(
                    'Catnum: %s, domain: %d, url: %s, reason: %s',
                    $skippedProduct['catnum'],
                    $skippedProduct['domain'],
                    $skippedProduct['url'],
                    $skippedProduct['message'],
                )
            );

            $productUrlMigrationIssue = new ProductUrlMigrationIssue();
            $productUrlMigrationIssue->setCatnum($skippedProduct['catnum']);
            $productUrlMigrationIssue->setDomain($skippedProduct['domain']);
            $productUrlMigrationIssue->setUrl($skippedProduct['url']);
            $productUrlMigrationIssue->setMessage($skippedProduct['message']);

            $this->entityManager->persist($productUrlMigrationIssue);
        }
        $this->entityManager->flush();
    }
}
