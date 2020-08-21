<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\FrameworkBundle\Migrations\MultidomainMigrationTrait;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20202008174060 extends AbstractMigration
{
    use MultidomainMigrationTrait;

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        foreach ($this->getAllDomainIds() as $domainId) {

            $phoneHours = $this->sql('SELECT COUNT(*) FROM setting_values WHERE name = \'shopInfoOpeningHours\' AND domain_id = :domainId;
            ', ['domainId' => $domainId])->fetchColumn(0);

            if ($phoneHours <= 0) {
                $this->sql('INSERT INTO setting_values (name, domain_id, value, type) VALUES
                    (\'shopInfoOpeningHours\', :domainId, \'(po-pá, 10:00 - 16:00)\', \'string\');
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