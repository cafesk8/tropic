<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Schema\Schema;
use Shopsys\FrameworkBundle\Component\String\TransformString;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200717073541 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('ALTER TABLE brands ADD slug VARCHAR(255) DEFAULT NULL');
        $brands = $this->sql('SELECT id, name FROM brands')->fetchAll(FetchMode::ASSOCIATIVE);

        foreach ($brands as $brand) {
            $this->sql('UPDATE brands SET slug = :slug WHERE id = :id', [
                'slug' => TransformString::stringToFriendlyUrlSlug($brand['name']),
                'id' => $brand['id'],
            ]);
        }

        $this->sql('ALTER TABLE brands ALTER slug SET NOT NULL');
        $this->sql('ALTER TABLE brands ALTER slug DROP DEFAULT');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
