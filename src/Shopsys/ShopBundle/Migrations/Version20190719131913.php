<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190719131913 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->sql('ALTER TABLE cart_items ADD gift_by_product_id INT DEFAULT NULL');
        $this->sql('
            ALTER TABLE
                cart_items
            ADD
                CONSTRAINT FK_BEF484457EC609D3 FOREIGN KEY (gift_by_product_id) REFERENCES products (id) ON DELETE
            SET
                NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->sql('CREATE INDEX IDX_BEF484457EC609D3 ON cart_items (gift_by_product_id)');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
