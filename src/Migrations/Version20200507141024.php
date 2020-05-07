<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\FrameworkBundle\Migrations\MultidomainMigrationTrait;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200507141024 extends AbstractMigration
{
    use MultidomainMigrationTrait;

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('ALTER TABLE products ADD promo_discount_disabled BOOLEAN NOT NULL DEFAULT FALSE');
        $this->sql('ALTER TABLE products ALTER promo_discount_disabled DROP DEFAULT');

        foreach ($this->getAllDomainIds() as $domainId) {
            $query = 'INSERT INTO setting_values (name, domain_id, value, type) VALUES (:name, :domainId, :value, :type)';

            $this->sql($query, [
                'name' => 'promoDiscountText',
                'domainId' => $domainId,
                'value' => t('Na tento produkt se nevztahuje sleva za slevové kupóny', [], 'dataFixtures'),
                'type' => 'string',
            ]);

            $this->sql($query, [
                'name' => 'allDiscountText',
                'domainId' => $domainId,
                'value' => t('Na tento produkt se nevztahují žádné slevy', [], 'dataFixtures'),
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
