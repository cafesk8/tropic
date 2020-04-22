<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200421063754 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('ALTER TABLE product_domains ADD generate_to_mergado_xml_feed BOOLEAN NOT NULL DEFAULT TRUE');
        $this->sql('ALTER TABLE product_domains ALTER generate_to_mergado_xml_feed DROP DEFAULT');
        $this->sql('ALTER TABLE products DROP COLUMN generate_to_hs_sport_xml_feed');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
