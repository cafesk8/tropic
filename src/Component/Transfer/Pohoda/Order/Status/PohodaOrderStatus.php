<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Order\Status;

class PohodaOrderStatus
{
    public const COL_POHODA_ORDER_ID = 'pohodaOrderId';
    public const COL_POHODA_STATUS_ID = 'pohodaStatusId';
    public const COL_POHODA_STATUS_NAME = 'pohodaStatusName';

    public int $pohodaOrderId;

    public int $pohodaStatusId;

    public ?string $statusName;

    /**
     * @param array $pohodaOrderStatusData
     */
    public function __construct(array $pohodaOrderStatusData)
    {
        $this->pohodaOrderId = (int)$pohodaOrderStatusData[self::COL_POHODA_ORDER_ID];
        $this->pohodaStatusId = (int)$pohodaOrderStatusData[self::COL_POHODA_STATUS_ID];
        $this->statusName = $pohodaOrderStatusData[self::COL_POHODA_STATUS_NAME];
    }
}
