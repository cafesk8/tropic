<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190907110005 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('ALTER TABLE order_statuses ADD check_order_ready_status BOOLEAN NOT NULL DEFAULT FALSE');
        $this->sql('ALTER TABLE order_statuses ALTER check_order_ready_status DROP DEFAULT');

        $this->sql('UPDATE order_statuses SET check_order_ready_status = TRUE WHERE id = 2 OR type IN (5,6)');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
