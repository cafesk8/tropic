<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200730104420 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('ALTER TABLE product_groups RENAME TO product_sets');
        $this->sql('ALTER INDEX idx_921178d47d7c1239 RENAME TO IDX_196567707D7C1239;');
        $this->sql('ALTER INDEX idx_921178d4126f525e RENAME TO IDX_19656770126F525E;');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
