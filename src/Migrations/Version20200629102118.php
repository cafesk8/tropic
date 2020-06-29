<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200629102118 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('UPDATE transfers SET frequency = :transferFrequency WHERE identifier = :transferName', [
            'transferFrequency' => 'každých 5 minut',
            'transferName' => 'import_product_images',
        ]);

        $this->sql('UPDATE transfers SET frequency = :transferFrequency WHERE identifier = :transferName', [
            'transferFrequency' => 'jednou denně (v 23:30)',
            'transferName' => 'import_categories',
        ]);
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
