<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Transport;

use Shopsys\FrameworkBundle\Model\Transport\TransportRepository as BaseTransportRepository;

class TransportRepository extends BaseTransportRepository
{
    /**
     * @return \Shopsys\FrameworkBundle\Model\Transport\Transport[]
     */
    public function getAllPickupTransports(): array
    {
        return $this->getQueryBuilderForAll()
            ->andWhere('t.balikobot = true')
            ->andWhere('t.pickupPlace = true')
            ->getQuery()
            ->getResult();
    }
}
