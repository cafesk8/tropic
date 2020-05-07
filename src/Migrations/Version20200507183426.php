<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use PDO;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200507183426 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('ALTER TABLE products ADD real_stock_quantity INT DEFAULT NULL');

        $products = $this->sql('SELECT id, stock_quantity, amount_multiplier FROM products')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($products as $product) {
            if ($product['stock_quantity'] % $product['amount_multiplier'] !== 0) {
                $realStockQuantity = (int)floor($product['stock_quantity'] / $product['amount_multiplier']) * $product['amount_multiplier'];
            } else {
                $realStockQuantity = $product['stock_quantity'];
            }

            $this->sql('UPDATE products SET real_stock_quantity = :realStockQuantity WHERE id = :productId', [
                'realStockQuantity' => $realStockQuantity,
                'productId' => $product['id'],
            ]);
        }
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
