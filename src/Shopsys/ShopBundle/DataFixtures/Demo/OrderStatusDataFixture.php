<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\DataFixtures\Demo;

use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\ShopBundle\Model\Order\Status\OrderStatus;
use Shopsys\ShopBundle\Model\Order\Status\OrderStatusDataFactory;
use Shopsys\ShopBundle\Model\Order\Status\OrderStatusFacade;

class OrderStatusDataFixture extends AbstractReferenceFixture
{
    public const ORDER_STATUS_NEW = 'order_status_new';
    public const ORDER_STATUS_IN_PROGRESS = 'order_status_in_progress';
    public const ORDER_STATUS_DONE = 'order_status_done';
    public const ORDER_STATUS_CANCELED = 'order_status_canceled';
    public const ORDER_STATUS_ALMOST_READY = 'order_status_almost_ready';
    public const ORDER_STATUS_ALMOST_READY_STORE = 'order_status_almost_ready_store';
    public const ORDER_STATUS_READY = 'order_status_ready';
    public const ORDER_STATUS_READY_STORE = 'order_status_ready_store';

    /**
     * @var \Shopsys\ShopBundle\Model\Order\Status\OrderStatusFacade
     */
    protected $orderStatusFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Order\Status\OrderStatusDataFactory
     */
    private $orderStatusDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Status\OrderStatusFacade $orderStatusFacade
     * @param \Shopsys\ShopBundle\Model\Order\Status\OrderStatusDataFactory $orderStatusDataFactory
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(
        OrderStatusFacade $orderStatusFacade,
        OrderStatusDataFactory $orderStatusDataFactory,
        Domain $domain
    ) {
        $this->orderStatusFacade = $orderStatusFacade;
        $this->orderStatusDataFactory = $orderStatusDataFactory;
        $this->domain = $domain;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $this->createOrderStatusReference(1, self::ORDER_STATUS_NEW);
        $this->createOrderStatusReference(2, self::ORDER_STATUS_IN_PROGRESS);
        $this->createOrderStatusReference(3, self::ORDER_STATUS_DONE);
        $this->createOrderStatusReference(4, self::ORDER_STATUS_CANCELED);

        $orderStatusData = $this->orderStatusDataFactory->create();
        $orderStatusData->name = [
            'cs' => 'Částečné vykrytí',
            'sk' => 'Částečné vykrytí',
            'de' => 'Částečné vykrytí',
        ];
        $orderStatus = $this->orderStatusFacade->createWithType($orderStatusData, OrderStatus::TYPE_ALMOST_READY);
        $this->createOrderStatusReference($orderStatus->getId(), self::ORDER_STATUS_ALMOST_READY);

        $orderStatusData = $this->orderStatusDataFactory->create();
        $orderStatusData->name = [
            'cs' => 'Částečné vykrytí - OM',
            'sk' => 'Částečné vykrytí - OM',
            'de' => 'Částečné vykrytí - OM',
        ];
        $orderStatus = $this->orderStatusFacade->createWithType($orderStatusData, OrderStatus::TYPE_ALMOST_READY_STORE);
        $this->createOrderStatusReference($orderStatus->getId(), self::ORDER_STATUS_ALMOST_READY_STORE);

        $orderStatusData = $this->orderStatusDataFactory->create();
        $orderStatusData->name = [
            'cs' => 'Vykrytá',
            'sk' => 'Vykrytá',
            'de' => 'Vykrytá',
        ];
        $orderStatus = $this->orderStatusFacade->createWithType($orderStatusData, OrderStatus::TYPE_READY);
        $this->createOrderStatusReference($orderStatus->getId(), self::ORDER_STATUS_READY);

        $orderStatusData = $this->orderStatusDataFactory->create();
        $orderStatusData->name = [
            'cs' => 'Vykrytá - OM',
            'sk' => 'Vykrytá - OM',
            'de' => 'Vykrytá - OM',
        ];
        $orderStatus = $this->orderStatusFacade->createWithType($orderStatusData, OrderStatus::TYPE_READY_STORE);
        $this->createOrderStatusReference($orderStatus->getId(), self::ORDER_STATUS_READY_STORE);

        $this->addTranslationsToOrderStatusReturned();
    }

    /**
     * Order statuses are created (with specific ids) in database migration.
     *
     * @param int $orderStatusId
     * @param string $referenceName
     * @see \Shopsys\FrameworkBundle\Migrations\Version20180603135341
     */
    protected function createOrderStatusReference(
        $orderStatusId,
        $referenceName
    ) {
        $orderStatus = $this->orderStatusFacade->getById($orderStatusId);
        $this->addReference($referenceName, $orderStatus);
    }

    private function addTranslationsToOrderStatusReturned(): void
    {
        $orderStatusReturned = $this->orderStatusFacade->getByType(OrderStatus::TYPE_RETURNED);
        $orderStatusDataReturned = $this->orderStatusDataFactory->createFromOrderStatus($orderStatusReturned);
        $name = $orderStatusReturned->getName('en');
        foreach ($this->domain->getAll() as $domainConfig) {
            $orderStatusDataReturned->name[$domainConfig->getLocale()] = $name;
        }
        $this->orderStatusFacade->edit($orderStatusReturned->getId(), $orderStatusDataReturned);
    }
}
