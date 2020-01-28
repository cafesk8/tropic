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
    public const ORDER_STATUS_RETURNED = 'order_status_returned';

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

        $orderStatus = $this->orderStatusFacade->getByType(OrderStatus::TYPE_ALMOST_READY);
        $this->createOrderStatusReference($orderStatus->getId(), self::ORDER_STATUS_ALMOST_READY);

        $orderStatus = $this->orderStatusFacade->getByType(OrderStatus::TYPE_ALMOST_READY_STORE);
        $this->createOrderStatusReference($orderStatus->getId(), self::ORDER_STATUS_ALMOST_READY_STORE);

        $orderStatus = $this->orderStatusFacade->getByType(OrderStatus::TYPE_READY);
        $this->createOrderStatusReference($orderStatus->getId(), self::ORDER_STATUS_READY);

        $orderStatus = $this->orderStatusFacade->getByType(OrderStatus::TYPE_READY_STORE);
        $this->createOrderStatusReference($orderStatus->getId(), self::ORDER_STATUS_READY_STORE);

        $orderStatus = $this->orderStatusFacade->getByType(OrderStatus::TYPE_RETURNED);
        $this->createOrderStatusReference($orderStatus->getId(), self::ORDER_STATUS_RETURNED);
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
        $orderStatusData = $this->orderStatusDataFactory->createFromOrderStatus($orderStatus);
        foreach ($this->domain->getAllLocales() as $locale) {
            switch ($referenceName) {
                case self::ORDER_STATUS_NEW:
                    $orderStatusData->name[$locale] = t('Nová', [], 'dataFixtures', $locale);
                    break;
                case self::ORDER_STATUS_IN_PROGRESS:
                    $orderStatusData->name[$locale] = t('Vyřizuje se', [], 'dataFixtures', $locale);
                    break;
                case self::ORDER_STATUS_DONE:
                    $orderStatusData->name[$locale] = t('Vyřízena', [], 'dataFixtures', $locale);
                    break;
                case self::ORDER_STATUS_CANCELED:
                    $orderStatusData->name[$locale] = t('Stornována', [], 'dataFixtures', $locale);
                    break;
                case self::ORDER_STATUS_ALMOST_READY:
                    $orderStatusData->name[$locale] = t('Částečné vykrytí', [], 'dataFixtures', $locale);
                    break;
                case self::ORDER_STATUS_ALMOST_READY_STORE:
                    $orderStatusData->name[$locale] = t('Částečné vykrytí - OM', [], 'dataFixtures', $locale);
                    break;
                case self::ORDER_STATUS_READY:
                    $orderStatusData->name[$locale] = t('Vykrytá', [], 'dataFixtures', $locale);
                    break;
                case self::ORDER_STATUS_READY_STORE:
                    $orderStatusData->name[$locale] = t('Vykrytá - OM', [], 'dataFixtures', $locale);
                    break;
                case self::ORDER_STATUS_RETURNED:
                    $orderStatusData->name[$locale] = t('Vrácené zboží', [], 'dataFixtures', $locale);
                    break;
                default:
                    throw new \Shopsys\FrameworkBundle\Component\DataFixture\Exception\UnknownNameTranslationForOrderStatusReferenceNameException($referenceName);
            }
        }
        $this->addReference($referenceName, $orderStatus);
    }
}
