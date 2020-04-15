<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200406104829 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('
            CREATE TABLE products_price_bomb (
                domain_id INT NOT NULL,
                product_id INT NOT NULL,
                position INT NOT NULL,
                PRIMARY KEY(product_id, domain_id)
            )');
        $this->sql('CREATE INDEX IDX_2F0273B34584665A ON products_price_bomb (product_id)');
        $this->sql('
            ALTER TABLE
                products_price_bomb
            ADD
                CONSTRAINT FK_2F0273B34584665A FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
