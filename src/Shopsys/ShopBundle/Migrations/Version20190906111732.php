<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190906111732 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('UPDATE products SET out_of_stock_action = \'hide\' WHERE variant_type = \'variant\';');
        $this->sql('UPDATE product_store_stocks SET stock_quantity = 0 WHERE stock_quantity < 0;');
        $this->sql('
                 UPDATE products SET stock_quantity = product_quantity_on_store.total_stock_quantity FROM 
                    (SELECT  p2.id AS productId, COALESCE(SUM(pss.stock_quantity),0) AS total_stock_quantity
                        FROM product_store_stocks AS pss
                        INNER JOIN products AS p2 ON pss.product_id = p2.id 
                        GROUP BY p2.id
                 ) AS product_quantity_on_store 
                 WHERE products.id= product_quantity_on_store.productId
         ');
        $this->sql('UPDATE products 
            SET calculated_hidden = CASE
            WHEN using_stock = TRUE
                AND variant_type <> \'main\'
                AND stock_quantity <= 0
                AND out_of_stock_action = \'hide\'
            THEN TRUE
                ELSE hidden
            END;
        ');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
