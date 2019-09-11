<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190911223915 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('
            CREATE TABLE user_transfer_ids_and_eans (
                transfer_id VARCHAR(255) NOT NULL,
                ean VARCHAR(13) NOT NULL,
                customer_id INT NOT NULL,
                PRIMARY KEY(customer_id, transfer_id, ean)
            )');
        $this->sql('CREATE INDEX IDX_891E458F9395C3F3 ON user_transfer_ids_and_eans (customer_id)');
        $this->sql('
            ALTER TABLE
                user_transfer_ids_and_eans
            ADD
                CONSTRAINT FK_891E458F9395C3F3 FOREIGN KEY (customer_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->sql('DROP INDEX email_domain');
        $this->sql('CREATE UNIQUE INDEX email_domain ON users (email, domain_id, ean)');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
