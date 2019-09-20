<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190917145031 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('ALTER TABLE orders ADD personal_take_type VARCHAR(50) NOT NULL DEFAULT \'none\'');
        $this->sql('UPDATE orders SET personal_take_type = \'store\' WHERE store_id IS NOT NULL');
        $this->sql('UPDATE orders SET personal_take_type = \'balikobot\' WHERE pickup_place_id IS NOT NULL');
        $this->sql('ALTER TABLE orders ALTER personal_take_type DROP DEFAULT');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
