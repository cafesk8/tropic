<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20191115161548 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('INSERT INTO "transfers" ("identifier", "name", "in_progress", "frequency", "enabled")
            VALUES (\'import_customers_changed\', \'Import změněných zákazníků z IS\', \'0\', \'Jednou denně\', \'1\'),
            (\'import_customers_czech\', \'Import všech českých zákazníků z IS\', \'0\', \'Jednou denně\', \'1\'),
            (\'import_customers_slovak\', \'Import všech slovenských zákazníků z IS\', \'0\', \'Jednou denně\', \'1\'),
            (\'import_customers_german\', \'Import všech německých zákazníků z IS\', \'0\', \'Jednou denně\', \'1\');');
        $this->sql('DELETE FROM "transfers" WHERE "identifier" LIKE \'import_customers\'');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
