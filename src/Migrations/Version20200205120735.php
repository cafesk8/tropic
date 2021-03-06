<?php

declare(strict_types=1);

namespace App\Migrations;

use App\Model\Product\Availability\AvailabilityData;
use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200205120735 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('ALTER TABLE availabilities ADD rgb_color VARCHAR(7) NOT NULL DEFAULT \'' . AvailabilityData::DEFAULT_COLOR . '\'');
        $this->sql('ALTER TABLE availabilities ALTER rgb_color DROP DEFAULT');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
