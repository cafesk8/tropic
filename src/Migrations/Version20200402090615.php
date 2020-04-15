<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200402090615 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('ALTER TABLE pricing_groups ADD calculated_from_default BOOLEAN NOT NULL DEFAULT FALSE');
        $this->sql('ALTER TABLE pricing_groups ALTER calculated_from_default DROP DEFAULT');
        $this->sql('UPDATE pricing_groups SET calculated_from_default = TRUE WHERE internal_id = \'registered_customer\'');
        $this->sql('UPDATE pricing_groups SET internal_id = \'ordinary_customer\', name = \'Běžný zákazník\' WHERE id = 1');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
