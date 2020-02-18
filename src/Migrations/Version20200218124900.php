<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\FrameworkBundle\Model\Order\Status\OrderStatus;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200218124900 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $validOrderStatusTypes = [OrderStatus::TYPE_NEW, OrderStatus::TYPE_IN_PROGRESS, OrderStatus::TYPE_DONE, OrderStatus::TYPE_CANCELED];
        $orderStatuses = $this->sql('SELECT id FROM order_statuses WHERE type NOT IN (' . implode(', ', $validOrderStatusTypes) . ')')->fetchAll();

        if (count($orderStatuses) > 0) {
            $this->sql('DELETE FROM order_status_translations WHERE translatable_id IN (' . implode(', ', array_column($orderStatuses, 'id')) . ')');

            foreach ($orderStatuses as $orderStatus) {
                $this->sql('DELETE FROM mail_templates WHERE name = \'order_status_' . $orderStatus['id'] . '\'');
            }

            $this->sql('DELETE FROM order_statuses WHERE type NOT IN (' . implode(', ', $validOrderStatusTypes) . ')');
        }
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
