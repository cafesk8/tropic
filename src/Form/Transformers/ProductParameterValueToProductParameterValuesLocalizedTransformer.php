<?php

declare(strict_types=1);

namespace App\Form\Transformers;

use App\Model\Product\Parameter\ProductParameterValuesLocalizedData;
use Shopsys\FrameworkBundle\Form\Transformers\ProductParameterValueToProductParameterValuesLocalizedTransformer as BaseProductParameterValueToProductParameterValuesLocalizedTransformer;

/**
 * @property \App\Model\Product\Parameter\ProductParameterValueDataFactory $productParameterValueDataFactory
 * @property \App\Model\Product\Parameter\ParameterValueDataFactory $parameterValueDataFactory
 * @method __construct(\App\Model\Product\Parameter\ProductParameterValueDataFactory $productParameterValueDataFactory, \App\Model\Product\Parameter\ParameterValueDataFactory $parameterValueDataFactory)
 */
class ProductParameterValueToProductParameterValuesLocalizedTransformer extends BaseProductParameterValueToProductParameterValuesLocalizedTransformer
{
    /**
     * @param \App\Model\Product\Parameter\ProductParameterValueData[]|mixed $normData
     * @return \App\Model\Product\Parameter\ProductParameterValuesLocalizedData[]|null
     */
    public function transform($normData)
    {
        if ($normData === null) {
            return null;
        }

        if (!is_array($normData)) {
            throw new \Symfony\Component\Form\Exception\TransformationFailedException('Invalid value');
        }

        $normValue = [];
        foreach ($normData as $productParameterValueData) {
            $parameterId = $productParameterValueData->parameter->getId();
            $locale = $productParameterValueData->parameterValueData->locale;

            if (!array_key_exists($parameterId, $normValue)) {
                $normValue[$parameterId] = new ProductParameterValuesLocalizedData();
                $normValue[$parameterId]->parameter = $productParameterValueData->parameter;
                $normValue[$parameterId]->position = $productParameterValueData->position;
                $normValue[$parameterId]->takenFromMainVariant = $productParameterValueData->takenFromMainVariant;
                $normValue[$parameterId]->valueTextsByLocale = [];
            }

            if (array_key_exists($locale, $normValue[$parameterId]->valueTextsByLocale)) {
                throw new \Symfony\Component\Form\Exception\TransformationFailedException('Duplicate parameter');
            }

            $normValue[$parameterId]->valueTextsByLocale[$locale] = $productParameterValueData->parameterValueData->text;
        }

        return array_values($normValue);
    }

    /**
     * @param \App\Model\Product\Parameter\ProductParameterValuesLocalizedData[]|mixed $viewData
     * @return \App\Model\Product\Parameter\ProductParameterValueData[]
     */
    public function reverseTransform($viewData)
    {
        if (is_array($viewData)) {
            $normData = [];

            foreach ($viewData as $productParameterValuesLocalizedData) {
                foreach ($productParameterValuesLocalizedData->valueTextsByLocale as $locale => $valueText) {
                    if ($valueText !== null) {
                        $productParameterValueData = $this->productParameterValueDataFactory->create();
                        $productParameterValueData->parameter = $productParameterValuesLocalizedData->parameter;
                        $productParameterValueData->takenFromMainVariant = $productParameterValuesLocalizedData->takenFromMainVariant;
                        $productParameterValueData->position = $productParameterValuesLocalizedData->position;
                        $parameterValueData = $this->parameterValueDataFactory->create();
                        $parameterValueData->text = $valueText;
                        $parameterValueData->locale = $locale;
                        $productParameterValueData->parameterValueData = $parameterValueData;

                        $normData[] = $productParameterValueData;
                    }
                }
            }

            return $normData;
        }

        throw new \Symfony\Component\Form\Exception\TransformationFailedException('Invalid value');
    }
}
