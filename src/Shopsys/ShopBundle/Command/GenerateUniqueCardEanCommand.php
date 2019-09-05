<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Command;

use Shopsys\ShopBundle\Component\CardEan\CardEanFacade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @param \Shopsys\ShopBundle\Component\CardEan\CardEanFacade $cardEanFacade
     */
    public function __construct(CardEanFacade $cardEanFacade)
    {
        parent::__construct();
        $this->cardEanFacade = $cardEanFacade;
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
        for ($i = 0; $i < self::NUMBER_OF_EANS_TO_GENERATE; $i++) {
            $this->cardEanFacade->createUniqueCardEan();
        }
    }
}
