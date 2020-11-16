<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20201113114100 extends AbstractMigration
{
    private const CHANGED_AVAILABILITIES = [
        [
            'locale' => 'cs',
            'current_name' => 'Skladem v e-shopu',
            'new_name' => 'Ihned k odeslání',
        ],
        [
            'locale' => 'sk',
            'current_name' => 'Skladom v e-shope',
            'new_name' => 'Ihneď k odoslaniu',
        ],
        [
            'locale' => 'en',
            'current_name' => 'On stock',
            'new_name' => 'Available immediately',
        ],
        [
            'locale' => 'sk',
            'current_name' => 'Skladom',
            'new_name' => 'Ihneď k odoslaniu',
        ],
        [
            'locale' => 'en',
            'current_name' => 'Skladem v e-shopu',
            'new_name' => 'Available immediately',
        ],
    ];

    private const NEW_AVAILABILITIES = [
        [
            'id' => 'availabilityInSaleStockId',
            'color' => '#3ea938',
            'names' => [
                'cs' => 'Za tuto cenu skladem',
                'sk' => 'Za túto cenu na sklade',
                'en' => 'In stock for this price',
            ],
        ], [
            'id' => 'availabilityInExternalStockId',
            'color' => '#3ea938',
            'names' => [
                'cs' => 'Skladem v e-shopu',
                'sk' => 'Skladom v e-shope',
                'en' => 'In stock',
            ],
        ], [
            'id' => 'availabilityInDaysId',
            'color' => '#0077b3',
            'names' => [
                'cs' => 'Dostupnost ve dnech',
                'sk' => 'Dostupnosť v dňoch',
                'en' => 'Availability in days',
            ],
        ], [
            'id' => 'availabilityByVariantId',
            'color' => '#3ea938',
            'names' => [
                'cs' => 'Dostupnost dle varianty',
                'sk' => 'Dostupnosť podľa varianty',
                'en' => 'Availability by variant',
            ],
        ],
    ];

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        foreach (self::CHANGED_AVAILABILITIES as $availabilityData) {
            $this->sql(
                'UPDATE availability_translations SET name = :newName WHERE name = :oldName AND locale = :locale',
                [
                    'newName' => $availabilityData['new_name'],
                    'oldName' => $availabilityData['current_name'],
                    'locale' => $availabilityData['locale'],
                ]
            );
        }

        foreach (self::NEW_AVAILABILITIES as $availabilityData) {
            $this->sql('INSERT INTO availabilities (dispatch_time, rgb_color) VALUES (0, :color)', ['color' => $availabilityData['color']]);
            $availabilityId = (int)$this->connection->lastInsertId('availabilities_id_seq');

            foreach ($availabilityData['names'] as $locale => $name) {
                $this->sql(
                    'INSERT INTO availability_translations (translatable_id, name, locale)
                            VALUES (:availabilityId, :availabilityName, :locale)',
                    [
                        'availabilityId' => $availabilityId,
                        'availabilityName' => $name,
                        'locale' => $locale,
                    ]
                );
            }

            $this->sql(
                'INSERT INTO setting_values (name, domain_id, type, value) VALUES (:name, 0, \'none\', :availabilityId)',
                [
                    'name' => $availabilityData['id'],
                    'availabilityId' => $availabilityId,
                ]
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
