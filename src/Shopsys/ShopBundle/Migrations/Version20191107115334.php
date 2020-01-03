<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20191107115334 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('ALTER TABLE transfer_issues ADD context TEXT DEFAULT NULL');
        $this->sql('ALTER TABLE transfer_issues ADD group_id TEXT NOT NULL DEFAULT \'\'');
        $this->sql('ALTER TABLE transfer_issues ALTER group_id DROP DEFAULT');
        $this->sql('CREATE INDEX IDX_BF6E22B0FE54D947 ON transfer_issues (group_id)');
        $this->sql('CREATE INDEX IDX_BF6E22B0B6BD307F ON transfer_issues (message)');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
