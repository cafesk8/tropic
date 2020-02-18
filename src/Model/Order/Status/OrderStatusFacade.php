<?php

declare(strict_types=1);

namespace App\Model\Order\Status;

use Shopsys\FrameworkBundle\Model\Order\Mail\OrderMail;
use Shopsys\FrameworkBundle\Model\Order\Status\OrderStatusFacade as BaseOrderStatusFacade;

/**
 * @method \App\Model\Order\Status\OrderStatus create(OrderStatusData $orderStatusFormData)
 * @method \App\Model\Order\Status\OrderStatus getById($orderStatusId)
 */
class OrderStatusFacade extends BaseOrderStatusFacade
{
    /**
     * @var \App\Model\Order\Status\OrderStatusRepository
     */
    protected $orderStatusRepository;

    /**
     * @param \App\Model\Order\Status\OrderStatusData $orderStatusFormData
     * @param int $type
     * @return \App\Model\Order\Status\OrderStatus
     */
    public function createWithType(OrderStatusData $orderStatusFormData, int $type): OrderStatus
    {
        /** @var \App\Model\Order\Status\OrderStatus $orderStatus */
        $orderStatus = $this->orderStatusFactory->create($orderStatusFormData, $type);
        $this->em->persist($orderStatus);
        $this->em->flush();

        $this->mailTemplateFacade->createMailTemplateForAllDomains(
            OrderMail::getMailTemplateNameByStatus($orderStatus)
        );

        return $orderStatus;
    }

    /**
     * @param string $transferId
     * @return \App\Model\Order\Status\OrderStatus|null
     */
    public function findByTransferStatus(string $transferId): ?OrderStatus
    {
        return $this->orderStatusRepository->findByTransferStatus($transferId);
    }

    /**
     * @param int $type
     * @return \App\Model\Order\Status\OrderStatus
     */
    public function getByType(int $type): OrderStatus
    {
        return $this->orderStatusRepository->getByType($type);
    }
}
