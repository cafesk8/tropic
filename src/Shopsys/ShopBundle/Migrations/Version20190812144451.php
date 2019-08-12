<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190812144451 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('
            CREATE TABLE advert_products (
                advert_id INT NOT NULL,
                advert_product_id INT NOT NULL,
                position INT NOT NULL,
                PRIMARY KEY(advert_id, advert_product_id)
            )');
        $this->sql('CREATE INDEX IDX_3118E0DBD07ECCB6 ON advert_products (advert_id)');
        $this->sql('CREATE INDEX IDX_3118E0DB4CA81760 ON advert_products (advert_product_id)');
        $this->sql('
            ALTER TABLE
                advert_products
            ADD
                CONSTRAINT FK_3118E0DBD07ECCB6 FOREIGN KEY (advert_id) REFERENCES adverts (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->sql('
            ALTER TABLE
                advert_products
            ADD
                CONSTRAINT FK_3118E0DB4CA81760 FOREIGN KEY (advert_product_id) REFERENCES products (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
