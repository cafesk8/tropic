<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200619131400 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $parameters = $this->sql('SELECT id FROM parameters')->fetchAll();
        $categories = $this->sql('SELECT id FROM categories')->fetchAll();

        foreach ($parameters as $parameter) {
            foreach ($categories as $category) {
                $this->sql('INSERT INTO category_parameters(parameter_id, category_id) VALUES (:parameterId, :categoryId)', [
                    'parameterId' => $parameter['id'],
                    'categoryId' => $category['id'],
                ]);
            }
        }
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
