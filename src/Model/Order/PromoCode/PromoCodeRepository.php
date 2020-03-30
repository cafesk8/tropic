<?php

declare(strict_types=1);

namespace App\Model\Order\PromoCode;

use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeRepository as BasePromoCodeRepository;

/**
 * @method \App\Model\Order\PromoCode\PromoCode|null findById(int $promoCodeId)
 * @method \App\Model\Order\PromoCode\PromoCode|null findByCode(string $code)
 * @method \App\Model\Order\PromoCode\PromoCode getById(int $promoCodeId)
 * @method \App\Model\Order\PromoCode\PromoCode[] getAll()
 */
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
     * @param \App\Model\Order\PromoCode\PromoCode[] $promoCodes
     */
//    public function activate(array $promoCodes): void
//    {
//        $this->getAllQueryBuilder()
//            ->update(PromoCode::class, 'pc')
//            ->set('pc.usageLimit', 1)
//            ->set('pc.validTo', '\'' . date('Y-m-d H:i:s', strtotime('+365 days')) . '\'')
//            ->where('pc IN (:promoCodes)')
//            ->setParameter('promoCodes', $promoCodes)
//            ->getQuery()->execute();
//    }

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
