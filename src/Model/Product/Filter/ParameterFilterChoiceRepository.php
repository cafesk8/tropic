<?php

declare(strict_types=1);

namespace App\Model\Product\Filter;

use App\Model\Product\ColorHelper;
use App\Model\Product\Parameter\Parameter;
use App\Model\Product\Parameter\ParameterValue as ProjectParameterValue;
use App\Model\Product\SizeHelper;
use Doctrine\ORM\Query\Expr\Join;
use Shopsys\FrameworkBundle\Component\Doctrine\GroupedScalarHydrator;
use Shopsys\FrameworkBundle\Model\Category\Category;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Product\Filter\ParameterFilterChoice;
use Shopsys\FrameworkBundle\Model\Product\Filter\ParameterFilterChoiceRepository as BaseParameterFilterChoiceRepository;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValue;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValue;
use Shopsys\FrameworkBundle\Model\Product\Product;

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
        $productsQueryBuilder = $this->productRepository->getListableInCategoryQueryBuilder(
            $domainId,
            $pricingGroup,
            $category
        );

        $productsQueryBuilder
            ->select('MIN(p), pp, pv')
            ->join(ProductParameterValue::class, 'ppv', Join::WITH, 'ppv.product = p')
            ->join(Parameter::class, 'pp', Join::WITH, 'pp = ppv.parameter')
            ->join(ParameterValue::class, 'pv', Join::WITH, 'pv = ppv.value AND pv.locale = :locale')
            ->andWhere('pp.type NOT IN (:specialTypes)')
            ->groupBy('pp, pv')
            ->setParameter('specialTypes', [Parameter::TYPE_SIZE, Parameter::TYPE_COLOR])
            ->resetDQLPart('orderBy')
            ->setParameter('locale', $locale);

        $rows = $productsQueryBuilder->getQuery()->execute(null, GroupedScalarHydrator::HYDRATION_MODE);

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

    /**
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param string $locale
     * @param \App\Model\Category\Category $category
     * @return \App\Model\Product\Parameter\ParameterValue[]
     */
    public function getColorParameterFilterChoicesInCategory(
        $domainId,
        PricingGroup $pricingGroup,
        $locale,
        Category $category
    ) {
        $productsQueryBuilder = $this->productRepository->getListableInCategoryQueryBuilder(
            $domainId,
            $pricingGroup,
            $category
        );

        $productsQueryBuilder
            ->select('MIN(p), pp, pv')
            ->join(ProductParameterValue::class, 'ppv', Join::WITH, 'ppv.product = p')
            ->join(Parameter::class, 'pp', Join::WITH, 'pp = ppv.parameter')
            ->join(ParameterValue::class, 'pv', Join::WITH, 'pv = ppv.value AND pv.locale = :locale')
            ->andWhere('pp.type = :colorType')
            ->andWhere('pv.rgb IS NOT NULL')
            ->groupBy('pp, pv, pv.rgb')
            ->setParameter('colorType', Parameter::TYPE_COLOR)
            ->resetDQLPart('orderBy')
            ->setParameter('locale', $locale);

        $rows = $productsQueryBuilder->getQuery()->execute(null, GroupedScalarHydrator::HYDRATION_MODE);

        $parameterValuesIndexedByParameterId = $this->getParameterValuesIndexedByIdOrderedByText($rows, $locale);

        usort($parameterValuesIndexedByParameterId, function (ProjectParameterValue $parameterValue1, ProjectParameterValue $parameterValue2) {
            $hue1 = ColorHelper::hexToHue($parameterValue1->getRgb());
            $hue2 = ColorHelper::hexToHue($parameterValue2->getRgb());
            return $hue1 <=> $hue2;
        });

        return $parameterValuesIndexedByParameterId;
    }

    /**
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param string $locale
     * @param \App\Model\Category\Category $category
     * @return \App\Model\Product\Parameter\ParameterValue[]
     */
    public function getSizeParameterFilterChoicesInCategory(
        $domainId,
        PricingGroup $pricingGroup,
        $locale,
        Category $category
    ) {
        $productsQueryBuilder = $this->productRepository->getListableInCategoryQueryBuilder(
            $domainId,
            $pricingGroup,
            $category
        );

        $productsQueryBuilder
            ->select('MIN(p), pp, pv')
            ->leftJoin(Product::class, 'variant', Join::WITH, 'variant.mainVariant = p')
            ->join(ProductParameterValue::class, 'ppv', Join::WITH, 'ppv.product = p OR ppv.product = variant')
            ->join(Parameter::class, 'pp', Join::WITH, 'pp = ppv.parameter')
            ->join(ParameterValue::class, 'pv', Join::WITH, 'pv = ppv.value AND pv.locale = :locale')
            ->andWhere('pp.type = :sizeType')
            ->groupBy('pp, pv')
            ->setParameter('sizeType', Parameter::TYPE_SIZE)
            ->resetDQLPart('orderBy')
            ->setParameter('locale', $locale);

        $rows = $productsQueryBuilder->getQuery()->execute(null, GroupedScalarHydrator::HYDRATION_MODE);

        $parameterValuesIndexedByParameterId = $this->getParameterValuesIndexedByIdOrderedByText($rows, $locale);

        usort($parameterValuesIndexedByParameterId, [SizeHelper::class, 'compareSizesInObject']);

        return $parameterValuesIndexedByParameterId;
    }
}
