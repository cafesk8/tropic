<?php

declare(strict_types=1);

namespace App\Component\Order\Migration;

class OrderPohodaInformationExportFacade
{
    private OrderPohodaInformationExportRepository $orderPohodaInformationExportRepository;

    /**
     * @param \App\Component\Order\Migration\OrderPohodaInformationExportRepository $orderPohodaInformationExportRepository
     */
    public function __construct(OrderPohodaInformationExportRepository $orderPohodaInformationExportRepository)
    {
        $this->orderPohodaInformationExportRepository = $orderPohodaInformationExportRepository;
    }

    /**
     * @param string[] $orderNumbers
     * @return string[]
     */
    public function getOrderNumbersWithPohodaId(array $orderNumbers): array
    {
        return $this->orderPohodaInformationExportRepository->getOrderNumbersWithPohodaId($orderNumbers);
    }
}
