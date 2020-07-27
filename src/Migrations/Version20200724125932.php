<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200724125932 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('INSERT INTO units (pohoda_name) VALUES (\'set\')');
        $unitLastId = (int)$this->connection->lastInsertId('units_id_seq');

        $this->sql('INSERT INTO unit_translations (translatable_id, name, locale) VALUES (:unitId, :unitName, :locale)', [
            'unitId' => $unitLastId,
            'unitName' => 'Set',
            'locale' => 'cs',
        ]);
        $this->sql('INSERT INTO unit_translations (translatable_id, name, locale) VALUES (:unitId, :unitName, :locale)', [
            'unitId' => $unitLastId,
            'unitName' => 'Set',
            'locale' => 'sk',
        ]);
        $this->sql('INSERT INTO unit_translations (translatable_id, name, locale) VALUES (:unitId, :unitName, :locale)', [
            'unitId' => $unitLastId,
            'unitName' => 'Set',
            'locale' => 'en',
        ]);

        $this->sql('INSERT INTO units (pohoda_name) VALUES (\'bal\')');
        $unitLastId = (int)$this->connection->lastInsertId('units_id_seq');

        $this->sql('INSERT INTO unit_translations (translatable_id, name, locale) VALUES (:unitId, :unitName, :locale)', [
            'unitId' => $unitLastId,
            'unitName' => 'Bal',
            'locale' => 'cs',
        ]);
        $this->sql('INSERT INTO unit_translations (translatable_id, name, locale) VALUES (:unitId, :unitName, :locale)', [
            'unitId' => $unitLastId,
            'unitName' => 'Bal',
            'locale' => 'sk',
        ]);
        $this->sql('INSERT INTO unit_translations (translatable_id, name, locale) VALUES (:unitId, :unitName, :locale)', [
            'unitId' => $unitLastId,
            'unitName' => 'Bal',
            'locale' => 'en',
        ]);
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
