<?php

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190729071215 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->sql('ALTER TABLE order_items ADD main_order_item_id INT DEFAULT NULL');
        $this->sql('
            ALTER TABLE
                order_items
            ADD
                CONSTRAINT FK_62809DB056B14888 FOREIGN KEY (main_order_item_id) REFERENCES order_items (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->sql('CREATE INDEX IDX_62809DB056B14888 ON order_items (main_order_item_id)');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
