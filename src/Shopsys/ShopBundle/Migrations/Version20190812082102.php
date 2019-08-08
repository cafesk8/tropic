<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190812082102 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('ALTER TABLE promo_codes ADD type VARCHAR(255) NOT NULL DEFAULT \'promoCode\'');
        $this->sql('ALTER TABLE promo_codes ADD certificate_value NUMERIC(20, 6) DEFAULT NULL');
        $this->sql('ALTER TABLE promo_codes ADD certificate_sku VARCHAR(255) DEFAULT NULL');
        $this->sql('COMMENT ON COLUMN promo_codes.certificate_value IS \'(DC2Type:money)\'');
        $this->sql('ALTER TABLE promo_codes ALTER type DROP DEFAULT');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
