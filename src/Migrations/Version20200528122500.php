<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\FrameworkBundle\Migrations\MultidomainMigrationTrait;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200528122500 extends AbstractMigration
{
    use MultidomainMigrationTrait;

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('ALTER TABLE product_flags DROP CONSTRAINT FK_AA593AE64584665A');
        $this->sql('ALTER TABLE product_flags DROP CONSTRAINT FK_AA593AE6919FE4E5');
        $this->sql('
            ALTER TABLE
                product_flags
            ADD
                CONSTRAINT FK_AA593AE64584665A FOREIGN KEY (product_id) REFERENCES products (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->sql('
            ALTER TABLE
                product_flags
            ADD
                CONSTRAINT FK_AA593AE6919FE4E5 FOREIGN KEY (flag_id) REFERENCES flags (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
