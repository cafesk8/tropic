<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\MainVariantGroup;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter;

class MainVariantGroupFacade
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
}
