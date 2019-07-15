<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190729102521 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->sql('ALTER TABLE cart_items ADD main_cart_item_id INT DEFAULT NULL');
        $this->sql('
            ALTER TABLE
                cart_items
            ADD
                CONSTRAINT FK_BEF48445F00D2FE0 FOREIGN KEY (main_cart_item_id) REFERENCES cart_items (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->sql('CREATE INDEX IDX_BEF48445F00D2FE0 ON cart_items (main_cart_item_id)');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
