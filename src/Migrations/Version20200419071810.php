<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200419071810 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->sql(
            'INSERT INTO "transfers" ("identifier", "name", "in_progress", "frequency", "enabled")
            VALUES (\'export_orders\', \'Export objednávek\', \'0\', \'každých 5 minut\', \'1\');'
        );

        $this->sql(
            'INSERT INTO "transfers" ("identifier", "name", "in_progress", "frequency", "enabled")
            VALUES (\'export_customers\', \'Export adresáře\', \'0\', \'každých 5 minut\', \'1\');'
        );
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
