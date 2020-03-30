<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200221150539 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('
            CREATE TABLE order_gifts (
                id SERIAL NOT NULL,
                enabled BOOLEAN NOT NULL,
                price_level_with_vat NUMERIC(20, 6) NOT NULL,
                domain_id INT NOT NULL,
                PRIMARY KEY(id)
            )');
        $this->sql('COMMENT ON COLUMN order_gifts.price_level_with_vat IS \'(DC2Type:money)\'');
        $this->sql('
            CREATE TABLE order_gift_products (
                order_gift_id INT NOT NULL,
                product_id INT NOT NULL,
                PRIMARY KEY(order_gift_id, product_id)
            )');
        $this->sql('CREATE INDEX IDX_58DCF6C261D3F3C8 ON order_gift_products (order_gift_id)');
        $this->sql('CREATE INDEX IDX_58DCF6C24584665A ON order_gift_products (product_id)');
        $this->sql('
            ALTER TABLE
                order_gift_products
            ADD
                CONSTRAINT FK_58DCF6C261D3F3C8 FOREIGN KEY (order_gift_id) REFERENCES order_gifts (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->sql('
            ALTER TABLE
                order_gift_products
            ADD
                CONSTRAINT FK_58DCF6C24584665A FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
