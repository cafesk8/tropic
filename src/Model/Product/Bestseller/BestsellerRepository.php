<?php

declare(strict_types=1);

namespace App\Model\Product\Bestseller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;

class BestsellerRepository
{
    private EntityManagerInterface $em;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     */
    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->em = $entityManager;
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getBestsellerRepository(): EntityRepository
    {
        return $this->em->getRepository(Bestseller::class);
    }

    /**
     * @param int $domainId
     * @return \App\Model\Product\Bestseller\Bestseller[]
     */
    public function getAllByDomainId(int $domainId): array
    {
        return $this->getBestsellerRepository()->findBy(['domainId' => $domainId], ['position' => 'ASC']);
    }

    /**
     * @param int $domainId
     * @return array[]
     */
    public function getProductIdsAndPosition(int $domainId): array
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('product_id', 'productId');
        $resultSetMapping->addScalarResult('position', 'position');

        return $this->em->createNativeQuery('SELECT product_id, position FROM products_bestseller WHERE domain_id = :domainId', $resultSetMapping)
            ->setParameter('domainId', $domainId)
            ->getResult();
    }
}