<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200824075835 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('
            CREATE TABLE category_brands (
                category_id INT NOT NULL,
                brand_id INT NOT NULL,
                priority INT NOT NULL,
                PRIMARY KEY(category_id, brand_id)
            )');
        $this->sql('CREATE INDEX IDX_714A8E4F12469DE2 ON category_brands (category_id)');
        $this->sql('CREATE INDEX IDX_714A8E4F44F5D008 ON category_brands (brand_id)');
        $this->sql('
            ALTER TABLE
                category_brands
            ADD
                CONSTRAINT FK_714A8E4F12469DE2 FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->sql('
            ALTER TABLE
                category_brands
            ADD
                CONSTRAINT FK_714A8E4F44F5D008 FOREIGN KEY (brand_id) REFERENCES brands (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
