<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190910132421 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('UPDATE transfers SET last_start_at = NULL WHERE identifier = \'import_customers\';');
        $this->sql('UPDATE cron_modules SET scheduled = true WHERE service_id = \'Shopsys\ShopBundle\Model\Customer\Transfer\CustomerImportCronModule\';');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
