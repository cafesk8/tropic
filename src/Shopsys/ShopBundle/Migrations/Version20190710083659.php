<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190710083659 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->sql('ALTER TABLE promo_codes ADD nominal_discount NUMERIC(20, 6) DEFAULT NULL');
        $this->sql('COMMENT ON COLUMN promo_codes.nominal_discount IS \'(DC2Type:money)\'');

        $this->sql('ALTER TABLE promo_codes ADD use_nominal_discount BOOLEAN NOT NULL DEFAULT FALSE');
        $this->sql('ALTER TABLE promo_codes ALTER use_nominal_discount DROP DEFAULT');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
