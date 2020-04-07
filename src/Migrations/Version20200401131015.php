<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use PDO;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200401131015 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema)
    {
        $mainVariantsIds = $this->sql('SELECT id FROM products WHERE variant_type = :variantType', [
            'variantType' => 'main',
        ])->fetchAll(PDO::FETCH_COLUMN);

        foreach ($mainVariantsIds as $mainVariantId) {
            $this->sql('UPDATE products SET variant_id = :variantId WHERE id = :id', [
                'variantId' => $mainVariantId,
                'id' => $mainVariantId,
            ]);
            $variantsIds = $this->sql('SELECT id FROM products WHERE main_variant_id = :mainVariantId', [
                'mainVariantId' => $mainVariantId,
            ])->fetchAll(PDO::FETCH_COLUMN);

            foreach ($variantsIds as $variantId) {
                $this->sql('UPDATE products SET variant_id = :variantId WHERE id = :id', [
                    'variantId' => $mainVariantId . '/' . $variantId,
                    'id' => $variantId,
                ]);
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
