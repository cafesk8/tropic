<?php

declare(strict_types=1);

namespace App\Model\Product\Group;

use App\Model\Product\Product;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class ProductGroupRepository
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
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getProductGroupRepository(): EntityRepository
    {
        return $this->em->getRepository(ProductGroup::class);
    }

    /**
     * @param \App\Model\Product\Product $mainProduct
     * @return \App\Model\Product\Group\ProductGroup[]
     */
    public function getAllByMainProduct(Product $mainProduct): array
    {
        return $this->getProductGroupRepository()->findBy(['mainProduct' => $mainProduct]);
    }
}
