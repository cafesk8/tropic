<?php

namespace SS6\ShopBundle\Model\Category;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use SS6\ShopBundle\Component\Paginator\QueryPaginator;
use SS6\ShopBundle\Component\String\DatabaseSearching;
use SS6\ShopBundle\Model\Category\Category;
use SS6\ShopBundle\Model\Domain\Config\DomainConfig;
use SS6\ShopBundle\Model\Product\Product;

class CategoryRepository extends NestedTreeRepository {

	const MOVE_DOWN_TO_BOTTOM = true;

	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	private $em;

	/**
	 * @param \Doctrine\ORM\EntityManager $em
	 */
	public function __construct(EntityManager $em) {
		$this->em = $em;
		$classMetadata = $this->em->getClassMetadata(Category::class);
		parent::__construct($this->em, $classMetadata);
	}

	/**
	 * @return \Doctrine\ORM\EntityRepository
	 */
	private function getCategoryRepository() {
		return $this->em->getRepository(Category::class);
	}

	/**
	 * @return \Doctrine\ORM\EntityRepository
	 */
	private function getCategoryDomainRepository() {
		return $this->em->getRepository(CategoryDomain::class);
	}

	/**
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	private function getAllQueryBuilder() {
		return $this->getCategoryRepository()
			->createQueryBuilder('c')
			->where('c.parent IS NOT NULL')
			->orderBy('c.lft');
	}

	/**
	 * @return \SS6\ShopBundle\Model\Category\Category[]
	 */
	public function getAll() {
		return $this->getAllQueryBuilder()
			->getQuery()
			->getResult();
	}

	/**
	 * @return \SS6\ShopBundle\Model\Category\Category
	 */
	public function getRootCategory() {
		return $this->getCategoryRepository()->findOneBy(['parent' => null]);
	}

	/**
	 * @param \SS6\ShopBundle\Model\Category\Category $categoryBranch
	 * @return \SS6\ShopBundle\Model\Category\Category[]
	 */
	public function getAllWithoutBranch(Category $categoryBranch) {
		return $this->getAllQueryBuilder()
			->andWhere('c.lft < :branchLft OR c.rgt > :branchRgt')
			->setParameter('branchLft', $categoryBranch->getLft())
			->setParameter('branchRgt', $categoryBranch->getRgt())
			->getQuery()
			->execute();
	}

	/**
	 * @param int $categoryId
	 * @return \SS6\ShopBundle\Model\Category\Category|null
	 */
	public function findById($categoryId) {
		return $this->getCategoryRepository()->find($categoryId);
	}

	/**
	 * @param int $categoryId
	 * @return \SS6\ShopBundle\Model\Category\Category
	 */
	public function getById($categoryId) {
		$category = $this->findById($categoryId);

		if ($category === null) {
			$message = 'Category with ID ' . $categoryId . ' not found.';
			throw new \SS6\ShopBundle\Model\Category\Exception\CategoryNotFoundException($message);
		}

		return $category;
	}

	/**
	 * @param string $locale
	 * @return \SS6\ShopBundle\Model\Category\Category[]
	 */
	public function getPreOrderTreeTraversalForAllCategories($locale) {
		$queryBuilder = $this->getAllQueryBuilder();
		$this->addTranslation($queryBuilder, $locale);

		$queryBuilder
			->andWhere('c.level >= 1')
			->orderBy('c.lft');

		return $queryBuilder->getQuery()->execute();
	}

	/**
	 * @param int $domainId
	 * @param string $locale
	 * @return \SS6\ShopBundle\Model\Category\Category[]
	 */
	public function getPreOrderTreeTraversalForVisibleCategoriesByDomain($domainId, $locale) {
		$queryBuilder = $this->getAllQueryBuilder();
		$this->addTranslation($queryBuilder, $locale);

		$queryBuilder
			->join(CategoryDomain::class, 'cd', Join::WITH, 'cd.category = c')
			->andWhere('c.level >= 1')
			->andWhere('cd.domainId = :domainId')
			->andWhere('cd.visible = TRUE')
			->orderBy('c.lft');

		$queryBuilder->setParameter('domainId', $domainId);

		return $queryBuilder->getQuery()->execute();
	}

	/**
	 * @param string $locale
	 */
	private function addTranslation(QueryBuilder $categoriesQueryBuilder, $locale) {
		$categoriesQueryBuilder
			->join('c.translations', 'ct', Join::WITH, 'ct.locale = :locale')
			->setParameter('locale', $locale);
	}

	/**
	 * @param \SS6\ShopBundle\Model\Category\Category $category
	 * @return \SS6\ShopBundle\Model\Category\CategoryDomain[]
	 */
	public function getCategoryDomainsByCategory(Category $category) {
		return $this->getCategoryDomainRepository()->findBy([
			'category' => $category,
		]);
	}

	/**
	 * @param string|null $searchText
	 * @param int $domainId
	 * @param string $locale
	 * @param int $page
	 * @param int $limit
	 * @return \SS6\ShopBundle\Component\Paginator\PaginationResult
	 */
	public function getPaginationResultForSearchVisible(
		$searchText,
		$domainId,
		$locale,
		$page,
		$limit
	) {
		$queryBuilder = $this->getVisibleByDomainIdAndSearchTextQueryBuilder($domainId, $locale, $searchText);
		$queryBuilder->orderBy('ct.name');

		$queryPaginator = new QueryPaginator($queryBuilder);

		return $queryPaginator->getResult($page, $limit);
	}

	/**
	 * @param int $domainId
	 * @param string $locale
	 * @param string|null $searchText
	 * @return \SS6\ShopBundle\Model\Category\Category[]
	 */
	public function getVisibleByDomainIdAndSearchText($domainId, $locale, $searchText) {
		$queryBuilder = $this->getVisibleByDomainIdAndSearchTextQueryBuilder(
			$domainId,
			$locale,
			$searchText
		);

		return $queryBuilder->getQuery()->execute();
	}

	/**
	 * @param int $domainId
	 * @param string $locale
	 * @param string|null $searchText
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	private function getVisibleByDomainIdAndSearchTextQueryBuilder(
		$domainId,
		$locale,
		$searchText
	) {
		$queryBuilder = $this->getAllVisibleByDomainIdQueryBuilder($domainId);
		$this->addTranslation($queryBuilder, $locale);
		$this->filterBySearchText($queryBuilder, $searchText);

		return $queryBuilder;
	}

	/**
	 * @param int $domainId
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	private function getAllVisibleByDomainIdQueryBuilder($domainId) {
		$queryBuilder = $this->getAllQueryBuilder()
			->join(CategoryDomain::class, 'cd', Join::WITH, 'cd.category = c.id')
			->andWhere('cd.domainId = :domainId')
			->andWhere('cd.visible = TRUE');

		$queryBuilder->setParameter('domainId', $domainId);

		return $queryBuilder;
	}

	/**
	 * @param \Doctrine\ORM\QueryBuilder $queryBuilder
	 * @param string|null $searchText
	 */
	private function filterBySearchText(QueryBuilder $queryBuilder, $searchText) {
		$queryBuilder->andWhere(
			'NORMALIZE(ct.name) LIKE NORMALIZE(:searchText)'
		);
		$queryBuilder->setParameter('searchText', '%' . DatabaseSearching::getLikeSearchString($searchText) . '%');
	}

	/*
	 * @param \SS6\ShopBundle\Model\Product\Product $product
	 * @param \SS6\ShopBundle\Model\Domain\Config\DomainConfig $domainConfig
	 * @return \SS6\ShopBundle\Model\Category\Category|null
	 */
	public function findProductMainCategoryOnDomain(Product $product, DomainConfig $domainConfig) {
		$qb = $this->getAllVisibleByDomainIdQueryBuilder($domainConfig->getId())
			->join('c.products', 'cp')
			->andWhere('cp = :product')
			->orderBy('c.level DESC, c.lft')
			->setMaxResults(1);

		$qb->setParameters([
			'domainId' => $domainConfig->getId(),
			'product' => $product,
		]);

		return $qb->getQuery()->getOneOrNullResult();
	}

	/**
	 * @param \SS6\ShopBundle\Model\Category\Category $category
	 * @param int $domainId
	 * @return \SS6\ShopBundle\Model\Category\Category[]
	 */
	public function getVisibleCategoryPathFromRootOnDomain(Category $category, $domainId) {
		$qb = $this->getAllVisibleByDomainIdQueryBuilder($domainId)
			->andWhere('c.lft <= :lft')->setParameter('lft', $category->getLft())
			->andWhere('c.rgt >= :rgt')->setParameter('rgt', $category->getRgt())
			->orderBy('c.lft');

		return $qb->getQuery()->getResult();
	}

}
