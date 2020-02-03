<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\PromoCode;

use Doctrine\ORM\EntityManagerInterface;

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
     * @return \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeLimit
     */
    public function getById(int $id)
    {
        return $this->getPromoCodeLimitRepository()->find($id);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode $promoCode
     * @return \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeLimit[]
     */
    public function getByPromoCode(PromoCode $promoCode)
    {
        return $this->getPromoCodeLimitRepository()->findBy(['promoCode' => $promoCode]);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode $promoCode
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
     * @return \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeLimit[]
     */
    public function getAll()
    {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
            ->select('pcl')
            ->from(PromoCodeLimit::class, 'pcl');

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository|\Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeLimitRepository
     */
    private function getPromoCodeLimitRepository()
    {
        return $this->em->getRepository(PromoCodeLimit::class);
    }
}
