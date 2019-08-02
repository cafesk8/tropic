<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190416130355 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->sql('
            CREATE TABLE product_store_stocks (
                product_id INT NOT NULL,
                store_id INT NOT NULL,
                stock_quantity INT DEFAULT NULL,
                PRIMARY KEY(product_id, store_id)
            )');
        $this->sql('CREATE INDEX IDX_BF442CF84584665A ON product_store_stocks (product_id)');
        $this->sql('CREATE INDEX IDX_BF442CF8B092A811 ON product_store_stocks (store_id)');
        $this->sql('
            ALTER TABLE
                product_store_stocks
            ADD
                CONSTRAINT FK_BF442CF84584665A FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->sql('
            ALTER TABLE
                product_store_stocks
            ADD
                CONSTRAINT FK_BF442CF8B092A811 FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
