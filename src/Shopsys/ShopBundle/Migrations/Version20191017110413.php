<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20191017110413 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('ALTER TABLE administrators ADD roles JSON NOT NULL DEFAULT \'{}\'');
        $this->sql('ALTER TABLE administrators ALTER roles DROP DEFAULT');
        $this->sql('
            UPDATE administrators
            SET roles=\'{"1":"ROLE_VIEW_SETTINGS","2":"ROLE_VIEW_ADMINISTRATORS","3":"ROLE_VIEW_MARKETING","4":"ROLE_VIEW_PRODUCTS","5":"ROLE_VIEW_CUSTOMERS","6":"ROLE_VIEW_ORDERS","7":"ROLE_VIEW_PRICING"}\'
        ');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
