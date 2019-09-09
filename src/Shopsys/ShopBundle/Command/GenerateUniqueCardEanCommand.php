<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\ShopBundle\Component\CardEan\CardEanFacade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenerateUniqueCardEanCommand extends Command
{
    private const NUMBER_OF_EANS_TO_GENERATE = 50000;

    /**
     * @var string
     */
    protected static $defaultName = 'shopsys:generate:unique-card-ean';

    /**
     * @var \Shopsys\ShopBundle\Component\CardEan\CardEanFacade
     */
    private $cardEanFacade;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @param \Shopsys\ShopBundle\Component\CardEan\CardEanFacade $cardEanFacade
     * @param \Doctrine\ORM\EntityManagerInterface $em
     */
    public function __construct(
        CardEanFacade $cardEanFacade,
        EntityManagerInterface $em
    ) {
        parent::__construct();
        $this->cardEanFacade = $cardEanFacade;
        $this->em = $em;
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setDescription('Generate unique card eans');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $symfonyStyleIo = new SymfonyStyle($input, $output);

        $symfonyStyleIo->note(sprintf('Start to generate `%s` new card EAN numbers', self::NUMBER_OF_EANS_TO_GENERATE));

        $progressBar = new ProgressBar($output, self::NUMBER_OF_EANS_TO_GENERATE);
        $progressBar->setBarCharacter('<fg=magenta>=</>');
        $progressBar->setProgressCharacter("\xF0\x9F\x8D\xBA");
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $progressBar->start();

        for ($i = 0; $i < self::NUMBER_OF_EANS_TO_GENERATE; $i++) {
            $this->cardEanFacade->createUniqueCardEan();
            $progressBar->advance();
            if ($i % 100 === 0) {
                $this->em->clear();
            }
        }

        $progressBar->finish();

        $symfonyStyleIo->newLine();
        $symfonyStyleIo->success('New card EAN numbers have benn successfully generated');
    }
}
