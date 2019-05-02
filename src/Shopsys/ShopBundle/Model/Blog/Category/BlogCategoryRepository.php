<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Blog\Category;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Component\EntityExtension\QueryBuilder;
use Shopsys\ShopBundle\Model\Blog\Article\BlogArticle;
use Shopsys\ShopBundle\Model\Blog\Article\BlogArticleBlogCategoryDomain;

class BlogCategoryRepository extends NestedTreeRepository
{
    public const MOVE_DOWN_TO_BOTTOM = true;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;

        $classMetadata = $this->em->getClassMetadata(BlogCategory::class);
        parent::__construct($this->em, $classMetadata);
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    private function getBlogCategoryRepository(): EntityRepository
    {
        return $this->em->getRepository(BlogCategory::class);
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\EntityExtension\QueryBuilder
     */
    private function getAllQueryBuilder(): QueryBuilder
    {
        return $this->getBlogCategoryRepository()
            ->createQueryBuilder('bc')
            ->where('bc.parent IS NOT NULL')
            ->orderBy('bc.lft');
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory[]
     */
    public function getAll(): array
    {
        return $this->getAllQueryBuilder()
            ->getQuery()
            ->getResult();
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory[] $selectedBlogCategories
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory[]
     */
    public function getAllBlogCategoriesOfCollapsedTree(array $selectedBlogCategories): array
    {
        $openedParentsQueryBuilder = $this->getBlogCategoryRepository()
            ->createQueryBuilder('bc')
            ->select('bc.id')
            ->where('bc.parent IS NULL');

        foreach ($selectedBlogCategories as $selectedBlogCategory) {
            $where = sprintf('bc.lft < %d AND bc.rgt > %d', $selectedBlogCategory->getLft(), $selectedBlogCategory->getRgt());
            $openedParentsQueryBuilder->orWhere($where);
        }

        $openedParentIds = array_column($openedParentsQueryBuilder->getQuery()->getScalarResult(), 'id');

        return $this->getAllQueryBuilder()
            ->select('bc, bcd, bct')
            ->join('bc.domains', 'bcd')
            ->join('bc.translations', 'bct')
            ->where('bc.parent IN (:openedParentIds)')
            ->setParameter('openedParentIds', $openedParentIds)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory
     */
    public function getRootBlogCategory(): BlogCategory
    {
        $rootCategory = $this->getBlogCategoryRepository()->findOneBy(['parent' => null]);

        return $rootCategory;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory $blogCategoryBranch
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory[]
     */
    public function getTranslatedAllWithoutBranch(BlogCategory $blogCategoryBranch, DomainConfig $domainConfig): array
    {
        $queryBuilder = $this->getAllQueryBuilder();
        $this->addTranslation($queryBuilder, $domainConfig->getLocale());

        return $queryBuilder->andWhere('bc.lft < :branchLft OR bc.rgt > :branchRgt')
            ->setParameter('branchLft', $blogCategoryBranch->getLft())
            ->setParameter('branchRgt', $blogCategoryBranch->getRgt())
            ->getQuery()
            ->execute();
    }

    /**
     * @param int $blogCategoryId
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory|null
     */
    public function findById(int $blogCategoryId): ?BlogCategory
    {
        /** @var \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory $blogCategory */
        $blogCategory = $this->getBlogCategoryRepository()->find($blogCategoryId);

        if ($blogCategory !== null && $blogCategory->getParent() === null) {
            return null;
        }

        return $blogCategory;
    }

    /**
     * @param int $blogCategoryId
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory
     */
    public function getById(int $blogCategoryId): BlogCategory
    {
        $blogCategory = $this->findById($blogCategoryId);

        if ($blogCategory === null) {
            $message = 'BlogCategory with ID ' . $blogCategoryId . ' not found.';
            throw new \Shopsys\ShopBundle\Model\Blog\Category\Exception\BlogCategoryNotFoundException($message);
        }

        return $blogCategory;
    }

    /**
     * @param string $locale
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory[]
     */
    public function getPreOrderTreeTraversalForAllBlogCategories(string $locale): array
    {
        $queryBuilder = $this->getPreOrderTreeTraversalForAllBlogCategoriesQueryBuilder($locale);

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @param int $domainId
     * @param string $locale
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory[]
     */
    public function getPreOrderTreeTraversalForVisibleBlogCategoriesOnDomain(int $domainId, string $locale): array
    {
        $queryBuilder = $this->getPreOrderTreeTraversalForAllBlogCategoriesQueryBuilder($locale);

        $queryBuilder->join(BlogCategoryDomain::class, 'bcd', Join::WITH, 'bcd.blogCategory = bc')
            ->andWhere('bcd.visible = TRUE')
            ->andWhere('bcd.domainId = :domainId')
            ->setParameter('domainId', $domainId);

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @param string $locale
     * @return \Shopsys\FrameworkBundle\Component\EntityExtension\QueryBuilder
     */
    private function getPreOrderTreeTraversalForAllBlogCategoriesQueryBuilder(string $locale): QueryBuilder
    {
        $queryBuilder = $this->getAllQueryBuilder();
        $this->addTranslation($queryBuilder, $locale);

        $queryBuilder
            ->andWhere('bc.level >= 1')
            ->orderBy('bc.lft');

        return $queryBuilder;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\EntityExtension\QueryBuilder $blogCategoriesQueryBuilder
     * @param string $locale
     */
    protected function addTranslation(QueryBuilder $blogCategoriesQueryBuilder, string $locale): void
    {
        $blogCategoriesQueryBuilder
            ->addSelect('bct')
            ->join('bc.translations', 'bct', Join::WITH, 'bct.locale = :locale')
            ->setParameter('locale', $locale);
    }

    /**
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Component\EntityExtension\QueryBuilder
     */
    public function getAllVisibleByDomainIdQueryBuilder(int $domainId): QueryBuilder
    {
        $queryBuilder = $this->getAllQueryBuilder()
            ->join(BlogCategoryDomain::class, 'bcd', Join::WITH, 'bcd.blogCategory = bc.id')
            ->andWhere('bcd.domainId = :domainId')
            ->andWhere('bcd.visible = TRUE');

        $queryBuilder->setParameter('domainId', $domainId);

        return $queryBuilder;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory $blogCategory
     * @param int $domainId
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory[]
     */
    public function getAllVisibleChildrenByBlogCategoryAndDomainId(BlogCategory $blogCategory, int $domainId): array
    {
        $queryBuilder = $this->getAllVisibleByDomainIdQueryBuilder($domainId)
            ->andWhere('bc.parent = :blogCategory')
            ->setParameter('blogCategory', $blogCategory);

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticle $blogArticle
     * @param int $domainId
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory|null
     */
    public function findBlogArticleMainBlogCategoryOnDomain(BlogArticle $blogArticle, int $domainId): ?BlogCategory
    {
        $qb = $this->getAllVisibleByDomainIdQueryBuilder($domainId)
            ->join(
                BlogArticleBlogCategoryDomain::class,
                'babcd',
                Join::WITH,
                'babcd.blogArticle = :blogArticle
                    AND babcd.blogCategory = bc
                    AND babcd.domainId = :domainId'
            )
            ->orderBy('bc.level DESC, bc.lft')
            ->setMaxResults(1);

        $qb->setParameters([
            'domainId' => $domainId,
            'blogArticle' => $blogArticle,
        ]);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticle $product
     * @param int $domainId
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory
     */
    public function getBlogArticleMainBlogCategoryOnDomain(BlogArticle $product, int $domainId): BlogCategory
    {
        $blogArticleMainBlogCategory = $this->findBlogArticleMainBlogCategoryOnDomain($product, $domainId);

        if ($blogArticleMainBlogCategory === null) {
            throw new \Shopsys\ShopBundle\Model\Blog\Category\Exception\BlogCategoryNotFoundException();
        }

        return $blogArticleMainBlogCategory;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory $blogCategory
     * @param int $domainId
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory[]
     */
    public function getVisibleBlogCategoriesInPathFromRootOnDomain(BlogCategory $blogCategory, int $domainId): array
    {
        $queryBuilder = $this->getAllVisibleByDomainIdQueryBuilder($domainId)
            ->andWhere('bc.lft <= :lft')->setParameter('lft', $blogCategory->getLft())
            ->andWhere('bc.rgt >= :rgt')->setParameter('rgt', $blogCategory->getRgt())
            ->orderBy('bc.lft');

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param  string $locale
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory[]
     */
    public function getAllByLocale(string $locale): array
    {
        $queryBuilder = $this->getAllQueryBuilder();
        $this->addTranslation($queryBuilder, $locale);

        return $queryBuilder->getQuery()
            ->getResult();
    }

    /**
     * @param array $blogCategoryIds
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory[]
     */
    public function getByIds(array $blogCategoryIds)
    {
        return $this->getBlogCategoryRepository()->findBy(['id' => $blogCategoryIds]);
    }
}
