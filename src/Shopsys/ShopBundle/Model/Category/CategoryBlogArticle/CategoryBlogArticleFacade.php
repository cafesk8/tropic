<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Category\CategoryBlogArticle;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Model\Category\Category;

class CategoryBlogArticleFacade
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @var \Shopsys\ShopBundle\Model\Category\CategoryBlogArticle\CategoryBlogArticleRepository
     */
    private $categoryBlogArticleRepository;

    /**
     * @var \Shopsys\ShopBundle\Model\Category\CategoryBlogArticle\CategoryBlogArticleFactory
     */
    private $categoryBlogArticleFactory;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\ShopBundle\Model\Category\CategoryBlogArticle\CategoryBlogArticleRepository $categoryBlogArticleRepository
     * @param \Shopsys\ShopBundle\Model\Category\CategoryBlogArticle\CategoryBlogArticleFactory $categoryBlogArticleFactory
     */
    public function __construct(EntityManagerInterface $em, CategoryBlogArticleRepository $categoryBlogArticleRepository, CategoryBlogArticleFactory $categoryBlogArticleFactory)
    {
        $this->em = $em;
        $this->categoryBlogArticleRepository = $categoryBlogArticleRepository;
        $this->categoryBlogArticleFactory = $categoryBlogArticleFactory;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Category\Category $category
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticle[] $blogArticles
     */
    public function saveBlogArticlesToCategory(Category $category, array $blogArticles): void
    {
        $oldBlogArticles = $this->categoryBlogArticleRepository->getAllByCategory($category);
        foreach ($oldBlogArticles as $oldTopCategory) {
            $this->em->remove($oldTopCategory);
        }
        $this->em->flush($oldBlogArticles);

        $blogArticlesForCategory = [];
        $position = 1;
        foreach ($blogArticles as $blogArticle) {
            $blogArticleToSave = $this->categoryBlogArticleFactory->create($category, $blogArticle, $position++);
            $this->em->persist($blogArticleToSave);
            $blogArticlesForCategory[] = $blogArticleToSave;
        }
        $this->em->flush($blogArticlesForCategory);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Category\Category $category
     * @return \Shopsys\ShopBundle\Model\Blog\Article\BlogArticle[]
     */
    public function getAllBlogArticlesByCategory(Category $category): array
    {
        $categoriesBlogArticles = $this->categoryBlogArticleRepository->getAllByCategory($category);

        return $this->getBlogArticlesFromCategoriesBlogArticles($categoriesBlogArticles);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Category\Category $category
     * @param int $domainId
     * @param int $limit
     * @return \Shopsys\ShopBundle\Model\Blog\Article\BlogArticle[]
     */
    public function getVisibleBlogArticlesByCategoryAndDomainId(Category $category, int $domainId, int $limit): array
    {
        $categoriesBlogArticles = $this->categoryBlogArticleRepository->getByCategoryAndDomainId($category, $domainId, $limit);

        return $this->getBlogArticlesFromCategoriesBlogArticles($categoriesBlogArticles, true, $limit);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Category\CategoryBlogArticle\CategoryBlogArticle[] $categoriesBlogArticles
     * @param bool $getOnlyVisibleArticles
     * @param int|null $limit
     * @return \Shopsys\ShopBundle\Model\Blog\Article\BlogArticle[]
     */
    private function getBlogArticlesFromCategoriesBlogArticles(array $categoriesBlogArticles, bool $getOnlyVisibleArticles = false, ?int $limit = null): array
    {
        $blogArticles = [];

        $i = 0;
        foreach ($categoriesBlogArticles as $categoryBlogArticle) {
            $blogArticle = $categoryBlogArticle->getBlogArticle();

            if ($getOnlyVisibleArticles && ($blogArticle->isHidden() === true || $blogArticle->getPublishDate() >= new DateTime())) {
                continue;
            }

            if ($limit !== null && $i === $limit) {
                break;
            }

            $blogArticles[] = $blogArticle;

            $i++;
        }

        return $blogArticles;
    }
}
