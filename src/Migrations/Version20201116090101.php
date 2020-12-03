<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20201116090101 extends AbstractMigration
{
    private const CODES_BY_SETTING = [
        'defaultAvailabilityInStockId' => 'inStock',
        'defaultAvailabilityOutOfStockId' => 'outOfStock',
        'availabilityInSaleStockId' => 'inSaleStock',
        'availabilityInExternalStockId' => 'inExternalStock',
        'availabilityInDaysId' => 'inDays',
        'availabilityByVariantId' => 'byVariants',
    ];

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('ALTER TABLE availabilities ADD code VARCHAR(255) DEFAULT NULL');
        $this->sql('ALTER TABLE availabilities ALTER code DROP DEFAULT');

        $settings = $this->sql(
            'SELECT name, value FROM setting_values WHERE name IN (\'defaultAvailabilityInStockId\', \'defaultAvailabilityOutOfStockId\', \'availabilityInSaleStockId\', \'availabilityInExternalStockId\', \'availabilityInDaysId\', \'availabilityByVariantId\')',
        )->fetchAll(FetchMode::ASSOCIATIVE);

        foreach ($settings as $setting) {
            $this->sql('UPDATE availabilities SET code = :code WHERE id = :id', [
                'code' => self::CODES_BY_SETTING[$setting['name']],
                'id' => $setting['value'],
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
