<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190604100834 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->sql('CREATE INDEX IDX_1A9C93E8C6814BC4 ON transport_pickup_places (balikobot_id)');
        $this->sql('CREATE INDEX IDX_1A9C93E852F436BC ON transport_pickup_places (balikobot_shipper)');
        $this->sql('CREATE INDEX IDX_1A9C93E813EFEF8B ON transport_pickup_places (balikobot_shipper_service)');
        $this->sql('CREATE INDEX IDX_1A9C93E8F026BB7C ON transport_pickup_places (country_code)');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
