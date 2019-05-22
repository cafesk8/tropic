<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\MainVariantGroup;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter;
use Shopsys\FrameworkBundle\Model\Product\Product;

class MainVariantGroupFacade
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroupRepository
     */
    private $mainVariantGroupRepository;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroupRepository $mainVariantGroupRepository
     */
    public function __construct(EntityManagerInterface $entityManager, MainVariantGroupRepository $mainVariantGroupRepository)
    {
        $this->entityManager = $entityManager;
        $this->mainVariantGroupRepository = $mainVariantGroupRepository;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter $distinguishingParameter
     * @param \Shopsys\ShopBundle\Model\Product\Product[] $products
     * @return \Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroup
     */
    public function createMainVariantGroup(Parameter $distinguishingParameter, array $products): MainVariantGroup
    {
        $mainVariantGroup = new MainVariantGroup($distinguishingParameter);
        $this->entityManager->persist($mainVariantGroup);

        foreach ($products as $product) {
            if ($product->isVariant() === false && $product->getMainVariantGroup() === null) {
                $product->setMainVariantGroup($mainVariantGroup);
            }
        }

        $this->entityManager->flush();

        return $mainVariantGroup;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @return \Shopsys\ShopBundle\Model\Product\Product[]
     */
    public function getProductsForMainVariantGroup(Product $product): array
    {
        return $this->mainVariantGroupRepository->getProductsForMainVariantGroup($product);
    }
}
