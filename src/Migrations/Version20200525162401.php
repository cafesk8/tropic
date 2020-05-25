<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200525162401 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $piecesUnitId = $this->sql('SELECT translatable_id FROM unit_translations WHERE name = \'ks\' AND locale = \'cs\'')->fetchColumn();
        if ($piecesUnitId !== false) {
            $this->sql('UPDATE units SET pohoda_name = \'kus\' WHERE id = :id', [
                'id' => $piecesUnitId,
            ]);
        }
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
