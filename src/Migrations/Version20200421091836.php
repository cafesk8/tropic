<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200421091836 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->sql('UPDATE stores SET external_number = :externalStockId WHERE id = \'1\'', [
            'externalStockId' => 2,
        ]);
        $this->sql('UPDATE stores SET external_number = :externalStockId WHERE id = \'2\'', [
            'externalStockId' => 13,
        ]);
        $this->sql('UPDATE stores SET external_number = :externalStockId WHERE id = \'3\'', [
            'externalStockId' => 10,
        ]);
        $this->sql('UPDATE stores SET external_number = :externalStockId WHERE id = \'4\'', [
            'externalStockId' => 11,
        ]);
        $this->sql('UPDATE stores SET external_number = :externalStockId WHERE id = \'5\'', [
            'externalStockId' => 4,
        ]);
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
