<?php

namespace SS6\ShopBundle\Model\Product\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use SS6\ShopBundle\Model\Category\Category;
use SS6\ShopBundle\Model\Pricing\Group\PricingGroup;
use SS6\ShopBundle\Model\Product\Filter\ParameterFilterChoiceRepository;
use SS6\ShopBundle\Model\Product\Filter\ProductFilterCountData;
use SS6\ShopBundle\Model\Product\Filter\ProductFilterData;
use SS6\ShopBundle\Model\Product\Filter\ProductFilterRepository;
use SS6\ShopBundle\Model\Product\Parameter\ProductParameterValue;
use SS6\ShopBundle\Model\Product\Product;
use SS6\ShopBundle\Model\Product\ProductRepository;

class ProductFilterCountRepository {

	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	private $em;

	/**
	 * @var \SS6\ShopBundle\Model\Product\ProductRepository
	 */
	private $productRepository;

	/**
	 * @var \SS6\ShopBundle\Model\Product\Filter\ProductFilterRepository
	 */
	private $productFilterRepository;

	/**
	 * @var \SS6\ShopBundle\Model\Product\Filter\ParameterFilterChoiceRepository
	 */
	private $parameterFilterChoiceRepository;

	public function __construct(
		EntityManager $em,
		ProductRepository $productRepository,
		ProductFilterRepository $productFilterRepository,
		ParameterFilterChoiceRepository $parameterFilterChoiceRepository
	) {
		$this->em = $em;
		$this->productRepository = $productRepository;
		$this->productFilterRepository = $productFilterRepository;
		$this->parameterFilterChoiceRepository = $parameterFilterChoiceRepository;
	}

	/**
	 * @param \SS6\ShopBundle\Model\Category\Category $category
	 * @param int $domainId
	 * @param string $locale
	 * @param \SS6\ShopBundle\Model\Product\Filter\ProductFilterData $productFilterData
	 * @param \SS6\ShopBundle\Model\Pricing\Group\PricingGroup $pricingGroup
	 * @return \SS6\ShopBundle\Model\Product\Filter\ProductFilterCountData
	 */
	public function getProductFilterCountDataInCategory(
		Category $category,
		$domainId,
		$locale,
		ProductFilterData $productFilterData,
		PricingGroup $pricingGroup
	) {
		$productsQueryBuilder = $this->productRepository->getListableInCategoryQueryBuilder(
			$domainId,
			$pricingGroup,
			$category
		);

		$productFilterCountData = new ProductFilterCountData();
		$productFilterCountData->countInStock = $this->getCountInStock(
			$productsQueryBuilder,
			$productFilterData,
			$pricingGroup
		);
		$productFilterCountData->countByFlagId = $this->getCountByFlagId(
			$productsQueryBuilder,
			$productFilterData,
			$pricingGroup
		);
		$productFilterCountData->countByParameterIdAndValueId = $this->getCountByParameterIdAndValueId(
			$productsQueryBuilder,
			$productFilterData,
			$pricingGroup,
			$locale
		);

		return $productFilterCountData;
	}

	/**
	 * @param string|null $searchText
	 * @param int $domainId
	 * @param string $locale
	 * @param \SS6\ShopBundle\Model\Product\Filter\ProductFilterData $productFilterData
	 * @param \SS6\ShopBundle\Model\Pricing\Group\PricingGroup $pricingGroup
	 * @return \SS6\ShopBundle\Model\Product\Filter\ProductFilterCountData
	 */
	public function getProductFilterCountDataForSearch(
		$searchText,
		$domainId,
		$locale,
		ProductFilterData $productFilterData,
		PricingGroup $pricingGroup
	) {
		$productsQueryBuilder = $this->productRepository->getListableBySearchTextQueryBuilder(
			$domainId,
			$pricingGroup,
			$locale,
			$searchText
		);

		$productFilterCountData = new ProductFilterCountData();
		$productFilterCountData->countInStock = $this->getCountInStock(
			$productsQueryBuilder,
			$productFilterData,
			$pricingGroup
		);
		$productFilterCountData->countByFlagId = $this->getCountByFlagId(
			$productsQueryBuilder,
			$productFilterData,
			$pricingGroup
		);

		return $productFilterCountData;
	}

	/**
	 * @param \Doctrine\ORM\QueryBuilder $productsQueryBuilder
	 * @param \SS6\ShopBundle\Model\Product\Filter\ProductFilterData $productFilterData
	 * @param \SS6\ShopBundle\Model\Pricing\Group\PricingGroup $pricingGroup
	 * @return int
	 */
	private function getCountInStock(
		QueryBuilder $productsQueryBuilder,
		ProductFilterData $productFilterData,
		PricingGroup $pricingGroup
	) {
		$productsInStockQueryBuilder = clone $productsQueryBuilder;
		$productInStockFilterData = clone $productFilterData;

		$productInStockFilterData->inStock = true;

		$this->productFilterRepository->applyFiltering(
			$productsInStockQueryBuilder,
			$productInStockFilterData,
			$pricingGroup
		);

		$productsInStockQueryBuilder
			->select('COUNT(p)')
			->resetDQLPart('orderBy');

		return $productsInStockQueryBuilder->getQuery()->getSingleScalarResult();
	}

	/**
	 * @param \Doctrine\ORM\QueryBuilder $productsQueryBuilder
	 * @param \SS6\ShopBundle\Model\Product\Filter\ProductFilterData $productFilterData
	 * @param \SS6\ShopBundle\Model\Pricing\Group\PricingGroup $pricingGroup
	 * @return int
	 */
	private function getCountByFlagId(
		QueryBuilder $productsQueryBuilder,
		ProductFilterData $productFilterData,
		PricingGroup $pricingGroup
	) {
		$productFilterDataExceptFlags = clone $productFilterData;
		$productFilterDataExceptFlags->flags = [];

		$productsFilteredExceptFlagsQueryBuilder = clone $productsQueryBuilder;

		$this->productFilterRepository->applyFiltering(
			$productsFilteredExceptFlagsQueryBuilder,
			$productFilterDataExceptFlags,
			$pricingGroup
		);

		$productsFilteredExceptFlagsQueryBuilder
			->select('f.id, COUNT(p) AS cnt')
			->join('p.flags', 'f')
			->andWhere(
				$productsFilteredExceptFlagsQueryBuilder->expr()->notIn(
					'p.id',
					$this->em->createQueryBuilder()
						->select('_p.id')
						->from(Product::class, '_p')
						->join('_p.flags', '_f')
						->where('_f IN (:activeFlags)')
						->getDQL()
				)
			)
			->resetDQLPart('orderBy')
			->groupBy('f.id')
			->setParameter('activeFlags', $productFilterData->flags);

		$rows = $productsFilteredExceptFlagsQueryBuilder->getQuery()->execute();

		$countByFlagId = [];
		foreach ($rows as $row) {
			$countByFlagId[$row['id']] = $row['cnt'];
		}

		return $countByFlagId;
	}

	/**
	 * @param \Doctrine\ORM\QueryBuilder $productsQueryBuilder
	 * @param \SS6\ShopBundle\Model\Product\Filter\ProductFilterData $productFilterData
	 * @param \SS6\ShopBundle\Model\Pricing\Group\PricingGroup $pricingGroup
	 * @param \SS6\ShopBundle\Model\Category\Category $category
	 * @param int $domainId
	 * @param string $locale
	 * @return int
	 */
	private function getCountByParameterIdAndValueId(
		QueryBuilder $productsQueryBuilder,
		ProductFilterData $productFilterData,
		PricingGroup $pricingGroup,
		Category $category,
		$domainId,
		$locale
	) {
		$parameterFilterChoices = $this->parameterFilterChoiceRepository->getParameterFilterChoicesInCategory(
			$domainId,
			$pricingGroup,
			$locale,
			$category
		);

		$countByParameterIdAndValueId = [];

		foreach ($parameterFilterChoices as $parameterFilterChoice) {
			$currentParameter = $parameterFilterChoice->getParameter();

			$productFilterDataExceptCurrentParameter = clone $productFilterData;
			foreach ($productFilterDataExceptCurrentParameter->parameters as $index => $parameterFilterData) {
				if ($parameterFilterData->parameter->getId() === $currentParameter->getId()) {
					unset($productFilterDataExceptCurrentParameter->parameters[$index]);
				}
			}

			$productsFilteredExceptCurrentParameterQueryBuilder = clone $productsQueryBuilder;

			$this->productFilterRepository->applyFiltering(
				$productsFilteredExceptCurrentParameterQueryBuilder,
				$productFilterDataExceptCurrentParameter,
				$pricingGroup
			);

			$productsFilteredExceptCurrentParameterQueryBuilder
				->select('pv.id, COUNT(p) AS cnt')
				->join(ProductParameterValue::class, 'ppv', Join::WITH, 'ppv.product = p AND ppv.locale = :locale')
				->join('ppv.value', 'pv')
				->andWhere('ppv.parameter = :parameter')
				->resetDQLPart('orderBy')
				->groupBy('pv.id')
				->setParameter('locale', $locale)
				->setParameter('parameter', $currentParameter);

			$rows = $productsFilteredExceptCurrentParameterQueryBuilder->getQuery()->execute();

			$countByParameterIdAndValueId[$currentParameter->getId()] = [];
			foreach ($rows as $row) {
				$countByParameterIdAndValueId[$currentParameter->getId()][$row['id']] = $row['cnt'];
			}
		}

		return $countByParameterIdAndValueId;
	}

}
