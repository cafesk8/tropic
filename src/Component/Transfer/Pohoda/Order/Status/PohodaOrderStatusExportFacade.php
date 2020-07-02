<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Order\Status;

use DateTime;

class PohodaOrderStatusExportFacade
{
    private PohodaOrderStatusExportRepository $pohodaOrderStatusExportRepository;

    /**
     * @param \App\Component\Transfer\Pohoda\Order\Status\PohodaOrderStatusExportRepository $pohodaOrderStatusExportRepository
     */
    public function __construct(PohodaOrderStatusExportRepository $pohodaOrderStatusExportRepository)
    {
        $this->pohodaOrderStatusExportRepository = $pohodaOrderStatusExportRepository;
    }

    /**
     * @param \DateTime|null $lastModificationDate
     * @return array
     */
    public function getPohodaOrderIdsFromLastModificationDate(?DateTime $lastModificationDate): array
    {
        return $this->pohodaOrderStatusExportRepository->getPohodaOrderIdsByLastUpdateTime($lastModificationDate);
    }
}
