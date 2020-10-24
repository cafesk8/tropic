<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20201016084812 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('
            CREATE TABLE pohoda_products_external_stock_quantity_queue (
                pohoda_product_id INT NOT NULL,
                inserted_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(pohoda_product_id)
            )');
        $this->sql('CREATE INDEX IDX_pohoda_products_external_stock_quantity_queue ON pohoda_products_external_stock_quantity_queue (pohoda_product_id)');

        $this->sql('INSERT INTO "transfers" ("identifier", "name", "in_progress", "frequency", "enabled")
            VALUES (\'import_products_external_stock_quantity\', \'Import skladových zásob externího skladu z IS\', \'0\', \'každých 5 minut\', \'1\');');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
