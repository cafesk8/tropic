<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use PDO;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20201103125500 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $results = $this->sql('SELECT order_id, go_pay_status FROM gopay_transactions ORDER BY go_pay_id')
            ->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as $result) {
            $this->sql('UPDATE orders SET go_pay_status = :goPayStatus WHERE id = :orderId', [
                'goPayStatus' => $result['go_pay_status'],
                'orderId' => $result['order_id'],
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
