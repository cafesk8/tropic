<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190415140415 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->sql('
            CREATE TABLE stores (
                id SERIAL NOT NULL,
                domain_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                description TEXT DEFAULT NULL,
                street VARCHAR(255) DEFAULT NULL,
                city VARCHAR(100) DEFAULT NULL,
                postcode VARCHAR(30) DEFAULT NULL,
                opening_hours VARCHAR(255) DEFAULT NULL,
                google_maps_link TEXT DEFAULT NULL,
                position INT DEFAULT NULL,
                PRIMARY KEY(id)
            )');
        $this->sql('ALTER TABLE stores ALTER description DROP DEFAULT;');
        $this->sql('ALTER TABLE stores ALTER street DROP DEFAULT;');
        $this->sql('ALTER TABLE stores ALTER city DROP DEFAULT;');
        $this->sql('ALTER TABLE stores ALTER postcode DROP DEFAULT;');
        $this->sql('ALTER TABLE stores ALTER opening_hours DROP DEFAULT;');
        $this->sql('ALTER TABLE stores ALTER google_maps_link DROP DEFAULT;');
        $this->sql('ALTER TABLE stores ALTER position DROP DEFAULT;');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
