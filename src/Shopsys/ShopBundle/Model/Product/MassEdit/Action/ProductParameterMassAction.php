<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\MassEdit\Action;

use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Form\Locale\LocalizedType;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValueData;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValueDataFactory;
use Shopsys\ShopBundle\Model\Product\MassEdit\MassEditActionInterface;
use Shopsys\ShopBundle\Model\Product\Parameter\Parameter;
use Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade;
use Shopsys\ShopBundle\Model\Product\Parameter\ParameterValueDataFactory;
use Shopsys\ShopBundle\Model\Product\Product;
use Shopsys\ShopBundle\Model\Product\ProductDataFactory;
use Shopsys\ShopBundle\Model\Product\ProductFacade;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class ProductParameterMassAction implements MassEditActionInterface
{
    public const NAME = 'parameter';

    public const OPERATION_ADD = 'add';
    public const OPERATION_REMOVE = 'remove';

    public const INPUT_PARAMETER = 'value_parameter';
    public const INPUT_PARAMETER_VALUE = 'value_parameter_value';

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade
     */
    private $parameterFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductDataFactory
     */
    private $productDataFactory;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Parameter\ParameterValueDataFactory
     */
    private $parameterValueDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValueDataFactory
     */
    private $productParameterValueDataFactory;

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade $parameterFacade
     * @param \Shopsys\ShopBundle\Model\Product\ProductDataFactory $productDataFactory
     * @param \Shopsys\ShopBundle\Model\Product\ProductFacade $productFacade
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\ParameterValueDataFactory $parameterValueDataFactory
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValueDataFactory $productParameterValueDataFactory
     */
    public function __construct(
        ParameterFacade $parameterFacade,
        ProductDataFactory $productDataFactory,
        ProductFacade $productFacade,
        ParameterValueDataFactory $parameterValueDataFactory,
        ProductParameterValueDataFactory $productParameterValueDataFactory
    ) {
        $this->parameterFacade = $parameterFacade;
        $this->productDataFactory = $productDataFactory;
        $this->productFacade = $productFacade;
        $this->parameterValueDataFactory = $parameterValueDataFactory;
        $this->productParameterValueDataFactory = $productParameterValueDataFactory;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): string
    {
        return t('Parametr');
    }

    /**
     * @inheritDoc
     */
    public function getOperations(): array
    {
        return [
            self::OPERATION_ADD => t('Přidat'),
            self::OPERATION_REMOVE => t('Odebrat'),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getValueFormType(string $operation)
    {
        $operations = [];
        $operations[self::INPUT_PARAMETER] = ChoiceType::class;

        if ($operation === self::OPERATION_ADD) {
            $operations[self::INPUT_PARAMETER_VALUE] = LocalizedType::class;
        }

        return $operations;
    }

    /**
     * @inheritDoc
     */
    public function getValueFormOptions(string $operation): array
    {
        $parameters = $this->parameterFacade->getAll();

        return [
            self::INPUT_PARAMETER => [
                'required' => true,
                'choices' => $parameters,
                'choice_label' => 'name',
                'choice_value' => 'id',
            ],
            self::INPUT_PARAMETER_VALUE => [
                'required' => true,
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function perform(QueryBuilder $selectedProductsQueryBuilder, string $operation, $values): void
    {
        $productsIterableResult = $selectedProductsQueryBuilder
            ->select('p')
            ->distinct()
            ->getQuery()->iterate();

        $parameter = is_array($values) ? $values[self::INPUT_PARAMETER] : $values;

        switch ($operation) {
            case self::OPERATION_ADD:
                $parameterValues = $values[self::INPUT_PARAMETER_VALUE];
                foreach ($productsIterableResult as $row) {
                    $product = $row[0];
                    $this->addParameterToProduct($product, $parameter, $parameterValues);
                }
                break;
            case self::OPERATION_REMOVE:
                foreach ($productsIterableResult as $row) {
                    $product = $row[0];
                    $this->removeParameterFromProduct($product, $parameter);
                }
                break;
        }
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\Parameter $parameter
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\ParameterValue[] $parameterValues
     */
    private function addParameterToProduct(Product $product, Parameter $parameter, array $parameterValues): void
    {
        $changed = false;
        $productData = $this->productDataFactory->createFromProduct($product);
        $productParameterValuesData = $productData->parameters;
        $productParameterValuesData = $this->filterProductParameterValuesDataByParameter($productParameterValuesData, $parameter);

        foreach ($parameterValues as $locale => $parameterText) {
            $parameterValueData = $this->parameterValueDataFactory->create();
            $parameterValueData->text = $parameterText;
            $parameterValueData->locale = $locale;
            $productParameterValueData = $this->productParameterValueDataFactory->create();
            $productParameterValueData->parameter = $parameter;
            $productParameterValueData->parameterValueData = $parameterValueData;
            if (!in_array($productParameterValueData, $productParameterValuesData, false)) {
                $productParameterValuesData[] = $productParameterValueData;
                $changed = true;
            }
        }
        if ($changed === true) {
            $this->productFacade->saveParameters($product, $productParameterValuesData);
        }
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\Parameter $parameter
     */
    private function removeParameterFromProduct(Product $product, Parameter $parameter): void
    {
        $changed = false;
        $productData = $this->productDataFactory->createFromProduct($product);
        $productParameterValuesData = $productData->parameters;
        foreach ($productParameterValuesData as $key => $productParameterValueData) {
            if ($productParameterValueData->parameter === $parameter) {
                unset($productParameterValuesData[$key]);
                $changed = true;
            }
        }
        if ($changed === true) {
            $productData->parameters = $productParameterValuesData;
            $this->productFacade->saveParameters($product, $productParameterValuesData);
        }
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValueData[] $productParameterValuesData
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\Parameter $parameter
     * @return array
     */
    private function filterProductParameterValuesDataByParameter(array $productParameterValuesData, Parameter $parameter): array
    {
        return array_filter($productParameterValuesData, function (ProductParameterValueData $productParameterValueData) use ($parameter) {
            return $productParameterValueData->parameter !== $parameter;
        });
    }
}
