<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Schema\Schema;
use Shopsys\FrameworkBundle\Migrations\MultidomainMigrationTrait;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190506093810 extends AbstractMigration
{
    use MultidomainMigrationTrait;

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $now = (new DateTimeImmutable())->format(DateTimeInterface::ISO8601);

        foreach ($this->getAllDomainIds() as $domainId) {
            $this->sql('INSERT INTO setting_values (name, domain_id, value, type) VALUES
                (\'infoRowVisibility\', :domainId, \'false\', \'boolean\'),
                (\'infoRowText\', :domainId, null, \'none\'),
                (\'infoRowLastChangeAt\', :domainId, :now, \'datetime\');
            ', [
                'domainId' => $domainId,
                'now' => $now,
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
