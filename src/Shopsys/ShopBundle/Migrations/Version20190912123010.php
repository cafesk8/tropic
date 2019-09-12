<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190912123010 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('ALTER TABLE orders ALTER first_name DROP NOT NULL');
        $this->sql('ALTER TABLE orders ALTER last_name DROP NOT NULL');
        $this->sql('ALTER TABLE orders ALTER telephone DROP NOT NULL');
        $this->sql('ALTER TABLE orders ALTER street DROP NOT NULL');
        $this->sql('ALTER TABLE orders ALTER city DROP NOT NULL');
        $this->sql('ALTER TABLE orders ALTER postcode DROP NOT NULL');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
