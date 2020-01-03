<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use PDO;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20191119185742 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $userTransferIdsAndEansData = $this->sql('
            SELECT id, ean, transfer_id FROM users
            WHERE ean IS NOT NULL
            AND transfer_id IS NOT NULL
            AND
            (
                id NOT IN (SELECT customer_id FROM user_transfer_ids_and_eans)
                OR
                ean NOT IN (SELECT ean FROM user_transfer_ids_and_eans)
            )')->fetchAll(PDO::FETCH_ASSOC);

        foreach ($userTransferIdsAndEansData as $data) {
            $this->sql('INSERT INTO user_transfer_ids_and_eans (customer_id, ean, transfer_id) VALUES (:customerId, :ean, :transferId)', [
                'customerId' => $data['id'],
                'ean' => $data['ean'],
                'transferId' => $data['transfer_id'],
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
