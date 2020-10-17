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
}
