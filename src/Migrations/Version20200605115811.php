<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200605115811 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('
            CREATE TABLE watch_dogs (
                id SERIAL NOT NULL,
                created_at DATE NOT NULL,
                product_id INT NOT NULL,
                pricing_group_id INT NOT NULL,
                email VARCHAR(255) NOT NULL,
                availability_watcher BOOLEAN NOT NULL,
                price_watcher BOOLEAN NOT NULL,
                original_price NUMERIC(20, 6) NOT NULL,
                targeted_discount NUMERIC(20, 6) NULL,
                PRIMARY KEY(id)
            )');
        $this->sql('CREATE INDEX IDX_64D84EE04584665A ON watch_dogs (product_id)');
        $this->sql('CREATE INDEX IDX_64D84EE0BE4A29AF ON watch_dogs (pricing_group_id)');
        $this->sql('COMMENT ON COLUMN watch_dogs.original_price IS \'(DC2Type:money)\'');
        $this->sql('COMMENT ON COLUMN watch_dogs.targeted_discount IS \'(DC2Type:money)\'');
        $this->sql('
            ALTER TABLE
                watch_dogs
            ADD
                CONSTRAINT FK_64D84EE04584665A FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->sql('
            ALTER TABLE
                watch_dogs
            ADD
                CONSTRAINT FK_64D84EE0BE4A29AF FOREIGN KEY (pricing_group_id) REFERENCES pricing_groups (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
