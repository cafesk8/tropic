<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200512145006 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->sql('ALTER TABLE units ADD pohoda_name VARCHAR(255) DEFAULT NULL');
        $this->sql('INSERT INTO units (pohoda_name) VALUES (\'metr\')');
        $unitLastId = (int)$this->connection->lastInsertId('units_id_seq');

        $this->sql('INSERT INTO unit_translations (translatable_id, name, locale) VALUES (:unitId, :unitName, :locale)', [
            'unitId' => $unitLastId,
            'unitName' => 'm',
            'locale' => 'cs',
        ]);
        $this->sql('INSERT INTO unit_translations (translatable_id, name, locale) VALUES (:unitId, :unitName, :locale)', [
            'unitId' => $unitLastId,
            'unitName' => 'm',
            'locale' => 'sk',
        ]);
        $this->sql('INSERT INTO unit_translations (translatable_id, name, locale) VALUES (:unitId, :unitName, :locale)', [
            'unitId' => $unitLastId,
            'unitName' => 'm',
            'locale' => 'en',
        ]);
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
