<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\PromoCode;

use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeRepository as BasePromoCodeRepository;

class PromoCodeRepository extends BasePromoCodeRepository
{
    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getAllQueryBuilder(): QueryBuilder
    {
        return $this->getPromoCodeRepository()
            ->createQueryBuilder('pc');
    }

    /**
     * @return string[]
     */
    public function getAllPromoCodeCodes(): array
    {
        $queryBuilder = $this->getAllQueryBuilder()
            ->select('pc.code');

        return array_column($queryBuilder->getQuery()->execute(), 'code');
    }

    /**
     * @param string $prefix
     */
    public function deleteByPrefix(string $prefix): void
    {
        $this->getPromoCodeRepository()
            ->createQueryBuilder('pc')
            ->delete(PromoCode::class, 'pc')
            ->where('pc.prefix = :prefix')
            ->setParameter('prefix', $prefix)
            ->getQuery()->execute();
    }
}
