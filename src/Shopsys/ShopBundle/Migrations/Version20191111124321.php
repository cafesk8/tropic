<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use PDO;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20191111124321 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $productIdAndGiftIdItems = $this->sql('SELECT id as "productId", gift_id as "giftId" FROM products WHERE gift_id IS NOT NULL')
            ->fetchAll(PDO::FETCH_ASSOC);

        $productIdsByGiftIds = [];
        foreach ($productIdAndGiftIdItems as $productIdAndGiftId) {
            $giftId = $productIdAndGiftId['giftId'];
            $productId = $productIdAndGiftId['productId'];

            if (!isset($productIdsByGiftIds[$giftId])) {
                $productIdsByGiftIds[$giftId] = [];
            }

            $productIdsByGiftIds[$giftId][] = $productId;
        }

        foreach ($productIdsByGiftIds as $giftId => $productIds) {
            $this->sql(
                'INSERT INTO "product_gifts" ("gift_id", "domain_id", "active") VALUES (:giftId, \'1\', \'1\');',
                ['giftId' => $giftId]
            );
            $productGiftsTableLastInsertedId = (int)$this->connection->lastInsertId('product_gifts_id_seq');

            foreach ($productIds as $productId) {
                $this->sql(
                    'INSERT INTO "product_gift_products" ("product_gift_id", "product_id") VALUES (:productGiftId, :productId);',
                    [
                        'productGiftId' => $productGiftsTableLastInsertedId,
                        'productId' => $productId,
                    ]
                );
            }
        }
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
