<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20191230161346 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('ALTER TABLE orders RENAME COLUMN promo_code_code TO promo_codes_codes');
        $this->sql('ALTER TABLE orders ALTER promo_codes_codes TYPE TEXT;');

        $this->sql('ALTER TABLE orders RENAME COLUMN gtm_coupon TO gtm_coupons');
        $this->sql('ALTER TABLE orders ALTER gtm_coupons TYPE TEXT;');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
