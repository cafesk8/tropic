<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20210608153558 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('ALTER TABLE category_domains ADD advert_id INT DEFAULT NULL');
        $this->sql('
            ALTER TABLE
                category_domains
            ADD
                CONSTRAINT FK_4BA3FFE3D07ECCB6 FOREIGN KEY (advert_id) REFERENCES adverts (id) ON DELETE
            SET
                NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->sql('CREATE INDEX IDX_4BA3FFE3D07ECCB6 ON category_domains (advert_id)');
        $advertsData = $this->sql('SELECT c.id AS category_id, a.id AS advert_id, a.domain_id AS domain_id FROM categories c JOIN adverts a ON c.advert_id = a.id WHERE c.advert_id IS NOT NULL')->fetchAll();
        foreach ($advertsData as $advertData) {
            $this->sql('UPDATE category_domains SET advert_id = :advertId WHERE category_id = :categoryId AND domain_id = :domainId', [
                'advertId' => $advertData['advert_id'],
                'categoryId' => $advertData['category_id'],
                'domainId' => $advertData['domain_id'],
            ]);
        }
        $this->sql('ALTER TABLE categories DROP advert_id');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
