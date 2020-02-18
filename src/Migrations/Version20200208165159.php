<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use PDO;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200208165159 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $administratorIds = $this->sql('SELECT id FROM administrators')->fetchAll(PDO::FETCH_COLUMN);
        $roles = [
            'ROLE_VIEW_SETTINGS',
            'ROLE_VIEW_ORDERS',
            'ROLE_VIEW_CUSTOMERS',
            'ROLE_VIEW_PRODUCTS',
            'ROLE_VIEW_PRICING',
            'ROLE_VIEW_MARKETING',
            'ROLE_VIEW_ADMINISTRATORS',
        ];
        foreach ($administratorIds as $administratorId) {
            foreach ($roles as $role) {
                $this->sql('INSERT INTO administrator_roles(administrator_id, role) VALUES (:administratorId, :role)', [
                    ':administratorId' => $administratorId,
                    ':role' => $role,
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
