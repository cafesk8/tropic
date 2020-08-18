<?php

declare(strict_types=1);

namespace App\Command\Migrations;

use App\Component\Doctrine\SqlLoggerFacade;
use App\Component\Order\Migration\OrderPohodaInformationExportFacade;
use App\Model\Customer\Migration\Issue\OrderMigrationIssue;
use App\Model\Order\OrderDataFactory;
use App\Model\Order\OrderFacade;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\Cdn\Component\Domain\Domain;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetPohodaIdToLegacyOrdersCommand extends Command
{
    private const ORDERS_TO_UPDATE_BATCH_SIZE = 100;

    private const UPDATE_ORDERS_IN_LAST_DAYS = 90;

    private OutputInterface $output;

    private SqlLoggerFacade $sqlLoggerFacade;

    private OrderFacade $orderFacade;

    private OrderPohodaInformationExportFacade $orderPohodaInformationExportFacade;

    private OrderDataFactory $orderDataFactory;

    private Domain $domain;

    private int $countUpdated;

    private int $countSkipped;

    private EntityManagerInterface $entityManager;

    private array $skippedOrders;

    /**
     * @param \App\Component\Doctrine\SqlLoggerFacade $sqlLoggerFacade
     * @param \App\Model\Order\OrderFacade $orderFacade
     * @param \App\Component\Order\Migration\OrderPohodaInformationExportFacade $orderPohodaInformationExportFacade
     * @param \App\Model\Order\OrderDataFactory $orderDataFactory
     * @param \Shopsys\Cdn\Component\Domain\Domain $domain
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     */
    public function __construct(
        SqlLoggerFacade $sqlLoggerFacade,
        OrderFacade $orderFacade,
        OrderPohodaInformationExportFacade $orderPohodaInformationExportFacade,
        OrderDataFactory $orderDataFactory,
        Domain $domain,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();

        $this->sqlLoggerFacade = $sqlLoggerFacade;
        $this->orderFacade = $orderFacade;
        $this->orderPohodaInformationExportFacade = $orderPohodaInformationExportFacade;
        $this->orderDataFactory = $orderDataFactory;
        $this->domain = $domain;
        $this->countUpdated = 0;
        $this->countSkipped = 0;
        $this->skippedOrders = [];
        $this->entityManager = $entityManager;
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this
            ->setName('shopsys:import:legacy-orders-pohoda-id')
            ->setDescription('Set Pohoda ID to legacy orders - orders without pohoda id and legacy id');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->sqlLoggerFacade->temporarilyDisableLogging();

        $fromDate = new \DateTime(sprintf('-%s days', self::UPDATE_ORDERS_IN_LAST_DAYS));

        $this->output->writeln(sprintf(
            '<info>Updating orders from %s - setting pohodaId</info>', $fromDate->getTimestamp()
        ));

        $this->updateOrders($fromDate);
        $this->output->writeln('<info>Finished.</info>');
        $this->displaySkippedOrders();

        $this->sqlLoggerFacade->reenableLogging();

        return 0;
    }

    /**
     * @param \DateTime $fromDate
     */
    private function updateOrders(\DateTime $fromDate): void
    {
        $ordersToUpdate = $this->orderFacade->getOrdersWithLegacyIdAndWithoutPohodaIdFromDate($fromDate);
        if (count($ordersToUpdate) > 0) {
            $pBar = $this->createProgressBar($this->output, count($ordersToUpdate));
            $pBar->start();
            $orderNumbersByPohodaId = $this->getPohodaOrderIds($ordersToUpdate);
            $this->setPohodaIdToOrders($orderNumbersByPohodaId, $pBar);

            $this->output->writeln(PHP_EOL . sprintf(
                    'Updated: %d, Skipped: %d, Total from e-shop: %d, Found in Pohoda: %d',
                    $this->countUpdated,
                    $this->countSkipped,
                    count($ordersToUpdate),
                    count($orderNumbersByPohodaId)
                ));
            $pBar->finish();
        }
    }

    /**
     * @param array $ordersToUpdate
     * @return string[]
     */
    private function getPohodaOrderIds(array $ordersToUpdate): array
    {
        $orderNumbersByPohodaId = [];
        $orderNumbers = [];
        foreach ($ordersToUpdate as $order) {
            $orderNumbers[] = $order->getNumber();

            if (count($orderNumbers) >= self::ORDERS_TO_UPDATE_BATCH_SIZE) {
                $orderNumbersByPohodaId += $this->orderPohodaInformationExportFacade->getOrderNumbersWithPohodaId($orderNumbers);
                $orderNumbers = [];
            }
        }
        $orderNumbersByPohodaId += $this->orderPohodaInformationExportFacade->getOrderNumbersWithPohodaId($orderNumbers);

        return $orderNumbersByPohodaId;
    }

    /**
     * @param string[] $pohodaOrderIds
     * @param \Symfony\Component\Console\Helper\ProgressBar $pBar
     */
    private function setPohodaIdToOrders(array $pohodaOrderIds, ProgressBar $pBar): void
    {
        foreach ($pohodaOrderIds as $pohodaOrderId => $orderNumber) {
            $pBar->advance();
            $order = $this->orderFacade->findByNumber($orderNumber);
            if ($order === null) {
                $this->setSkippedOrder($orderNumber, 'Order not found in e-shop');
                $this->countSkipped++;
                continue;
            }
            $orderData = $this->orderDataFactory->createFromOrder($order);
            $orderData->pohodaId = $pohodaOrderId;
            $orderDomain = $this->domain->getDomainConfigById($order->getDomainId());
            $this->orderFacade->edit($order->getId(), $orderData, $orderDomain->getLocale());
            $this->countUpdated++;
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

    /**
     * @param string $orderNumber
     * @param string $skipReason
     */
    private function setSkippedOrder(string $orderNumber, string $skipReason): void
    {
        $this->skippedOrders[] = [
            'orderNumber' => $orderNumber,
            'skipReason' => $skipReason,
        ];
    }

    private function displaySkippedOrders(): void
    {
        foreach ($this->skippedOrders as $invalidOrder) {
            $this->output->writeln(
                sprintf(
                    'Skipped order with number: %s, reason: %s',
                    $invalidOrder['orderNumber'],
                    $invalidOrder['skipReason']
                )
            );

            $orderMigrationIssue = new OrderMigrationIssue();
            $orderMigrationIssue->setOrderLegacyId(-1);
            $orderMigrationIssue->setOrderLegacyNumber($invalidOrder['orderNumber']);
            $orderMigrationIssue->setMessage($invalidOrder['skipReason']);

            $this->entityManager->persist($orderMigrationIssue);
        }
        $this->entityManager->flush();
    }
}
