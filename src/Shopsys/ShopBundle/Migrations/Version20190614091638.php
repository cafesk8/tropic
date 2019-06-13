<?php

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190614091638 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->sql('ALTER TABLE stores ADD country_id INT NOT NULL');

        $this->sql('UPDATE stores SET postcode = \'\' WHERE postcode IS NULL');
        $this->sql('ALTER TABLE stores ALTER postcode SET NOT NULL');

        $this->sql('
            ALTER TABLE
                stores
            ADD
                CONSTRAINT FK_D5907CCCF92F3E70 FOREIGN KEY (country_id) REFERENCES countries (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->sql('CREATE INDEX IDX_D5907CCCF92F3E70 ON stores (country_id)');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
