<?php

declare(strict_types=1);

namespace App\Migrations;

use App\Component\Setting\Setting;
use App\Model\Product\Product;
use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200505114318 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('INSERT INTO availabilities (rgb_color) VALUES (\'#002B41\')');
        $availabilityId = (int)$this->connection->lastInsertId('availabilities_id_seq');

        $this->sql(
            'INSERT INTO availability_translations (translatable_id, name, locale)
            VALUES (:availabilityId, :availabilityName, :locale)',
            [
                'availabilityId' => $availabilityId,
                'availabilityName' => 'Momentálně nedostupné',
                'locale' => 'cs',
            ]
        );
        $this->sql(
            'INSERT INTO availability_translations (translatable_id, name, locale)
            VALUES (:availabilityId, :availabilityName, :locale)',
            [
                'availabilityId' => $availabilityId,
                'availabilityName' => 'Momentálne nedostupné',
                'locale' => 'sk',
            ]
        );
        $this->sql(
            'INSERT INTO availability_translations (translatable_id, name, locale)
            VALUES (:availabilityId, :availabilityName, :locale)',
            [
                'availabilityId' => $availabilityId,
                'availabilityName' => 'Currently unavailable',
                'locale' => 'en',
            ]
        );

        $this->sql('UPDATE products SET using_stock = true');
        $this->sql(
            'UPDATE products 
            SET out_of_stock_action = :outOfStockAction, 
                out_of_stock_availability_id = :outOfStockAvailabilityId',
            [
                'outOfStockAction' => Product::OUT_OF_STOCK_ACTION_SET_ALTERNATE_AVAILABILITY,
                'outOfStockAvailabilityId' => $availabilityId,
            ]
        );

        $this->sql(
            'INSERT INTO setting_values (name, domain_id, type, value) VALUES (:defaultAvailabilityOutOfStockId, :domainId, \'none\', :availabilityId)',
            [
                'defaultAvailabilityOutOfStockId' => Setting::DEFAULT_AVAILABILITY_OUT_OF_STOCK_ID,
                'domainId' => 0,
                'availabilityId' => $availabilityId,
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
