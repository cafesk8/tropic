<?php

declare(strict_types=1);

namespace App\DataFixtures\Demo;

use App\Model\Order\Status\OrderStatusDataFactory;
use App\Model\Order\Status\OrderStatusFacade;
use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Component\Domain\Domain;

class OrderStatusDataFixture extends AbstractReferenceFixture
{
    public const ORDER_STATUS_NEW = 'order_status_new';
    public const ORDER_STATUS_IN_PROGRESS = 'order_status_in_progress';
    public const ORDER_STATUS_DONE = 'order_status_done';
    public const ORDER_STATUS_CANCELED = 'order_status_canceled';
    public const ORDER_STATUS_CUSTOMER_DID_NOT_PICK_UP = 'order_status_customer_did_not_pick_up';
    public const ORDER_STATUS_PAID = 'order_status_paid';

    /**
     * @var \App\Model\Order\Status\OrderStatusFacade
     */
    protected $orderStatusFacade;

    /**
     * @var \App\Model\Order\Status\OrderStatusDataFactory
     */
    private $orderStatusDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \App\Model\Order\Status\OrderStatusFacade $orderStatusFacade
     * @param \App\Model\Order\Status\OrderStatusDataFactory $orderStatusDataFactory
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
        $this->createOrderStatusReference(10, self::ORDER_STATUS_CUSTOMER_DID_NOT_PICK_UP);
        $this->createOrderStatusReference(11, self::ORDER_STATUS_PAID);
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
                case self::ORDER_STATUS_CUSTOMER_DID_NOT_PICK_UP:
                    $orderStatusData->name[$locale] = t('Nepřevzato zákazníkem', [], 'dataFixtures', $locale);
                    break;
                case self::ORDER_STATUS_PAID:
                    $orderStatusData->name[$locale] = t('Zaplaceno', [], 'dataFixtures', $locale);
                    break;
                default:
                    throw new \Shopsys\FrameworkBundle\Component\DataFixture\Exception\UnknownNameTranslationForOrderStatusReferenceNameException($referenceName);
            }
        }
        $this->orderStatusFacade->edit($orderStatusId, $orderStatusData);
        $this->addReference($referenceName, $orderStatus);
    }
}
