<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200303114800 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('ALTER TABLE stores DROP COLUMN domain_id');
        $country = $this->sql('SELECT id FROM countries WHERE code = \'CZ\'')->fetch(FetchMode::ASSOCIATIVE);

        if ($country !== null && !empty($country['id'])) {
            $countryId = $country['id'];

            $query = 'INSERT INTO stores (
                        name, 
                        description, 
                        street, 
                        city, 
                        postcode, 
                        opening_hours, 
                        google_maps_link, 
                        position, 
                        country_id, 
                        pickup_place, 
                        show_on_store_list, 
                        franchisor, 
                        central_store
                    ) VALUES (
                        :name, 
                        :description, 
                        :street, 
                        :city, 
                        :postcode, 
                        :opening_hours, 
                        :google_maps_link, 
                        :position, 
                        :country_id, 
                        :pickup_place, 
                        :show_on_store_list, 
                        :franchisor, 
                        :central_store
                    )';

            $data = [
                [
                    'name' => t('Výprodej'),
                    'description' => t('Výprodejový sklad'),
                    'street' => '',
                    'city' => '',
                    'postcode' => '',
                    'opening_hours' => '',
                    'google_maps_link' => '',
                    'position' => 0,
                    'country_id' => $countryId,
                    'pickup_place' => 0,
                    'show_on_store_list' => 0,
                    'franchisor' => 0,
                    'central_store' => 0,
                ],
                [
                    'name' => t('Prodejna - výprodej'),
                    'description' => t('Výprodejový sklad na prodejně'),
                    'street' => '',
                    'city' => '',
                    'postcode' => '',
                    'opening_hours' => '',
                    'google_maps_link' => '',
                    'position' => 1,
                    'country_id' => $countryId,
                    'pickup_place' => 0,
                    'show_on_store_list' => 0,
                    'franchisor' => 0,
                    'central_store' => 0,
                ],
                [
                    'name' => t('Interní sklad'),
                    'description' => '',
                    'street' => '',
                    'city' => '',
                    'postcode' => '',
                    'opening_hours' => '',
                    'google_maps_link' => '',
                    'position' => 2,
                    'country_id' => $countryId,
                    'pickup_place' => 0,
                    'show_on_store_list' => 0,
                    'franchisor' => 0,
                    'central_store' => 1,
                ],
                [
                    'name' => t('Externí sklad'),
                    'description' => '',
                    'street' => '',
                    'city' => '',
                    'postcode' => '',
                    'opening_hours' => '',
                    'google_maps_link' => '',
                    'position' => 3,
                    'country_id' => $countryId,
                    'pickup_place' => 0,
                    'show_on_store_list' => 0,
                    'franchisor' => 0,
                    'central_store' => 0,
                ],
                [
                    'name' => t('Prodejna'),
                    'description' => t('Skladové zásoby na prodejně'),
                    'street' => 'Dr. Milady Horákové 76',
                    'city' => 'Liberec',
                    'postcode' => '460 07',
                    'opening_hours' => t('Po - Pá: 9:00 - 18:00, So: 9:00 - 12:00'),
                    'google_maps_link' => 'https://goo.gl/maps/dbaTeitaZ2vbqRxB8',
                    'position' => 4,
                    'country_id' => $countryId,
                    'pickup_place' => 1,
                    'show_on_store_list' => 1,
                    'franchisor' => 0,
                    'central_store' => 0,
                ],
            ];

            foreach ($data as $store) {
                $this->sql($query, $store);
            }
        }
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
