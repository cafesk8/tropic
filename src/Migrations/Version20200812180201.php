<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Shopsys\MigrationBundle\Component\Doctrine\Migrations\AbstractMigration;

class Version20200812180201 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function up(Schema $schema): void
    {
        $mainVariantsProductParameterValues = $this->sql('SElECT ppv.product_id, ppv.parameter_id, ppv.value_id FROM product_parameter_values ppv JOIN products p ON ppv.product_id = p.id AND p.variant_type = \'main\'')->fetchAll();
        $productParameterValuesIndexedByMainVariantId = [];
        foreach ($mainVariantsProductParameterValues as $productParameterValue) {
            $productParameterValuesIndexedByMainVariantId[$productParameterValue['product_id']][] = [
                'parameter_id' => $productParameterValue['parameter_id'],
                'value_id' => $productParameterValue['value_id'],
            ];
        }
        foreach ($productParameterValuesIndexedByMainVariantId as $mainVariantId => $parameterValues) {
            $variantIds = $this->sql('SELECT id from products WHERE main_variant_id = :mainVariantId', [
                'mainVariantId' => $mainVariantId
            ])->fetchAll();
            foreach (array_column($variantIds, 'id') as $variantId) {
                $variantParameterIds = $this->sql('SElECT ppv.parameter_id as parameter_id FROM product_parameter_values ppv JOIN products p ON ppv.product_id = p.id AND p.id = :id', [
                    'id' => $variantId
                ])->fetchAll();
                foreach ($parameterValues as $mainVariantParameterValue) {
                    if (in_array($mainVariantParameterValue['parameter_id'], array_column($variantParameterIds, 'parameter_id')) === false) {
                        $this->sql('INSERT INTO product_parameter_values(product_id, parameter_id, value_id) VALUES (:productId, :parameterId, :valueId)', [
                            'productId' => $variantId,
                            'parameterId' => $mainVariantParameterValue['parameter_id'],
                            'valueId' => $mainVariantParameterValue['value_id'],
                        ]);
                    }
                }
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
