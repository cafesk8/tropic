<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Mall;

use Exception;
use Shopsys\Plugin\Cron\IteratedCronModuleInterface;
use Shopsys\ShopBundle\Component\Mall\MallImportOrderClient;
use Symfony\Bridge\Monolog\Logger;

class OrderImportCronModule implements IteratedCronModuleInterface
{
    private const BATCH_SIZE = 100;

    /**
     * @var \Symfony\Bridge\Monolog\Logger
     */
    private $logger;

    /**
     * @var \Shopsys\ShopBundle\Component\Mall\MallImportOrderClient
     */
    private $mallImportOrderClient;

    /**
     * @var \Shopsys\ShopBundle\Model\Order\Mall\MallImportOrderFactory
     */
    private $mallImportOrderFactory;

    /**
     * @param \Shopsys\ShopBundle\Component\Mall\MallImportOrderClient $mallImportOrderClient
     * @param \Shopsys\ShopBundle\Model\Order\Mall\MallImportOrderFactory $orderMallImportFactory
     */
    public function __construct(
        MallImportOrderClient $mallImportOrderClient,
        MallImportOrderFactory $orderMallImportFactory
    ) {
        $this->mallImportOrderClient = $mallImportOrderClient;
        $this->mallImportOrderFactory = $orderMallImportFactory;
    }

    /**
     * @return bool|void
     */
    public function iterate()
    {
        try {
            $openOrders = $this->mallImportOrderClient->getOpenedOrders();

            foreach ($openOrders as $idx => $mallOrderId) {
                try {
                    $this->importOrder($mallOrderId);

                    if ($idx === self::BATCH_SIZE) {
                        return true;
                    }
                } catch (Exception $exception) {
                    $this->logger->addError(
                        sprintf(
                            'Order `%s` can not be imported from Mall.cz due to exception: %s',
                            $mallOrderId,
                            $exception->getMessage()
                        ),
                        [
                            'exception' => $exception,
                        ]
                    );
                }
            }
        } catch (Exception $connectionException) {
            $this->logger->addError(
                sprintf(
                    'Mall connection error, exception: %s',
                    $connectionException->getMessage()
                ),
                [
                    'exception' => $connectionException,
                ]
            );
        }
    }

    /**
     * @param int $mallOrderId
     */
    private function importOrder(int $mallOrderId)
    {
        $mallOrderDetail = $this->mallImportOrderClient->getOrderDetail($mallOrderId);
        if ($mallOrderDetail->getConfirmed() === true) {
            $this->logger->addInfo(sprintf('Order `%s` was imported at past', $mallOrderId));
            return;
        }

        $this->mallImportOrderFactory->createOrder($mallOrderDetail);
        $this->logger->addInfo(sprintf('Order `%s` was successful imported', $mallOrderId));
    }

    /**
     * @param \Symfony\Bridge\Monolog\Logger $logger
     */
    public function setLogger(Logger $logger): void
    {
        $this->logger = $logger;
    }

    public function wakeUp()
    {
    }

    public function sleep()
    {
    }
}
