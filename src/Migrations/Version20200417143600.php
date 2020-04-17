<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200417143600 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->sql('ALTER TABLE customer_users DROP COLUMN IF EXISTS member_of_loyalty_program;');
        $this->sql('UPDATE promo_codes SET user_type = \'logged_users\' WHERE user_type = \'loyalty_program_member_users\';');
        $this->sql('ALTER TABLE orders DROP COLUMN IF EXISTS member_of_loyalty_program;');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
