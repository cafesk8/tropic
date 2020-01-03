<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20191010144727 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('ALTER TABLE brands ADD type VARCHAR(50) NOT NULL DEFAULT \'default\'');
        $this->sql('ALTER TABLE brands ALTER type DROP DEFAULT');

        $this->sql('UPDATE brands SET type = \'mainBushman\' WHERE name = \'Bushman\'');

        $this->sql('UPDATE products SET brand_id = (SELECT id FROM brands WHERE name = \'Bushman\')');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
