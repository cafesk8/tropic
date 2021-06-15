<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20210616052226 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('
            CREATE TABLE products_bestseller (
                domain_id INT NOT NULL,
                product_id INT NOT NULL,
                position INT NOT NULL,
                PRIMARY KEY(product_id, domain_id)
            )');
        $this->sql('CREATE INDEX IDX_821390DA4584665A ON products_bestseller (product_id)');
        $this->sql('
            ALTER TABLE
                products_bestseller
            ADD
                CONSTRAINT FK_821390DA4584665A FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
