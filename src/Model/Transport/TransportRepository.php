<?php

declare(strict_types=1);

namespace App\Model\Transport;

use Doctrine\ORM\Query\Expr\Join;
use Shopsys\FrameworkBundle\Model\Transport\TransportDomain;
use Shopsys\FrameworkBundle\Model\Transport\TransportRepository as BaseTransportRepository;

/**
 * @method \App\Model\Transport\Transport[] getAll()
 * @method \App\Model\Transport\Transport[] getAllByIds(array $transportIds)
 * @method \App\Model\Transport\Transport[] getAllByDomainId(int $domainId)
 * @method \App\Model\Transport\Transport[] getAllIncludingDeleted()
 * @method \App\Model\Transport\Transport|null findById(int $id)
 * @method \App\Model\Transport\Transport getById(int $id)
 */
class TransportRepository extends BaseTransportRepository
{
    /**
     * @return \App\Model\Transport\Transport[]
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
     * @return \App\Model\Transport\Transport[]
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
     * @return \App\Model\Transport\Transport[]
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
     * @param bool $showEmailTransportInCart
     * @return \App\Model\Transport\Transport[]
     */
    public function getAllByDomainIdAndTransportEmailType(int $domainId, bool $showEmailTransportInCart)
    {
        $queryBuilder = $this->getQueryBuilderForAll()
            ->join(TransportDomain::class, 'td', Join::WITH, 't.id = td.transport AND td.domainId = :domainId')
            ->setParameter('domainId', $domainId);

        if ($showEmailTransportInCart === false) {
            $queryBuilder->andWhere('t.transportType != :transportEmailType');
            $queryBuilder->setParameter('transportEmailType', Transport::TYPE_EMAIL);
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
