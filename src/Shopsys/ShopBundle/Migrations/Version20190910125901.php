<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190910125901 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('DROP INDEX UNIQ_1483A5E967B1C660');
        $this->sql('DROP INDEX UNIQ_1483A5E963F16A01');
        $this->sql('DROP INDEX email_domain');
        $this->sql('CREATE UNIQUE INDEX email_domain ON users (email, domain_id, ean)');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
