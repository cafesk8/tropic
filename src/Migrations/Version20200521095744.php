<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200521095744 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('ALTER TABLE product_domains ADD description_hash VARCHAR(255) DEFAULT NULL');
        $this->sql('ALTER TABLE product_domains ADD short_description_hash VARCHAR(255) DEFAULT NULL');
        $this->sql('ALTER TABLE products ADD description_automatically_translated BOOLEAN NOT NULL DEFAULT FALSE');
        $this->sql('ALTER TABLE products ADD short_description_automatically_translated BOOLEAN NOT NULL DEFAULT FALSE');
        $this->sql('ALTER TABLE product_domains ALTER description_hash DROP DEFAULT');
        $this->sql('ALTER TABLE product_domains ALTER short_description_hash DROP DEFAULT');
        $this->sql('ALTER TABLE products ALTER description_automatically_translated DROP DEFAULT');
        $this->sql('ALTER TABLE products ALTER short_description_automatically_translated DROP DEFAULT');

        $this->sql('ALTER TABLE product_flags DROP CONSTRAINT FK_AA593AE64584665A');
        $this->sql('ALTER TABLE product_flags DROP CONSTRAINT FK_AA593AE6919FE4E5');
        $this->sql('
            ALTER TABLE
                product_flags
            ADD
                CONSTRAINT FK_AA593AE64584665A FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->sql('
            ALTER TABLE
                product_flags
            ADD
                CONSTRAINT FK_AA593AE6919FE4E5 FOREIGN KEY (flag_id) REFERENCES flags (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
