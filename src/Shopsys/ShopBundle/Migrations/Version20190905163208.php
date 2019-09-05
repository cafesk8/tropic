<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190905163208 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('CREATE TABLE card_eans (id SERIAL NOT NULL, ean VARCHAR(13) NOT NULL, PRIMARY KEY(id))');
        $this->sql('CREATE UNIQUE INDEX UNIQ_BAE81D3767B1C660 ON card_eans (ean)');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
