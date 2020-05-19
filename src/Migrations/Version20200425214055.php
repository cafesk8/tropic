<?php

declare(strict_types=1);

namespace App\Migrations;

use App\Model\Pricing\Group\PricingGroup;
use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200425214055 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql(
            'UPDATE pricing_groups
			SET pohoda_ident = :pohoda_ident
			WHERE internal_id = :internal_id',
            [
                'pohoda_ident' => 'Registr',
                'internal_id' => PricingGroup::PRICING_GROUP_REGISTERED_CUSTOMER,
            ]
        );
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
