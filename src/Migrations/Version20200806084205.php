<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200806084205 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('ALTER TABLE transport_prices ADD action_active BOOLEAN NOT NULL DEFAULT FALSE');
        $this->sql('ALTER TABLE transport_prices ALTER action_active DROP DEFAULT');
        $this->sql('ALTER TABLE transport_prices ADD min_free_order_price NUMERIC(20, 6) DEFAULT NULL');
        $this->sql('ALTER TABLE transport_prices RENAME COLUMN min_order_price TO min_action_order_price');
        $this->sql('COMMENT ON COLUMN transport_prices.min_free_order_price IS \'(DC2Type:money)\'');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
