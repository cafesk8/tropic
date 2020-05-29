<?php

declare(strict_types=1);

namespace App\Migrations;

use App\Component\Setting\Setting;
use Doctrine\DBAL\Schema\Schema;
use Shopsys\FrameworkBundle\Migrations\MultidomainMigrationTrait;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200529112847 extends AbstractMigration
{
    use MultidomainMigrationTrait;

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        foreach ($this->getAllDomainIds() as $domainId) {
            $this->sql(
                'INSERT INTO setting_values (name, domain_id, value, type) VALUES (:name, :domainId, :minimumPrice, \'money\');',
                [
                    'name' => Setting::COFIDIS_BANNER_MINIMUM_SHOW_PRICE_ID,
                    'domainId' => $domainId,
                    'minimumPrice' => 0,
                ]
            );
        }
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
