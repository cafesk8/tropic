<?php

declare(strict_types=1);

namespace App\Command\Migrations;

use App\Component\Doctrine\SqlLoggerFacade;
use App\Component\Domain\DomainHelper;
use App\Model\Product\ProductDataFactory;
use App\Model\Product\ProductFacade;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Csv\CsvReader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportLegacySlovakDescriptionsFromCSVCommand extends Command
{
    private const USER_COL_INDEX_CATNUM = 0;
    private const USER_COL_INDEX_SHORT_DESCRIPTION = 1;
    private const USER_COL_INDEX_LONG_DESCRIPTION = 2;

    private const CSV_SKIP_N_ROWS = 1;

    private OutputInterface $output;

    private ?string $sourceDirForImportedFiles;

    private SqlLoggerFacade $sqlLoggerFacade;

    private EntityManagerInterface $entityManager;

    private ProductFacade $productFacade;

    private ProductDataFactory $productDataFactory;

    /**
     * @param string $shopsysMigrationsDirPath
     * @param \App\Component\Doctrine\SqlLoggerFacade $sqlLoggerFacade
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Model\Product\ProductDataFactory $productDataFactory
     */
    public function __construct(
        string $shopsysMigrationsDirPath,
        SqlLoggerFacade $sqlLoggerFacade,
        EntityManagerInterface $entityManager,
        ProductFacade $productFacade,
        ProductDataFactory $productDataFactory
    ) {
        parent::__construct();

        $this->sourceDirForImportedFiles = $shopsysMigrationsDirPath;
        $this->sqlLoggerFacade = $sqlLoggerFacade;
        $this->entityManager = $entityManager;
        $this->productFacade = $productFacade;
        $this->productDataFactory = $productDataFactory;
    }

    protected function configure(): void
    {
        $this
            ->setName('shopsys:import:legacy-slovak-descriptions')
            ->setDescription('Migrate slovak descriptions for products from old shop');
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

        $this->output->writeln('<info>Importing descriptions</info>');
        $this->importDescriptions();
        $this->output->writeln('<info>Finished.</info>');

        $this->sqlLoggerFacade->reenableLogging();

        return 0;
    }

    private function importDescriptions(): void
    {
        $descriptionsCsv = $this->sourceDirForImportedFiles . '/descriptions.csv';

        if (!is_readable($descriptionsCsv)) {
            throw new \IOException(sprintf("Can't read file: %s", $descriptionsCsv));
        }

        $csvReader = new CsvReader();
        $csvRows = $csvReader->getRowsFromCsv($descriptionsCsv, ';');

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
                $products = $this->productFacade->getByCatnum($csvRow[self::USER_COL_INDEX_CATNUM]);

                if (empty($products)) {
                    $countSkipped++;
                    continue;
                }

                $description = strip_tags($csvRow[self::USER_COL_INDEX_SHORT_DESCRIPTION]);
                $longDescription = $csvRow[self::USER_COL_INDEX_LONG_DESCRIPTION];
                foreach ($products as $product) {
                    $productData = $this->productDataFactory->createFromProduct($product);
                    if ($description !== '') {
                        $productData->shortDescriptions[DomainHelper::SLOVAK_DOMAIN] = $description;
                    }
                    if ($longDescription !== '') {
                        $productData->descriptions[DomainHelper::SLOVAK_DOMAIN] = $longDescription;
                    }

                    $this->productFacade->edit($product->getId(), $productData);
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
}
