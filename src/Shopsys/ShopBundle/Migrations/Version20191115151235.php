<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20191115151235 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('INSERT INTO "transfers" ("identifier", "name", "in_progress", "frequency", "enabled")
            VALUES (\'import_product_store_stock_czech\', \'Import skladového množství produktů z IS pro českou doménu\', \'0\', \'Jednou denně ve 3:00\', \'1\'),
            (\'import_product_store_stock_slovak\', \'Import skladového množství produktů z IS pro slovenskou doménu\', \'0\', \'Jednou denně ve 3:00\', \'1\'),
            (\'import_product_store_stock_german\', \'Import skladového množství produktů z IS pro německou doménu\', \'0\', \'Jednou denně ve 3:00\', \'1\');');
        $this->sql('DELETE FROM "transfers" WHERE "identifier" LIKE \'import_product_store_stock\'');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
