<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\MainVariantGroup;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\ShopBundle\Model\Product\Product;

class MainVariantGroupRepository
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @return \Shopsys\ShopBundle\Model\Product\Product[]
     */
    public function getProductsForMainVariantGroup(Product $product): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('p')
            ->from(Product::class, 'p')
            ->where('p.mainVariantGroup = :mainVariantGroup')
            ->setParameter('mainVariantGroup', $product->getMainVariantGroup())
            ->getQuery()
            ->getResult();
    }
}
