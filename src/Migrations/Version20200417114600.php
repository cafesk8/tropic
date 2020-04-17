<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200417114600 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->sql('ALTER TABLE categories DROP COLUMN IF EXISTS displayed_in_horizontal_menu;');
        $this->sql('ALTER TABLE categories DROP COLUMN IF EXISTS displayed_in_first_column;');
        $this->sql('ALTER TABLE categories DROP COLUMN IF EXISTS legendary_category;');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
