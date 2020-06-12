<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200604091844 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('ALTER TABLE flags ADD news BOOLEAN NOT NULL DEFAULT FALSE');
        $this->sql('ALTER TABLE flags ALTER news DROP DEFAULT');
        $this->sql('UPDATE flags SET news = TRUE WHERE pohoda_id = \'Novinka\'');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
