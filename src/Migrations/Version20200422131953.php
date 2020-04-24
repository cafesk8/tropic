<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200422131953 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('
            CREATE TABLE product_groups (
                main_product_id INT NOT NULL,
                item_id INT NOT NULL,
                item_count INT NOT NULL,
                PRIMARY KEY(main_product_id, item_id)
            )');
        $this->sql('CREATE INDEX IDX_921178D47D7C1239 ON product_groups (main_product_id)');
        $this->sql('CREATE INDEX IDX_921178D4126F525E ON product_groups (item_id)');
        $this->sql('
            ALTER TABLE
                product_groups
            ADD
                CONSTRAINT FK_921178D47D7C1239 FOREIGN KEY (main_product_id) REFERENCES products (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->sql('
            ALTER TABLE
                product_groups
            ADD
                CONSTRAINT FK_921178D4126F525E FOREIGN KEY (item_id) REFERENCES products (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
