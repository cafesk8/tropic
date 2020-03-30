<?php

declare(strict_types=1);

namespace App\Migrations;

use App\Component\DiscountExclusion\DiscountExclusionFacade;
use Doctrine\DBAL\Schema\Schema;
use Shopsys\FrameworkBundle\Migrations\MultidomainMigrationTrait;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200330130544 extends AbstractMigration
{
    use MultidomainMigrationTrait;

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('ALTER TABLE products ADD registration_discount_disabled BOOLEAN NOT NULL DEFAULT FALSE');
        $this->sql('ALTER TABLE products ALTER registration_discount_disabled DROP DEFAULT');

        foreach ($this->getAllDomainIds() as $domainId) {
            $this->sql('INSERT INTO setting_values (name, domain_id, value, type) VALUES (:name, :domainId, :value, :type)', [
                'name' => DiscountExclusionFacade::SETTING_REGISTRATION_DISCOUNT_EXCLUSION,
                'domainId' => $domainId,
                'value' => t('Na tento produkt se nevztahuje sleva pro registrované zákazníky', [], 'dataFixtures'),
                'type' => 'string',
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
