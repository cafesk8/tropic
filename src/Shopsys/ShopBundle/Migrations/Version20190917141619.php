<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190917141619 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('ALTER TABLE transports ADD personal_take_type VARCHAR(50) NOT NULL DEFAULT \'none\'');
        $this->sql('UPDATE transports SET personal_take_type = \'store\' WHERE choose_store = TRUE');
        $this->sql('UPDATE transports SET personal_take_type = \'balikobot\' WHERE pickup_place = TRUE');
        $this->sql('ALTER TABLE transports ALTER personal_take_type DROP DEFAULT');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
