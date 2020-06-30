<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200624075625 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('ALTER TABLE stores ADD pohoda_name VARCHAR(32) DEFAULT NULL');

        $this->sql('UPDATE stores SET pohoda_name = :pohodaName WHERE external_number = :pohodaId', [
            'pohodaName' => 'VÝPRODEJ',
            'pohodaId' => 2,
        ]);

        $this->sql('UPDATE stores SET pohoda_name = :pohodaName WHERE external_number = :pohodaId', [
            'pohodaName' => 'PRODEJNA',
            'pohodaId' => 4,
        ]);

        $this->sql('UPDATE stores SET pohoda_name = :pohodaName WHERE external_number = :pohodaId', [
            'pohodaName' => 'TROPIC',
            'pohodaId' => 10,
        ]);

        $this->sql('UPDATE stores SET pohoda_name = :pohodaName WHERE external_number = :pohodaId', [
            'pohodaName' => 'EXTERNÍ',
            'pohodaId' => 11,
        ]);

        $this->sql('UPDATE stores SET pohoda_name = :pohodaName WHERE external_number = :pohodaId', [
            'pohodaName' => 'PRODEJNA-V',
            'pohodaId' => 13,
        ]);
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
