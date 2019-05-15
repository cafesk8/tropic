<?php

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190513152423 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->sql('
            CREATE TABLE product_main_variant_groups (
                id SERIAL NOT NULL,
                distinguishing_parameter_id INT DEFAULT NULL,
                PRIMARY KEY(id)
            )');
        $this->sql('CREATE INDEX IDX_327A3E7E471E7F4D ON product_main_variant_groups (distinguishing_parameter_id)');
        $this->sql('
            ALTER TABLE
                product_main_variant_groups
            ADD
                CONSTRAINT FK_327A3E7E471E7F4D FOREIGN KEY (distinguishing_parameter_id) REFERENCES parameters (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->sql('ALTER TABLE products ADD maint_variant_group_id INT DEFAULT NULL');
        $this->sql('
            ALTER TABLE
                products
            ADD
                CONSTRAINT FK_B3BA5A5AFE2F458 FOREIGN KEY (maint_variant_group_id) REFERENCES product_main_variant_groups (id) ON DELETE
            SET
                NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->sql('CREATE INDEX IDX_B3BA5A5AFE2F458 ON products (maint_variant_group_id)');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
