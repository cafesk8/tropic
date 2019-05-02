<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Blog\Article;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Component\EntityExtension\QueryBuilder;
use Shopsys\FrameworkBundle\Component\Paginator\PaginationResult;
use Shopsys\FrameworkBundle\Component\Paginator\QueryPaginator;
use Shopsys\ShopBundle\Model\Blog\Category\BlogCategory;

class BlogArticleRepository
{
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
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    private function getBlogArticleRepository(): EntityRepository
    {
        return $this->em->getRepository(BlogArticle::class);
    }

    /**
     * @param int $blogArticleId
     * @return \Shopsys\ShopBundle\Model\Blog\Article\BlogArticle|null
     */
    public function findById(int $blogArticleId): ?BlogArticle
    {
        return $this->getBlogArticleRepository()->find($blogArticleId);
    }

    /**
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Component\EntityExtension\QueryBuilder
     */
    public function getBlogArticlesByDomainIdQueryBuilder(int $domainId): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select('ba, babcd')
            ->from(BlogArticle::class, 'ba')
            ->join('ba.blogArticleBlogCategoryDomains', 'babcd')
            ->where('babcd.domainId = :domainId')
            ->setParameter('domainId', $domainId);
    }

    /**
     * @param int $domainId
     * @param string $locale
     * @return \Shopsys\FrameworkBundle\Component\EntityExtension\QueryBuilder
     */
    public function getBlogArticlesByDomainIdAndLocaleQueryBuilder(int $domainId, string $locale): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select('ba, bat, babcd')
            ->from(BlogArticle::class, 'ba')
            ->join('ba.translations', 'bat', Join::WITH, 'bat.locale = :locale')
            ->join('ba.blogArticleBlogCategoryDomains', 'babcd', Join::WITH, 'babcd.domainId = :domainId')
            ->setParameter('domainId', $domainId)
            ->setParameter('locale', $locale)
            ->orderBy('ba.createdAt', 'DESC');
    }

    /**
     * @param string $locale
     * @return \Shopsys\FrameworkBundle\Component\EntityExtension\QueryBuilder
     */
    public function getAllBlogArticlesByLocaleQueryBuilder(string $locale): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select('ba, bat')
            ->from(BlogArticle::class, 'ba')
            ->join('ba.translations', 'bat', Join::WITH, 'bat.locale = :locale')
            ->setParameter('locale', $locale)
            ->orderBy('ba.createdAt', 'DESC');
    }

    /**
     * @param int $domainId
     * @param string $locale
     * @return \Shopsys\FrameworkBundle\Component\EntityExtension\QueryBuilder
     */
    public function getVisibleBlogArticlesByDomainIdAndLocaleQueryBuilder(int $domainId, string $locale): QueryBuilder
    {
        return $this->getBlogArticlesByDomainIdAndLocaleQueryBuilder($domainId, $locale)
            ->join('ba.domains', 'bad', Join::WITH, 'bad.domainId = :domainId')
            ->andWhere('bad.visible = true');
    }

    /**
     * @param int $domainId
     * @return int
     */
    public function getAllBlogArticlesCountByDomainId(int $domainId): int
    {
        return (int)($this->getBlogArticlesByDomainIdQueryBuilder($domainId)
            ->select('COUNT(ba)')
            ->getQuery()->getSingleScalarResult());
    }

    /**
     * @param int $blogArticleId
     * @return \Shopsys\ShopBundle\Model\Blog\Article\BlogArticle
     */
    public function getById(int $blogArticleId): BlogArticle
    {
        $blogArticle = $this->getBlogArticleRepository()->find($blogArticleId);

        if ($blogArticle === null) {
            $message = 'Blog article with ID ' . $blogArticleId . ' not found';
            throw new \Shopsys\ShopBundle\Model\Blog\Article\Exception\BlogArticleNotFoundException($message);
        }

        return $blogArticle;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @param int $blogArticleId
     * @return \Shopsys\ShopBundle\Model\Blog\Article\BlogArticle
     */
    public function getVisibleOnDomainById(DomainConfig $domainConfig, int $blogArticleId): BlogArticle
    {
        $blogArticle = $this->getVisibleBlogArticlesByDomainIdAndLocaleQueryBuilder($domainConfig->getId(), $domainConfig->getLocale())
            ->andWhere('ba.id = :blogArticleId')
            ->setParameter('blogArticleId', $blogArticleId)
            ->getQuery()->getOneOrNullResult();

        if ($blogArticle === null) {
            $message = 'Article with ID ' . $blogArticleId . ' not found';
            throw new \Shopsys\ShopBundle\Model\Blog\Article\Exception\BlogArticleNotFoundException($message);
        }
        return $blogArticle;
    }

    /**
     * @param int $domainId
     * @return \Shopsys\ShopBundle\Model\Blog\Article\BlogArticle[]
     */
    public function getAllByDomainId(int $domainId): array
    {
        return $this->getBlogArticlesByDomainIdQueryBuilder($domainId)
            ->orderBy('ba.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory $blogCategory
     * @param int $domainId
     * @param string $locale
     * @param int $page
     * @param int $limit
     * @return \Shopsys\FrameworkBundle\Component\Paginator\PaginationResult
     */
    public function getPaginationResultForListableInBlogCategory(
        BlogCategory $blogCategory,
        int $domainId,
        string $locale,
        int $page,
        int $limit
    ): PaginationResult {
        $queryBuilder = $this->getVisibleBlogArticlesByDomainIdAndLocaleQueryBuilder($domainId, $locale);
        $queryBuilder->andWhere('babcd.blogCategory = :blogCategory');
        $queryBuilder->setParameter('blogCategory', $blogCategory);

        $queryPaginator = new QueryPaginator($queryBuilder);

        return $queryPaginator->getResult($page, $limit);
    }
}
