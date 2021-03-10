<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20210216110718 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('ALTER TABLE category_domains ADD tip_product_id INT DEFAULT NULL');
        $this->sql('ALTER TABLE category_domains ADD tip_shown BOOLEAN NOT NULL DEFAULT FALSE');
        $this->sql('ALTER TABLE category_domains ALTER tip_shown DROP DEFAULT');
        $this->sql('ALTER TABLE category_domains ADD tip_name VARCHAR(255) DEFAULT NULL');
        $this->sql('ALTER TABLE category_domains ADD tip_text VARCHAR(255) DEFAULT NULL');
        $this->sql('ALTER TABLE category_domains ADD CONSTRAINT FK_4BA3FFE3C21C3315 FOREIGN KEY (tip_product_id) REFERENCES products (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->sql('CREATE UNIQUE INDEX UNIQ_4BA3FFE3C21C3315 ON category_domains (tip_product_id)');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
