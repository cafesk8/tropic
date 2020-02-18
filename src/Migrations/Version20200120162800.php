<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\FrameworkBundle\Migrations\MultidomainMigrationTrait;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200120162800 extends AbstractMigration
{
    use MultidomainMigrationTrait;

    private const NEW_ORDER_STATUES_BY_TYPE = [
        '5' => 'Částečné vykrytí',
        '6' => 'Částečné vykrytí - Odběrné místo',
        '7' => 'Objednávka vykrytá',
        '8' => 'Objednávka vykrytá - Odběrné místo',
        '9' => 'Vrácené zboží',
    ];

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        foreach (self::NEW_ORDER_STATUES_BY_TYPE as $orderStatusId => $orderStatusName) {
            foreach (['cs', 'en', 'de', 'sk'] as $locale) {
                $this->sql('INSERT INTO order_status_translations (translatable_id, name, locale) 
                VALUES (:translatable_id, :name, :locale) ON CONFLICT DO NOTHING', [
                    'translatable_id' => $orderStatusId,
                    'name' => $orderStatusName,
                    'locale' => $locale,
                ]);
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
