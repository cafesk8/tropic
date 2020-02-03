<?php

declare(strict_types=1);

namespace App\Model\Order\PromoCode;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class PromoCodeLimitRepository
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param int $id
     * @return \App\Model\Order\PromoCode\PromoCodeLimit
     */
    public function getById(int $id): PromoCodeLimit
    {
        return $this->getPromoCodeLimitRepository()->find($id);
    }

    /**
     * @param \App\Model\Order\PromoCode\PromoCode $promoCode
     * @return \App\Model\Order\PromoCode\PromoCodeLimit[]
     */
    public function getByPromoCode(PromoCode $promoCode): array
    {
        return $this->getPromoCodeLimitRepository()->findBy(['promoCode' => $promoCode]);
    }

    /**
     * @param \App\Model\Order\PromoCode\PromoCode $promoCode
     */
    public function deleteByPromoCode(PromoCode $promoCode)
    {
        $this->getPromoCodeLimitRepository()
            ->createQueryBuilder('pcl')
            ->delete(PromoCodeLimit::class, 'pcl')
            ->where('pcl.promoCode = :promoCode')
            ->setParameter('promoCode', $promoCode)
            ->getQuery()->execute();
    }

    /**
     * @return \App\Model\Order\PromoCode\PromoCodeLimit[]
     */
    public function getAll(): array
    {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
            ->select('pcl')
            ->from(PromoCodeLimit::class, 'pcl');

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    private function getPromoCodeLimitRepository(): EntityRepository
    {
        return $this->em->getRepository(PromoCodeLimit::class);
    }
}
