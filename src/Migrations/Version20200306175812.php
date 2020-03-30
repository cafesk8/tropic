<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200306175812 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $memberOfLoyaltyProgramColumnExists = $this->sql('SELECT EXISTS (SELECT 1 
            FROM information_schema.columns 
            WHERE table_name=\'orders\' AND column_name=\'member_of_loyalty_program\')')->fetchColumn(0);
        if ($memberOfLoyaltyProgramColumnExists === false) {
            $this->sql('ALTER TABLE orders ADD member_of_loyalty_program BOOLEAN NOT NULL DEFAULT false');
            $this->sql('ALTER TABLE orders ALTER member_of_loyalty_program DROP DEFAULT');
        }
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
