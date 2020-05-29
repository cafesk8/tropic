<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\FrameworkBundle\Migrations\MultidomainMigrationTrait;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200518110613 extends AbstractMigration
{
    use MultidomainMigrationTrait;

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $pohodaIdsByName = [
            'Novinka' => 'Novinka',
            'Doprodej' => 'Doprodej',
            'Akce' => 'Akce',
            'Doporučujeme' => 'Doporucujeme',
            'Sleva' => 'Sleva',
            'Připravujeme' => 'Priprav',
        ];

        $maxPosition = $this->sql('SELECT MAX(F.position) AS flag_position FROM flags F')->fetch();
        $maxPosition = $maxPosition['flag_position'];
        $flags = $this->sql('SELECT F.id AS flag_id, FT.name AS flag_name FROM flags F JOIN flag_translations FT ON F.id = FT.translatable_id WHERE locale = \'cs\'')->fetchAll();

        $this->sql('ALTER TABLE flags ADD pohoda_id VARCHAR(20) DEFAULT NULL');

        foreach ($pohodaIdsByName as $name => $pohodaId) {
            foreach ($flags as $flagArray) {
                if ($flagArray['flag_name'] === $name) {
                    $this->sql('UPDATE flags SET pohoda_id = \'' . $pohodaId . '\' WHERE id = ' . $flagArray['flag_id']);
                    unset($pohodaIdsByName[$name]);
                }
            }
        }

        foreach ($pohodaIdsByName as $name => $pohodaId) {
            $this->sql('INSERT INTO flags(rgb_color, visible, position, sale, pohoda_id) VALUES (\'#000000\', TRUE, ' . ++$maxPosition . ', FALSE, \'' . $pohodaId . '\')');
            $newFlagId = $this->connection->lastInsertId();

            foreach ($this->getAllDomainIds() as $domainId) {
                $this->sql('INSERT INTO flag_translations(translatable_id, name, locale) VALUES (' . $newFlagId . ', \'' . $name . '\', \'' . $this->getDomainLocale($domainId) . '\')');
            }
        }
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
