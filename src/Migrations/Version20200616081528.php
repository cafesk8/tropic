<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200616081528 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('
            CREATE TABLE order_item_source_stocks (
                order_item_id INT NOT NULL,
                stock_id INT NOT NULL,
                quantity INT NOT NULL,
                PRIMARY KEY(order_item_id, stock_id)
            )');
        $this->sql('CREATE INDEX IDX_F009FE0EE415FB15 ON order_item_source_stocks (order_item_id)');
        $this->sql('CREATE INDEX IDX_F009FE0EDCD6110 ON order_item_source_stocks (stock_id)');
        $this->sql('
            ALTER TABLE
                order_item_source_stocks
            ADD
                CONSTRAINT FK_F009FE0EE415FB15 FOREIGN KEY (order_item_id) REFERENCES order_items (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->sql('
            ALTER TABLE
                order_item_source_stocks
            ADD
                CONSTRAINT FK_F009FE0EDCD6110 FOREIGN KEY (stock_id) REFERENCES stores (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
