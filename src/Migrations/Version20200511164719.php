<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\FrameworkBundle\Migrations\MultidomainMigrationTrait;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200511164719 extends AbstractMigration
{
    use MultidomainMigrationTrait;

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $maxPosition = (int)$this->sql('SELECT MAX(position) FROM flags')->fetchColumn();
        $this->sql('INSERT INTO flags (rgb_color, visible, position, sale) VALUES (:rgb_color, :visible, :position, :sale)', [
            'rgb_color' => '#abcdef',
            'visible' => true,
            'position' => $maxPosition + 1,
            'sale' => true,
        ]);

        $flagId = (int)$this->connection->lastInsertId('flags_id_seq');
        foreach ($this->getAllDomainIds() as $domainId) {
            $this->sql('INSERT INTO flag_translations (translatable_id, name, locale) 
                VALUES (:translatable_id, :name, :locale)', [
                'translatable_id' => $flagId,
                'name' => 'Výprodej',
                'locale' => $this->getDomainLocale($domainId),
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
