<?php

declare(strict_types = 1);

namespace Shopsys\ShopBundle\Model\Product\MassEdit;

use Doctrine\ORM\QueryBuilder;

interface MassEditActionInterface
{
    /**
     * Returns unique action name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Returns displayed name.
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * Returns array of operations.
     *
     * Keys are symbolic operation names and values are their labels.
     *
     * @return string[]
     */
    public function getOperations(): array;

    /**
     * Returns a form type that should be used for value selection
     *
     * If using array, keys are used as input names and you need to use prefix "value" for keys.
     *
     * @param string $operation Symbolic operation name defined in getOperations() array indices
     * @return string|\Symfony\Component\Form\FormTypeInterface|\Symfony\Component\Form\FormTypeInterface[]
     */
    public function getValueFormType(string $operation);

    /**
     * Returns options that will be passed to the form type used for value selection
     *
     * If ValueFormType returns array, you need this to be an array also. Keys are input names.
     * Non specified options will be assumed to be empty array.
     *
     * @param string $operation Symbolic operation name defined in getOperations() array indices
     * @return array
     */
    public function getValueFormOptions(string $operation): array;

    /**
     * In $queryBuilder is p used as alias for Product
     *
     * @param \Doctrine\ORM\QueryBuilder $selectedProductsQueryBuilder
     * @param string $operation Symbolic operation name defined in getOperations() array indices
     * @param mixed $value Single value or array of values (array key = input name)
     */
    public function perform(QueryBuilder $selectedProductsQueryBuilder, string $operation, $value): void;
}
