<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200419090944 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('
            CREATE TABLE order_discount_levels (
                id SERIAL NOT NULL,
                enabled BOOLEAN NOT NULL,
                price_level_with_vat NUMERIC(20, 6) NOT NULL,
                domain_id INT NOT NULL,
                discount_percent INT NOT NULL,
                PRIMARY KEY(id)
            )');
        $this->sql('COMMENT ON COLUMN order_discount_levels.price_level_with_vat IS \'(DC2Type:money)\'');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
