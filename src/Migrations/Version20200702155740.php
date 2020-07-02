<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200702155740 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('
            CREATE TABLE pohoda_changed_order_statuses_queue (
                pohoda_id INT NOT NULL,
                inserted_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(pohoda_id)
            )');
        $this->sql('CREATE INDEX IDX_pohoda_changed_order_statuses_queue ON pohoda_changed_order_statuses_queue (pohoda_id)');

        $this->sql(
            'INSERT INTO "transfers" ("identifier", "name", "in_progress", "frequency", "enabled")
            VALUES (\'import_order_statuses\', \'Import stavů objednávek\', \'0\', \'každých 15 minut\', \'1\');'
        );
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
