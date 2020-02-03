<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;
use App\Component\Domain\DomainHelper;
use App\Model\Pricing\Group\PricingGroup;

class Version20200130095506 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('ALTER TABLE users RENAME COLUMN member_of_bushman_club TO member_of_loyalty_program');
        $this->sql('ALTER TABLE orders RENAME COLUMN member_of_bushman_club TO member_of_loyalty_program');
        $this->sql('DELETE FROM pricing_groups WHERE internal_id != \'ordinary_customer\'');

        foreach (DomainHelper::DOMAIN_ID_BY_COUNTRY_CODE as $domainId) {
            $this->sql('INSERT INTO pricing_groups (name, domain_id, internal_id, minimal_price, discount) 
                            VALUES (:name, :domain_id, :internal_id, :minimal_price, :discount)', [
                    'name' => 'Registrovaný zákazník',
                    'domain_id' => $domainId,
                    'internal_id' => PricingGroup::PRICING_GROUP_REGISTERED_CUSTOMER,
                    'minimal_price' => 0,
                    'discount' => 1,
                ]);
        }
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
