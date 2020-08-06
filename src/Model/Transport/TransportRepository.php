<?php

declare(strict_types=1);

namespace App\Model\Transport;

use Doctrine\ORM\Query\Expr\Join;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Transport\TransportDomain;
use Shopsys\FrameworkBundle\Model\Transport\TransportRepository as BaseTransportRepository;

/**
 * @method \App\Model\Transport\Transport[] getAll()
 * @method \App\Model\Transport\Transport[] getAllByIds(array $transportIds)
 * @method \App\Model\Transport\Transport[] getAllByDomainId(int $domainId)
 * @method \App\Model\Transport\Transport[] getAllIncludingDeleted()
 * @method \App\Model\Transport\Transport|null findById(int $id)
 * @method \App\Model\Transport\Transport getById(int $id)
 * @method \App\Model\Transport\Transport getOneByUuid(string $uuid)
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
     * @param bool $oversizedTransportAllowed
     * @param bool $bulkyTransportAllowed
     * @return \App\Model\Transport\Transport[]
     */
    public function getAllByDomainIdAndTransportEmailType(
        int $domainId,
        bool $showEmailTransportInCart,
        bool $oversizedTransportAllowed,
        bool $bulkyTransportAllowed
    ): array {
        $queryBuilder = $this->getQueryBuilderForAll()
            ->join(TransportDomain::class, 'td', Join::WITH, 't.id = td.transport AND td.domainId = :domainId')
            ->setParameter('domainId', $domainId);

        if ($showEmailTransportInCart === false) {
            $queryBuilder->andWhere('t.transportType != :transportEmailType');
            $queryBuilder->setParameter('transportEmailType', Transport::TYPE_EMAIL);
        }

        if ($oversizedTransportAllowed) {
            $queryBuilder->andWhere('t.oversizedAllowed = true');
        }

        if ($bulkyTransportAllowed) {
            $queryBuilder->andWhere('t.bulkyAllowed = true');
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    public function getMinOrderPriceForFreeTransport(int $domainId): ?Money
    {
        $freeResult = $this->getTransportRepository()->createQueryBuilder('t')
            ->join(TransportPrice::class, 'tp', Join::WITH, 'tp.transport = t')
            ->where('t.deleted = FALSE')
            ->andWhere('tp.domainId = :domainId')
            ->andWhere('tp.minFreeOrderPrice IS NOT NULL')
            ->setParameter('domainId', $domainId)
            ->orderBy('tp.minFreeOrderPrice', 'ASC')
            ->select('tp.minFreeOrderPrice')
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        $actionResult = $this->getTransportRepository()->createQueryBuilder('t')
            ->join(TransportPrice::class, 'tp', Join::WITH, 'tp.transport = t')
            ->where('t.deleted = FALSE')
            ->andWhere('tp.actionActive = TRUE')
            ->andWhere('tp.actionPrice = 0')
            ->andWhere('tp.domainId = :domainId')
            ->andWhere('(tp.actionDateFrom <= :currentDate OR tp.actionDateFrom IS NULL)')
            ->andWhere('(DATE_ADD(tp.actionDateTo, 1, \'day\') >= :currentDate OR tp.actionDateTo IS NULL)')
            ->setParameter('domainId', $domainId)
            ->setParameter('currentDate', date('Y-m-d'))
            ->orderBy('tp.minActionOrderPrice', 'ASC')
            ->select('tp.minActionOrderPrice')
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        if (!isset($freeResult['minFreeOrderPrice'])) {
            return $actionResult['minActionOrderPrice'] ?? null;
        } elseif (!isset($actionResult['minActionOrderPrice'])) {
            return $freeResult['minFreeOrderPrice'] ?? null;
        }

        return $actionResult['minActionOrderPrice']->isGreaterThan($freeResult['minFreeOrderPrice']) ? $freeResult['minFreeOrderPrice'] : $actionResult['minActionOrderPrice'];
    }
}
