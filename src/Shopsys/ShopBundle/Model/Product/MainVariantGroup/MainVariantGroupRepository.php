<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\MainVariantGroup;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter;
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
     * @return \Doctrine\ORM\EntityRepository|\Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroupRepository
     */
    protected function getMainVariantGroupRepository(): EntityRepository
    {
        return $this->entityManager->getRepository(MainVariantGroup::class);
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

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter $parameter
     * @return \Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroup[]
     */
    public function getByDistinguishingParameter(Parameter $parameter): array
    {
        return $this->getMainVariantGroupRepository()->findBy([
            'distinguishingParameter' => $parameter,
        ]);
    }
}
