<?php

declare(strict_types=1);

namespace App\Command\Migrations;

use App\Component\Doctrine\SqlLoggerFacade;
use App\Component\Domain\DomainHelper;
use App\Component\Router\FriendlyUrl\FriendlyUrlFacade;
use App\Model\Url\Migration\LegacyUrlMigrationIssue;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Shopsys\FrameworkBundle\Component\Csv\CsvReader;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\Exception\FriendlyUrlNotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportOtherLegacyUrlsCommand extends Command
{
    private const COL_INDEX_LEGACY_TYPE_URL = 0;
    private const COL_INDEX_LEGACY_URL = 2;
    private const COL_INDEX_NEW_URL = 4;

    private const CSV_SKIP_N_ROWS = 1;

    private OutputInterface $output;

    private ?string $sourceDirForImportedFiles;

    private SqlLoggerFacade $sqlLoggerFacade;

    private EntityManagerInterface $entityManager;

    private array $skippedUrls;

    private FriendlyUrlFacade $friendlyUrlFacade;

    /**
     * @param string $shopsysMigrationsDirPath
     * @param \App\Component\Doctrine\SqlLoggerFacade $sqlLoggerFacade
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
     */
    public function __construct(
        string $shopsysMigrationsDirPath,
        SqlLoggerFacade $sqlLoggerFacade,
        EntityManagerInterface $entityManager,
        FriendlyUrlFacade $friendlyUrlFacade
    ) {
        parent::__construct();

        $this->sourceDirForImportedFiles = $shopsysMigrationsDirPath;
        $this->sqlLoggerFacade = $sqlLoggerFacade;
        $this->entityManager = $entityManager;
        $this->friendlyUrlFacade = $friendlyUrlFacade;
    }

    protected function configure(): void
    {
        $this
            ->setName('shopsys:import:legacy-other-urls')
            ->setDescription('Migrate other than product urls from old eshop');
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

        $this->output->writeln('<info>Importing other URLs</info>');
        $this->importUls();
        $this->output->writeln('<info>Finished.</info>');

        $this->sqlLoggerFacade->reenableLogging();

        return 0;
    }

    private function importUls(): void
    {
        $urlsCsv = $this->sourceDirForImportedFiles . '/other_urls.csv';
        if (!is_readable($urlsCsv)) {
            throw new \IOException(sprintf("Can't read file: %s", $urlsCsv));
        }
        $csvReader = new CsvReader();
        // We use comma because it is export from Google Spreadsheets
        $csvRows = $csvReader->getRowsFromCsv($urlsCsv, ',');
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
                if (!filter_var($csvRow[self::COL_INDEX_LEGACY_URL],
                        FILTER_VALIDATE_URL) || !filter_var($csvRow[self::COL_INDEX_NEW_URL], FILTER_VALIDATE_URL)) {
                    throw new \Exception('Invalid URL.');
                }
                $domainId = $this->getTldFromUrl($csvRow[self::COL_INDEX_LEGACY_URL]) === 'cz' ? DomainHelper::CZECH_DOMAIN : DomainHelper::SLOVAK_DOMAIN;
                $newShopUrl = $this->getPathFromUrl($csvRow[self::COL_INDEX_NEW_URL]) . '/';
                $friendlyUrl = $this->friendlyUrlFacade->getFriendlyUrlBySlugAndDomainId($newShopUrl, $domainId);
                $legacyPath = $this->getPathFromUrl($csvRow[self::COL_INDEX_LEGACY_URL]);

                $this->entityManager->createNativeQuery(
                    'INSERT INTO friendly_urls
                     VALUES(:domainId, :slug, :routeName, :entityId, false)', new ResultSetMapping())
                    ->execute([
                        'domainId' => $domainId,
                        'slug' => $legacyPath,
                        'routeName' => $friendlyUrl->getRouteName(),
                        'entityId' => $friendlyUrl->getEntityId(),
                    ]);
                $countUpdated++;
            } catch (UniqueConstraintViolationException $exc) {
                $this->setSkippedUrl($csvRow, 'URL already exists.');
                $countSkipped++;
            } catch (FriendlyUrlNotFoundException $exc) {
                $this->setSkippedUrl($csvRow, 'URL was not found in e-shop.');
                $countSkipped++;
            } catch (\Exception $exc) {
                $this->setSkippedUrl($csvRow, $exc->getMessage());
                $countSkipped++;
            } finally {
                $this->entityManager->clear();
            }
        }

        $this->displaySkippedUrls();
        $this->output->writeln(PHP_EOL . sprintf('Updated: %d, Skipped: %d, Total: %d', $countUpdated, $countSkipped, $countTotal));
        $pBar->finish();
    }

    /**
     * @param string $url
     * @return string
     */
    private function getPathFromUrl(string $url): string
    {
        return trim(parse_url($url, PHP_URL_PATH), '/');
    }

    /**
     * @param string $url
     * @return string
     */
    private function getTldFromUrl(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        $hostArray = explode('.', $host);

        return (string)end($hostArray);
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
    private function setSkippedUrl(array $csvRow, string $message): void
    {
        $this->skippedUrls[] = [
            'legacyUrl' => $csvRow[self::COL_INDEX_LEGACY_URL],
            'newUrl' => $csvRow[self::COL_INDEX_NEW_URL],
            'legacyType' => $csvRow[self::COL_INDEX_LEGACY_TYPE_URL],
            'message' => $message,
        ];
    }

    private function displaySkippedUrls(): void
    {
        foreach ($this->skippedUrls as $skippedUrl) {
            $this->output->writeln(
                sprintf(
                    'legacyUrl: %s, newUrl: %s, legacyType: %s, reason: %s',
                    $skippedUrl['legacyUrl'],
                    $skippedUrl['newUrl'],
                    $skippedUrl['legacyType'],
                    $skippedUrl['message'],
                )
            );

            $legacyUrlMigrationIssue = new LegacyUrlMigrationIssue(
                $skippedUrl['legacyUrl'],
                $skippedUrl['newUrl'],
                $skippedUrl['legacyType'],
                $skippedUrl['message'],
            );
            $this->entityManager->persist($legacyUrlMigrationIssue);
        }
        $this->entityManager->flush();
    }
}
