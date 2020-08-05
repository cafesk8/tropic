<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200602082741 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('ALTER TABLE payment_domains ADD minimum_order_price NUMERIC(20, 6) NOT NULL DEFAULT 0');
        $this->sql('COMMENT ON COLUMN payment_domains.minimum_order_price IS \'(DC2Type:money)\'');

        $this->sql('ALTER TABLE payment_domains ALTER minimum_order_price DROP DEFAULT');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
