<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200401134740 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('ALTER TABLE vats ADD pohoda_id INT DEFAULT NULL');
        $this->sql('ALTER TABLE vats ALTER pohoda_id DROP DEFAULT');
        $this->sql('UPDATE vats SET pohoda_id = 2 WHERE percent = 21 and domain_id = 1');
        $this->sql('UPDATE vats SET pohoda_id = 1 WHERE percent = 15 and domain_id = 1');
        $this->sql('UPDATE vats SET pohoda_id = 3 WHERE percent = 10 and domain_id = 1');
        $this->sql('UPDATE vats SET pohoda_id = 0 WHERE percent = 0 and domain_id = 1');
        $this->sql('UPDATE vats SET name = \'Nulová sazba\' WHERE name = \'Zero rate\' and domain_id = 1');
        $this->sql('UPDATE vats SET percent = 20, name = \'Základná sadzba\' WHERE name = \'Standard rate\' and domain_id = 2');
        $this->sql('UPDATE vats SET percent = 10, name = \'Znížená sadzba\' WHERE name = \'Reduced rate\' and domain_id = 2');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
