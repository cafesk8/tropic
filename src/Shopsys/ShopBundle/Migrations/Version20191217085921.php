<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20191217085921 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('UPDATE transfers SET last_start_at = NULL WHERE identifier = \'import_product_store_stock_czech\';');
        $this->sql('UPDATE transfers SET last_start_at = NULL WHERE identifier = \'import_product_store_stock_slovak\';');
        $this->sql('UPDATE transfers SET last_start_at = NULL WHERE identifier = \'import_product_store_stock_german\';');
        $this->sql('UPDATE transfers SET last_start_at = NULL WHERE identifier = \'import_product_store_stock_changed\';');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
