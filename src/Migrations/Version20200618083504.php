<?php

declare(strict_types=1);

namespace App\Migrations;

use App\Component\Domain\DomainHelper;
use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200618083504 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('ALTER TABLE vats ADD pohoda_name VARCHAR(20) DEFAULT NULL');

        $this->sql('UPDATE vats SET pohoda_name = :pohodaName WHERE pohoda_id = :pohodaId', [
            'pohodaName' => 'none',
            'pohodaId' => 0,
        ]);

        $this->sql('UPDATE vats SET pohoda_name = :pohodaName WHERE pohoda_id = :pohodaId', [
            'pohodaName' => 'low',
            'pohodaId' => 1,
        ]);

        $this->sql('UPDATE vats SET pohoda_name = :pohodaName WHERE pohoda_id = :pohodaId', [
            'pohodaName' => 'high',
            'pohodaId' => 2,
        ]);

        $this->sql('UPDATE vats SET pohoda_name = :pohodaName WHERE pohoda_id = :pohodaId', [
            'pohodaName' => 'third',
            'pohodaId' => 3,
        ]);
        // 20% VAT will always be sent on the Slovak domain
        $this->sql('UPDATE vats SET pohoda_name = :pohodaName WHERE domain_id = :domainId', [
            'pohodaName' => 'historyHigh',
            'domainId' => DomainHelper::SLOVAK_DOMAIN,
        ]);
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
