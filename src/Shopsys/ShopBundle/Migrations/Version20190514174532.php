<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190514174532 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->sql('
            CREATE TABLE transfers (
                id SERIAL NOT NULL,
                identifier VARCHAR(100) NOT NULL,
                name VARCHAR(100) NOT NULL,
                last_start_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                last_finish_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                in_progress BOOLEAN NOT NULL,
                frequency VARCHAR(100) NOT NULL,
                enabled BOOLEAN NOT NULL,
                PRIMARY KEY(id)
            )');
        $this->sql('CREATE UNIQUE INDEX UNIQ_802A3918772E836A ON transfers (identifier)');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
