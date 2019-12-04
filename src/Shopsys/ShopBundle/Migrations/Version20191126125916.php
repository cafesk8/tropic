<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20191126125916 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('
            CREATE TABLE promo_products (
                id SERIAL NOT NULL,
                promo_product_id INT NOT NULL,
                domain_id INT NOT NULL,
                price NUMERIC(20, 6) DEFAULT NULL,
                minimal_cart_price NUMERIC(20, 6) DEFAULT NULL,
                PRIMARY KEY(id)
            )');
        $this->sql('CREATE INDEX IDX_7B657F62191FC639 ON promo_products (promo_product_id)');
        $this->sql('COMMENT ON COLUMN promo_products.price IS \'(DC2Type:money)\'');
        $this->sql('COMMENT ON COLUMN promo_products.minimal_cart_price IS \'(DC2Type:money)\'');
        $this->sql('
            ALTER TABLE
                promo_products
            ADD
                CONSTRAINT FK_7B657F62191FC639 FOREIGN KEY (promo_product_id) REFERENCES products (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
