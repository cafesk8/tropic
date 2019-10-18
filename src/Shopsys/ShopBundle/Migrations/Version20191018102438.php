<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20191018102438 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('
            CREATE TABLE transfer_issues (
                id SERIAL NOT NULL,
                transfer_id INT NOT NULL,
                message TEXT NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )');
        $this->sql('CREATE INDEX IDX_BF6E22B0537048AF ON transfer_issues (transfer_id)');
        $this->sql('
            ALTER TABLE
                transfer_issues
            ADD
                CONSTRAINT FK_BF6E22B0537048AF FOREIGN KEY (transfer_id) REFERENCES transfers (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
