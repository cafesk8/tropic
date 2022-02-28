<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20220223112425 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('ALTER TABLE product_domains ADD registration_discount_disabled BOOLEAN DEFAULT NULL');
        $this->sql('ALTER TABLE product_domains ADD promo_discount_disabled BOOLEAN DEFAULT NULL');

        $this->sql('UPDATE product_domains SET registration_discount_disabled = P.registration_discount_disabled, promo_discount_disabled = P.promo_discount_disabled FROM products P WHERE P.id = product_id');

        $this->sql('ALTER TABLE product_domains ALTER registration_discount_disabled SET NOT NULL');
        $this->sql('ALTER TABLE product_domains ALTER registration_discount_disabled DROP DEFAULT');
        $this->sql('ALTER TABLE product_domains ALTER promo_discount_disabled SET NOT NULL');
        $this->sql('ALTER TABLE product_domains ALTER promo_discount_disabled DROP DEFAULT');

        $this->sql('ALTER TABLE products DROP registration_discount_disabled');
        $this->sql('ALTER TABLE products DROP promo_discount_disabled');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
