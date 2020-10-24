<?php

declare(strict_types=1);

namespace App\Model\Product\StoreStock;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;

class ProductStoreStockRepository
{
    private EntityManagerInterface $em;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param int $productId
     * @param int $storeId
     * @param int $quantity
     */
    public function manualInsertStoreStock(int $productId, int $storeId, int $quantity): void
    {
        $query = $this->em->createNativeQuery(
            'INSERT INTO product_store_stocks (product_id, store_id, stock_quantity)
            VALUES (:productId, :storeId, :quantity)
            ON CONFLICT (product_id, store_id) DO UPDATE SET stock_quantity = :quantity',
            new ResultSetMapping()
        );

        $query->execute([
            'productId' => $productId,
            'storeId' => $storeId,
            'quantity' => $quantity,
        ]);
    }

    /**
     * @param int[] $productIds
     * @return array
     */
    public function getProductStockQuantities(array $productIds): array
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('product_id', 'productId');
        $resultSetMapping->addScalarResult('stock_quantity', 'stockQuantity');
        $resultSetMapping->addScalarResult('store_id', 'storeId');

        $query = $this->em->createNativeQuery(
            'SELECT product_id, stock_quantity, store_id
            FROM product_store_stocks
            WHERE product_id IN (:productIds)',
            $resultSetMapping
        )->setParameters([
            'productIds' => $productIds,
        ]);

        $stockQuantitiesResult = $query->getResult();
        $stockQuantities = [];
        foreach ($stockQuantitiesResult as $productResult) {
            $stockQuantities[(int)$productResult['productId']][(int)$productResult['storeId']] = (int)$productResult['stockQuantity'];
        }

        return $stockQuantities;
    }
}
