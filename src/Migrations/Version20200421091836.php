<?php

declare(strict_types=1);

namespace App\Migrations;

use App\Component\Transfer\Pohoda\Product\PohodaProductExportRepository;
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
            'externalStockId' => PohodaProductExportRepository::POHODA_STOCK_SALE_ID,
        ]);
        $this->sql('UPDATE stores SET external_number = :externalStockId WHERE id = \'2\'', [
            'externalStockId' => PohodaProductExportRepository::POHODA_STOCK_STORE_SALE_ID,
        ]);
        $this->sql('UPDATE stores SET external_number = :externalStockId WHERE id = \'3\'', [
            'externalStockId' => PohodaProductExportRepository::POHODA_STOCK_TROPIC_ID,
        ]);
        $this->sql('UPDATE stores SET external_number = :externalStockId WHERE id = \'4\'', [
            'externalStockId' => PohodaProductExportRepository::POHODA_STOCK_EXTERNAL_ID,
        ]);
        $this->sql('UPDATE stores SET external_number = :externalStockId WHERE id = \'5\'', [
            'externalStockId' => PohodaProductExportRepository::POHODA_STOCK_STORE_ID,
        ]);
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
