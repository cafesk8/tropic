<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200207180842 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('DROP INDEX email_domain');
        $this->sql('ALTER TABLE customer_users ADD transfer_id VARCHAR(255) DEFAULT NULL');
        $this->sql('ALTER TABLE customer_users ADD ean VARCHAR(13) DEFAULT NULL');
        $this->sql('ALTER TABLE customer_users ADD member_of_bushman_club BOOLEAN NOT NULL');
        $this->sql('ALTER TABLE customer_users ADD export_status VARCHAR(50) NOT NULL');
        $this->sql('ALTER TABLE customer_users ADD exported_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->sql('ALTER TABLE customer_users ADD pricing_group_updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->sql('ALTER TABLE customer_users ALTER first_name TYPE VARCHAR(60)');
        $this->sql('ALTER TABLE customer_users ALTER last_name TYPE VARCHAR(30)');
        $this->sql('ALTER TABLE customer_users ALTER email TYPE VARCHAR(50)');
        $this->sql('CREATE UNIQUE INDEX email_domain ON customer_users (email, domain_id, ean)');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
