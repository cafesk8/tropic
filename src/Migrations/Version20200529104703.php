<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200529104703 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->sql('ALTER TABLE product_domains ADD shown BOOLEAN NOT NULL DEFAULT TRUE');
        $this->sql('ALTER TABLE product_domains ALTER shown DROP DEFAULT');
        $query = $this->connection->query('SELECT id, hidden FROM products');

        while ($product = $query->fetch()) {
            $this->sql('UPDATE product_domains SET shown = :shown WHERE product_id = :productId', [
                'shown' => (int)!$product['hidden'],
                'productId' => $product['id'],
            ]);
        }
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
