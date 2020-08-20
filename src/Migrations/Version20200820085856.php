<?php

declare(strict_types=1);

namespace App\Migrations;

use App\Component\Domain\DomainHelper;
use App\Model\Pricing\Group\PricingGroup;
use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200820085856 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $queryParametersDataProvider = [
            [
                'pohodaIdent' => 'Registr',
                'internalId' => PricingGroup::PRICING_GROUP_REGISTERED_CUSTOMER,
                'domainId' => DomainHelper::CZECH_DOMAIN,
            ], [
                'pohodaIdent' => 'Registr EU',
                'internalId' => PricingGroup::PRICING_GROUP_REGISTERED_CUSTOMER,
                'domainId' => DomainHelper::SLOVAK_DOMAIN,
            ], [
                'pohodaIdent' => 'Prodejní',
                'internalId' => PricingGroup::PRICING_GROUP_ORDINARY_CUSTOMER,
                'domainId' => DomainHelper::CZECH_DOMAIN,
            ], [
                'pohodaIdent' => 'EU',
                'internalId' => PricingGroup::PRICING_GROUP_ORDINARY_CUSTOMER,
                'domainId' => DomainHelper::SLOVAK_DOMAIN,
            ],
        ];

        foreach ($queryParametersDataProvider as $queryParameters) {
            $this->sql(
                'UPDATE pricing_groups
                SET pohoda_ident = :pohodaIdent
                WHERE internal_id = :internalId
                AND domain_id = :domainId',
                $queryParameters
            );
        }
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
