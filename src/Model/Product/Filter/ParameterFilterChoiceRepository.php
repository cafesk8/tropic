<?php

declare(strict_types=1);

namespace App\Model\Product\Filter;

use Doctrine\ORM\Query\Expr\Join;
use Shopsys\FrameworkBundle\Component\Doctrine\GroupedScalarHydrator;
use Shopsys\FrameworkBundle\Model\Category\Category;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Product\Filter\ParameterFilterChoice;
use Shopsys\FrameworkBundle\Model\Product\Filter\ParameterFilterChoiceRepository as BaseParameterFilterChoiceRepository;
use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValue;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValue;

/**
 * @property \App\Model\Product\ProductRepository $productRepository
 * @method __construct(\Doctrine\ORM\EntityManagerInterface $em, \App\Model\Product\ProductRepository $productRepository)
 * @method \App\Model\Product\Parameter\Parameter[] getVisibleParametersIndexedByIdOrderedByName(array $rows, string $locale)
 * @method \App\Model\Product\Parameter\ParameterValue[][] getParameterValuesIndexedByParameterIdOrderedByValueText(array $rows, string $locale)
 * @method \App\Model\Product\Parameter\ParameterValue[] getParameterValuesIndexedByIdOrderedByText(array $rows, string $locale)
 */
class ParameterFilterChoiceRepository extends BaseParameterFilterChoiceRepository
{
    /**
     * The difference with the parent method is that here we use getOfferedInCategoryQueryBuilder instead of getListableInCategoryQueryBuilder
     * so variant parameters are included in the filter and then we append set items' parameters as well
     *
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param string $locale
     * @param \App\Model\Category\Category $category
     * @return \Shopsys\FrameworkBundle\Model\Product\Filter\ParameterFilterChoice[]
     */
    public function getParameterFilterChoicesInCategory(
        $domainId,
        PricingGroup $pricingGroup,
        $locale,
        Category $category
    ) {
        $productsQueryBuilder = $this->productRepository->getOfferedInCategoryQueryBuilder(
            $domainId,
            $pricingGroup,
            $category
        );

        $productsQueryBuilder
            ->select('MIN(p), pp, pv')
            ->join(ProductParameterValue::class, 'ppv', Join::WITH, 'ppv.product = p')
            ->join(Parameter::class, 'pp', Join::WITH, 'pp = ppv.parameter AND pp IN (:parameters)')
            ->join(ParameterValue::class, 'pv', Join::WITH, 'pv = ppv.value AND pv.locale = :locale')
            ->groupBy('pp, pv')
            ->resetDQLPart('orderBy')
            ->setParameter('locale', $locale)
            ->setParameter('parameters', $category->getFilterParameters());

        $rows = $productsQueryBuilder->getQuery()->execute(null, GroupedScalarHydrator::HYDRATION_MODE);

        $setItemsQueryBuilder = $this->productRepository->getVisibleSetItemsInCategoryQueryBuilder(
            $domainId,
            $pricingGroup,
            $category
        );

        $setItemsQueryBuilder
            ->select('MIN(setItem), pp, pv')
            ->join(ProductParameterValue::class, 'ppv', Join::WITH, 'ppv.product = setItem')
            ->join(Parameter::class, 'pp', Join::WITH, 'pp = ppv.parameter AND pp IN (:parameters)')
            ->join(ParameterValue::class, 'pv', Join::WITH, 'pv = ppv.value AND pv.locale = :locale')
            ->groupBy('pp, pv')
            ->resetDQLPart('orderBy')
            ->setParameter('locale', $locale)
            ->setParameter('parameters', $category->getFilterParameters());

        $setItemRows = $setItemsQueryBuilder->getQuery()->execute(null, GroupedScalarHydrator::HYDRATION_MODE);

        foreach ($setItemRows as $setItemRow) {
            $rows[] = $setItemRow;
        }

        $visibleParametersIndexedById = $this->getVisibleParametersIndexedByIdOrderedByName($rows, $locale);
        $parameterValuesIndexedByParameterId = $this->getParameterValuesIndexedByParameterIdOrderedByValueText($rows, $locale);
        $parameterFilterChoices = [];

        foreach ($visibleParametersIndexedById as $parameterId => $parameter) {
            if (array_key_exists($parameterId, $parameterValuesIndexedByParameterId)) {
                $parameterFilterChoices[] = new ParameterFilterChoice(
                    $parameter,
                    $parameterValuesIndexedByParameterId[$parameterId]
                );
            }
        }

        return $parameterFilterChoices;
    }
}
