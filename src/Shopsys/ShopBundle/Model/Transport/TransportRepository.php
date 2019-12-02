<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Transport;

use Doctrine\ORM\Query\Expr\Join;
use Shopsys\FrameworkBundle\Model\Transport\TransportDomain;
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

    /**
     * @return \Shopsys\ShopBundle\Model\Transport\Transport[]
     */
    public function getTransportsForInitialDownload(): array
    {
        return $this->getQueryBuilderForAll()
            ->andWhere('t.balikobot = true')
            ->andWhere('t.pickupPlace = true')
            ->andWhere('t.initialDownload = true')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $mallTypeName
     * @return \Shopsys\ShopBundle\Model\Transport\Transport[]
     */
    public function getByMallTransportName(string $mallTypeName): array
    {
        return $this->getQueryBuilderForAll()
            ->andWhere('t.mallType = :mallType')->setParameter('mallType', $mallTypeName)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param int $domainId
     * @param bool $isTransportEmailType
     * @return \Shopsys\FrameworkBundle\Model\Transport\Transport[]
     */
    public function getAllByDomainIdAndTransportEmailType(int $domainId, bool $isTransportEmailType)
    {
        $queryBuilder = $this->getQueryBuilderForAll()
            ->join(TransportDomain::class, 'td', Join::WITH, 't.id = td.transport AND td.domainId = :domainId')
            ->setParameter('domainId', $domainId);

        if ($isTransportEmailType !== true) {
            $queryBuilder->andWhere('t.transportType != :transportEmailType');
            $queryBuilder->setParameter('transportEmailType', Transport::TYPE_EMAIL);
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
