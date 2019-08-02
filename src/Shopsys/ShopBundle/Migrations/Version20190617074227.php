<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190617074227 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->sql('ALTER TABLE users ADD transfer_id VARCHAR(255) DEFAULT NULL');
        $this->sql('ALTER TABLE users ADD branch_number VARCHAR(255) DEFAULT NULL');
        $this->sql('CREATE UNIQUE INDEX UNIQ_1483A5E9537048AF ON users (transfer_id)');
        $this->sql('CREATE UNIQUE INDEX UNIQ_1483A5E963F16A01 ON users (branch_number)');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
