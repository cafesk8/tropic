<?php

declare(strict_types=1);

namespace App\Model\Product;

use App\Model\Product\Set\ProductSet;
use App\Model\Product\Set\ProductSetFacade;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\ProductSellingDeniedRecalculator as BaseProductSellingDeniedRecalculator;

/**
 * @method calculateSellingDeniedForProduct(\App\Model\Product\Product $product)
 * @method propagateMainVariantSellingDeniedToVariants(\App\Model\Product\Product[] $products)
 * @method propagateVariantsSellingDeniedToMainVariant(\App\Model\Product\Product[] $products)
 */
class ProductSellingDeniedRecalculator extends BaseProductSellingDeniedRecalculator
{
    /**
     * @var \App\Model\Product\Set\ProductSetFacade
     */
    private $productSetFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \App\Model\Product\Set\ProductSetFacade $productSetFacade
     */
    public function __construct(EntityManagerInterface $entityManager, ProductSetFacade $productSetFacade)
    {
        parent::__construct($entityManager);
        $this->productSetFacade = $productSetFacade;
    }

    /**
     * @inheritDoc
     */
    protected function calculate(array $products = [])
    {
        parent::calculate($products);
        $this->propagateSellingDeniedFromSetItems($products);
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return \App\Model\Product\Product[]
     */
    protected function getProductsForCalculations(Product $product)
    {
        /** @var \App\Model\Product\Product[] $products */
        $products = parent::getProductsForCalculations($product);
        $productSets = $this->productSetFacade->getAllByItem($product);

        foreach ($productSets as $productSet) {
            $products[] = $productSet->getMainProduct();
        }

        return $products;
    }

    /**
     * @param \App\Model\Product\Product[] $products
     */
    private function propagateSellingDeniedFromSetItems(array $products)
    {
        foreach ($products as $product) {
            $productIds = array_map(function (ProductSet $productSet) {
                return $productSet->getItem()->getId();
            }, $product->getProductSets());

            if (count($productIds) < 1) {
                continue;
            }

            $results = $this->em->createQueryBuilder()
                ->select('p.id')
                ->from(Product::class, 'p')
                ->where('p.id IN (:productIds)')
                ->andWhere('p.calculatedSellingDenied = TRUE')
                ->setParameter('productIds', $productIds)
                ->getQuery()->getResult();

            $qb = $this->em->createQueryBuilder()
                ->update(Product::class, 'p')
                ->set('p.sellingDenied', count($results) > 0 ? 'TRUE' : 'FALSE')
                ->where('p = :product')
                ->setParameter('product', $product);

            $qb->getQuery()->execute();
        }
    }

    /**
     * @param array $products
     */
    protected function calculateIndependent(array $products): void
    {
        $qb = $this->em->createQueryBuilder()
            ->update(\App\Model\Product\Product::class, 'p')
            ->set('p.calculatedSellingDenied', '
                CASE
                    WHEN p.realStockQuantity <= 0
                        AND p.variantType IN (:variantTypes)
                    THEN TRUE
                    ELSE p.sellingDenied
                END
            ')
            ->setParameters([
               'variantTypes' => [Product::VARIANT_TYPE_VARIANT, Product::VARIANT_TYPE_NONE],
            ]);

        if (count($products) > 0) {
            $qb->andWhere('p IN (:products)')->setParameter('products', $products);
        }
        $qb->getQuery()->execute();
    }
}
