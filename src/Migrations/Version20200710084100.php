<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200710084100 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('ALTER TABLE transport_prices ADD action_price NUMERIC(20, 6) DEFAULT NULL');
        $this->sql('ALTER TABLE transport_prices ADD min_order_price NUMERIC(20, 6) DEFAULT NULL');
        $this->sql('ALTER TABLE transport_prices ADD action_date_from TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->sql('ALTER TABLE transport_prices ADD action_date_to TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->sql('COMMENT ON COLUMN transport_prices.action_price IS \'(DC2Type:money)\'');
        $this->sql('COMMENT ON COLUMN transport_prices.min_order_price IS \'(DC2Type:money)\'');
        $this->sql('ALTER TABLE transport_prices ALTER action_price DROP DEFAULT');
        $this->sql('ALTER TABLE transport_prices ALTER min_order_price DROP DEFAULT');
        $this->sql('ALTER TABLE transport_prices ALTER action_date_from DROP DEFAULT');
        $this->sql('ALTER TABLE transport_prices ALTER action_date_to DROP DEFAULT');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
