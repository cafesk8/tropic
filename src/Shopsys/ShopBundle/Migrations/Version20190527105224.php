<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190527105224 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->sql('
            CREATE TABLE transport_pickup_places (
                id SERIAL NOT NULL,
                balikobot_id VARCHAR(255) NOT NULL,
                balikobot_shipper VARCHAR(255) NOT NULL,
                balikobot_shipper_service VARCHAR(255) DEFAULT NULL,
                name VARCHAR(250) NOT NULL,
                city VARCHAR(250) NOT NULL,
                street VARCHAR(250) NOT NULL,
                post_code VARCHAR(30) NOT NULL,
                country_code VARCHAR(10) NOT NULL,
                PRIMARY KEY(id)
            )');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
