<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200311193302 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('ALTER TABLE categories ADD pohoda_id INT DEFAULT NULL');
        $this->sql('ALTER TABLE categories ADD updated_by_pohoda_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->sql('CREATE UNIQUE INDEX UNIQ_3AF3466828B287A8 ON categories (pohoda_id)');

        $this->sql(
            'INSERT INTO "transfers" ("identifier", "name", "in_progress", "frequency", "enabled")
            VALUES (\'import_categories\', \'Import kategorií\', \'0\', \'každých 15 minut\', \'1\');'
        );

        $this->sql(
            'INSERT INTO "transfers" ("identifier", "name", "in_progress", "frequency", "enabled")
            VALUES (\'import_changed_category_ids\', \'Import ID kategorií z IS do fronty ke zpracování\', \'0\', \'každých 5 minut\', \'1\');'
        );

        $this->sql('
            CREATE TABLE pohoda_changed_categories_queue (
                pohoda_id INT NOT NULL,
                inserted_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(pohoda_id)
            )');
        $this->sql('CREATE INDEX IDX_pohoda_changed_categories_queue ON pohoda_changed_categories_queue (pohoda_id)');

        $this->sql('ALTER TABLE categories ADD pohoda_parent_id INT DEFAULT NULL');

        $this->sql('ALTER TABLE categories ADD pohoda_position INT DEFAULT NULL');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
