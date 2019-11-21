<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20191111115119 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('
            CREATE TABLE product_gifts (
                id SERIAL NOT NULL,
                gift_id INT NOT NULL,
                domain_id INT NOT NULL,
                active BOOLEAN NOT NULL,
                PRIMARY KEY(id)
            )');
        $this->sql('CREATE INDEX IDX_C447B47397A95A83 ON product_gifts (gift_id)');
        $this->sql('
            CREATE TABLE product_gift_products (
                product_gift_id INT NOT NULL,
                product_id INT NOT NULL,
                PRIMARY KEY(product_gift_id, product_id)
            )');
        $this->sql('CREATE INDEX IDX_2F52A2E246704133 ON product_gift_products (product_gift_id)');
        $this->sql('CREATE INDEX IDX_2F52A2E24584665A ON product_gift_products (product_id)');
        $this->sql('
            ALTER TABLE
                product_gifts
            ADD
                CONSTRAINT FK_C447B47397A95A83 FOREIGN KEY (gift_id) REFERENCES products (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->sql('
            ALTER TABLE
                product_gift_products
            ADD
                CONSTRAINT FK_2F52A2E246704133 FOREIGN KEY (product_gift_id) REFERENCES product_gifts (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->sql('
            ALTER TABLE
                product_gift_products
            ADD
                CONSTRAINT FK_2F52A2E24584665A FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
