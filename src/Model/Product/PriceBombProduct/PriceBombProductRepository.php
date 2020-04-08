<?php

declare(strict_types=1);

namespace App\Model\Product\PriceBombProduct;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Product\ProductRepository;

class PriceBombProductRepository
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $em;

    /**
     * @var \App\Model\Product\ProductRepository
     */
    protected $productRepository;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \App\Model\Product\ProductRepository $productRepository
     */
    public function __construct(EntityManagerInterface $entityManager, ProductRepository $productRepository)
    {
        $this->em = $entityManager;
        $this->productRepository = $productRepository;
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getPriceBombProductRepository(): EntityRepository
    {
        return $this->em->getRepository(PriceBombProduct::class);
    }

    /**
     * @param int $domainId
     * @return \App\Model\Product\PriceBombProduct\PriceBombProduct[]
     */
    public function getAll(int $domainId): array
    {
        return $this->getPriceBombProductRepository()->findBy(['domainId' => $domainId], ['position' => 'ASC']);
    }

    /**
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param int|null $limit
     * @return \App\Model\Product\Product[]
     */
    public function getSellableProductsUsingStockInStockForPriceBombProductsOnDomain(int $domainId, PricingGroup $pricingGroup, ?int $limit = null): array
    {
        $queryBuilder = $this->productRepository->getAllSellableUsingStockInStockQueryBuilder($domainId, $pricingGroup);

        $queryBuilder
            ->join(PriceBombProduct::class, 'pbp', Join::WITH, 'pbp.product = p')
            ->andWhere('pbp.domainId = :domainId')
            ->andWhere('pbp.domainId = prv.domainId')
            ->orderBy('pbp.position')
            ->setParameter('domainId', $domainId);

        if ($limit !== null) {
            $queryBuilder->setMaxResults($limit);
        }

        //die(var_dump($queryBuilder->getQuery()->getSQL()));

        return $queryBuilder->getQuery()->execute();
    }
}
