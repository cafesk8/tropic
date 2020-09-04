<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200904105600 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $recommendedFlags = $this->sql('SELECT id FROM flags WHERE pohoda_id = \'Doporucujeme\'')->fetchAll();

        foreach ($recommendedFlags as $recommendedFlag) {
            $this->sql('UPDATE flag_translations FT SET name = \'Tropic doporučuje\' WHERE translatable_id = :flagId AND locale = \'cs\'', ['flagId' => $recommendedFlag['id']]);
            $this->sql('UPDATE flag_translations FT SET name = \'Tropic odporúča\' WHERE translatable_id = :flagId AND locale = \'sk\'', ['flagId' => $recommendedFlag['id']]);
            $this->sql('UPDATE flag_translations FT SET name = \'Tropic recommends\' WHERE translatable_id = :flagId AND locale = \'en\'', ['flagId' => $recommendedFlag['id']]);
        }
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {

    }
}
