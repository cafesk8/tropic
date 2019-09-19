<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190919104024 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('UPDATE transport_pickup_places SET post_code = REPLACE(post_code, \' \', \'\')');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
