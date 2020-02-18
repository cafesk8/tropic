<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190510104015 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->sql('ALTER TABLE categories ADD pre_listing_category BOOLEAN DEFAULT FALSE NOT NULL');
        $this->sql('ALTER TABLE categories ALTER COLUMN pre_listing_category DROP DEFAULT');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
