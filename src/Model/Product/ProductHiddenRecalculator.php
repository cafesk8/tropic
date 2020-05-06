<?php

declare(strict_types=1);

namespace App\Model\Product;

use App\Model\Product\Group\ProductGroupFacade;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\ProductHiddenRecalculator as BaseProductHiddenRecalculator;

class ProductHiddenRecalculator extends BaseProductHiddenRecalculator
{
    /**
     * @var \App\Model\Product\Group\ProductGroupFacade
     */
    private $productGroupFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \App\Model\Product\Group\ProductGroupFacade $productGroupFacade
     */
    public function __construct(EntityManagerInterface $entityManager, ProductGroupFacade $productGroupFacade)
    {
        parent::__construct($entityManager);
        $this->productGroupFacade = $productGroupFacade;
    }

    /**
     * @param \App\Model\Product\Product $product
     */
    public function calculateHiddenForProduct(Product $product)
    {
        parent::calculateHiddenForProduct($product);

        foreach ($this->productGroupFacade->getAllByItem($product) as $productGroup) {
            parent::calculateHiddenForProduct($productGroup->getMainProduct());
        }
    }

    /**
     * @param \App\Model\Product\Product|null $product
     */
    protected function executeQuery(?Product $product = null)
    {
        if ($product === null || !$product->isPohodaProductTypeGroup()) {
            parent::executeQuery($product);
        } else {
            $hide = false;

            foreach ($product->getProductGroups() as $productGroup) {
                if ($productGroup->getItem()->getCalculatedSellingDenied()) {
                    $hide = true;
                    break;
                }
            }

            $qb = $this->em->createQueryBuilder()
                ->update(Product::class, 'p')
                ->set('p.calculatedHidden', $hide ? 'TRUE' : 'FALSE')
                ->where('p = :product')
                ->setParameter('product', $product);

            $qb->getQuery()->execute();
        }
    }
}
