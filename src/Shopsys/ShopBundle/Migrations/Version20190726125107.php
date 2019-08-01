<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20190726125107 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->sql('
            CREATE TABLE transport_countries (
                transport_id INT NOT NULL,
                country_id INT NOT NULL,
                PRIMARY KEY(transport_id, country_id)
            )');
        $this->sql('CREATE INDEX IDX_CBA56D369909C13F ON transport_countries (transport_id)');
        $this->sql('CREATE INDEX IDX_CBA56D36F92F3E70 ON transport_countries (country_id)');
        $this->sql('
            ALTER TABLE
                transport_countries
            ADD
                CONSTRAINT FK_CBA56D369909C13F FOREIGN KEY (transport_id) REFERENCES transports (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->sql('
            ALTER TABLE
                transport_countries
            ADD
                CONSTRAINT FK_CBA56D36F92F3E70 FOREIGN KEY (country_id) REFERENCES countries (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
