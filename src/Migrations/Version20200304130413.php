<?php

declare(strict_types=1);

namespace App\Migrations;

use App\Model\Pricing\Group\PricingGroup;
use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200304130413 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('UPDATE pricing_groups SET discount = 0 WHERE discount IS NULL;');
        $this->sql('ALTER TABLE pricing_groups ALTER discount SET NOT NULL;');
        $this->sql('UPDATE pricing_groups SET discount = 3 WHERE internal_id = \':internalId\';', [
            'internalId' => PricingGroup::PRICING_GROUP_REGISTERED_CUSTOMER,
        ]);
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
