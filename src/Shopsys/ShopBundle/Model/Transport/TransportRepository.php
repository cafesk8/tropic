<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Transport;

use Doctrine\ORM\Query\Expr\Join;
use Shopsys\FrameworkBundle\Model\Transport\TransportDomain;
use Shopsys\FrameworkBundle\Model\Transport\TransportRepository as BaseTransportRepository;

/**
 * @method \Shopsys\ShopBundle\Model\Transport\Transport[] getAll()
 * @method \Shopsys\ShopBundle\Model\Transport\Transport[] getAllByIds(array $transportIds)
 * @method \Shopsys\ShopBundle\Model\Transport\Transport[] getAllByDomainId(int $domainId)
 * @method \Shopsys\ShopBundle\Model\Transport\Transport[] getAllIncludingDeleted()
 * @method \Shopsys\ShopBundle\Model\Transport\Transport|null findById(int $id)
 * @method \Shopsys\ShopBundle\Model\Transport\Transport getById(int $id)
 */
class TransportRepository extends BaseTransportRepository
{
    /**
     * @return \Shopsys\ShopBundle\Model\Transport\Transport[]
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
     * @param bool $showEmailTransportInCart
     * @return \Shopsys\ShopBundle\Model\Transport\Transport[]
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
