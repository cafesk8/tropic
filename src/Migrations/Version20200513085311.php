<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200513085311 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql(
            'UPDATE availability_translations SET name = :newInStockAvailabilityName
            WHERE name = :oldInStockAvailabilityName',
            [
                'newInStockAvailabilityName' => 'Skladem v e-shopu',
                'oldInStockAvailabilityName' => 'Skladem',
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
