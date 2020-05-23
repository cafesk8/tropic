<?php

declare(strict_types=1);

namespace App\Model\Product\Flag;

use App\Model\Product\Product;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class ProductFlagRepository
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $em;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @param \App\Model\Product\Flag\Flag $flag
     */
    public function deleteByFlag(Flag $flag): void
    {
        $this->getProductFlagRepository()
            ->createQueryBuilder('pf')
            ->delete(ProductFlag::class, 'pf')
            ->where('pf.flag = :flag')
            ->setParameter('flag', $flag)
            ->getQuery()->execute();
    }

    /**
     * @param \App\Model\Product\Product $product
     */
    public function deleteByProduct(Product $product): void
    {
        $this->getProductFlagRepository()
            ->createQueryBuilder('pf')
            ->delete(ProductFlag::class, 'pf')
            ->where('pf.product = :product')
            ->setParameter('product', $product)
            ->getQuery()->execute();
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    private function getProductFlagRepository(): EntityRepository
    {
        return $this->em->getRepository(ProductFlag::class);
    }
}
