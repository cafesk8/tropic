<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\FrameworkBundle\Migrations\MultidomainMigrationTrait;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190828095521 extends AbstractMigration
{
    use MultidomainMigrationTrait;

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        foreach ($this->getAllDomainIds() as $domainId) {
            $this->sql(
                'INSERT INTO setting_values (name, domain_id, type, value) VALUES (\'ourValuesArticleId\', :domainId, \'integer\', 1)',
                [
                    'domainId' => $domainId,
                ]
            );
            $this->sql(
                'INSERT INTO setting_values (name, domain_id, type, value) VALUES (\'ourStoryArticleId\', :domainId, \'integer\', 1)',
                [
                    'domainId' => $domainId,
                ]
            );
        }
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
