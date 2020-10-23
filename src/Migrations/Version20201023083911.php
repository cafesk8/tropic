<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20201023083911 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('UPDATE pricing_groups SET pohoda_ident = \'EURregistr\' WHERE pohoda_ident = \'EU Registr\'');
        $this->sql('ALTER TABLE pricing_groups ADD pohoda_id INT DEFAULT NULL');
        $pohodaIdsIndexedByPohodaIdent = [
            'Prodejní' => 1,
            'Registr' => 2,
            'EUR' => 3,
            'EURregistr' => 4,
            'EU' => 4,
            'EU registr' => 6,
        ];
        foreach ($pohodaIdsIndexedByPohodaIdent as $pohodaIdent => $pohodaId) {
            $this->sql('UPDATE pricing_groups SET pohoda_id = :pohodaId WHERE pohoda_ident = :pohodaIdent', [
                'pohodaId' => $pohodaId,
                'pohodaIdent' => $pohodaIdent,
            ]);
        }
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
