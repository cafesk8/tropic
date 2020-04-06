<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200406074321 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->sql('DELETE FROM transfers WHERE identifier = \'export_orders\'');
        $this->sql('DELETE FROM transfers WHERE identifier = \'import_product_prices\'');
        $this->sql('DELETE FROM transfers WHERE identifier = \'import_order_statuses\'');
        $this->sql('DELETE FROM transfers WHERE identifier = \'export_customers\'');
        $this->sql('DELETE FROM transfers WHERE identifier = \'import_customers_pricing_groups\'');
        $this->sql('DELETE FROM transfers WHERE identifier = \'import_product_prices_changed\'');
        $this->sql('DELETE FROM transfers WHERE identifier = \'import_product_store_stock_changed\'');
        $this->sql('DELETE FROM transfers WHERE identifier = \'import_customers_changed\'');
        $this->sql('DELETE FROM transfers WHERE identifier = \'import_customers_czech\'');
        $this->sql('DELETE FROM transfers WHERE identifier = \'import_customers_slovak\'');
        $this->sql('DELETE FROM transfers WHERE identifier = \'import_customers_german\'');
        $this->sql('DELETE FROM transfers WHERE identifier = \'import_product_store_stock_czech\'');
        $this->sql('DELETE FROM transfers WHERE identifier = \'import_product_store_stock_slovak\'');
        $this->sql('DELETE FROM transfers WHERE identifier = \'import_product_store_stock_german\'');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
