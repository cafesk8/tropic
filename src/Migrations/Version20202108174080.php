<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\FrameworkBundle\Migrations\MultidomainMigrationTrait;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20202108174080 extends AbstractMigration
{
    use MultidomainMigrationTrait;

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        foreach ($this->getAllDomainIds() as $domainId) {
            $headerTitle = $this->sql('SELECT COUNT(*) FROM setting_values WHERE name = \'headerTitle\' AND domain_id = :domainId;
            ', ['domainId' => $domainId])->fetchColumn(0);

            $headerText = $this->sql('SELECT COUNT(*) FROM setting_values WHERE name = \'headerText\' AND domain_id = :domainId;
            ', ['domainId' => $domainId])->fetchColumn(0);

            $headerLink = $this->sql('SELECT COUNT(*) FROM setting_values WHERE name = \'headerLink\' AND domain_id = :domainId;
            ', ['domainId' => $domainId])->fetchColumn(0);

            if ($headerTitle <= 0) {
                $this->sql('INSERT INTO setting_values (name, domain_id, value, type) VALUES
                    (\'headerTitle\', :domainId, \'Bonusový program\', \'string\');
                ', ['domainId' => $domainId]);
            }

            if ($headerText <= 0) {
                $this->sql('INSERT INTO setting_values (name, domain_id, value, type) VALUES
                    (\'headerText\', :domainId, \'Bonusový program\', \'string\');
                ', ['domainId' => $domainId]);
            }

            if ($headerLink <= 0) {
                $this->sql('INSERT INTO setting_values (name, domain_id, value, type) VALUES
                    (\'headerLink\', :domainId, \'\', \'string\');
                ', ['domainId' => $domainId]);
            }
        }
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
