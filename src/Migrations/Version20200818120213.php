<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200818120213 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('
            CREATE TABLE heureka_reviews (
                id SERIAL NOT NULL,
                name VARCHAR(60),
                rating_id INT NOT NULL,
                added_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                total_ratings DOUBLE PRECISION NOT NULL,
                pros TEXT DEFAULT NULL,
                cons TEXT DEFAULT NULL,
                summary TEXT DEFAULT NULL,
                PRIMARY KEY(id)
            )');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
