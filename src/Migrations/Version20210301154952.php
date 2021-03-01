<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20210301154952 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('ALTER TABLE products ADD transport_fee_multiplier INT DEFAULT NULL');
        $this->sql('ALTER TABLE product_domains ADD transport_fee NUMERIC(20, 6) DEFAULT NULL');
        $this->sql('COMMENT ON COLUMN product_domains.transport_fee IS \'(DC2Type:money)\'');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
