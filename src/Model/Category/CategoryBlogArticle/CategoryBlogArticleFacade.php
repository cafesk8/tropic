<?php

declare(strict_types=1);

namespace App\Model\Category\CategoryBlogArticle;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Model\Category\Category;

class CategoryBlogArticleFacade
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator
     */
    private $em;

    /**
     * @var \App\Model\Category\CategoryBlogArticle\CategoryBlogArticleRepository
     */
    private $categoryBlogArticleRepository;

    /**
     * @var \App\Model\Category\CategoryBlogArticle\CategoryBlogArticleFactory
     */
    private $categoryBlogArticleFactory;

    /**
     * @param \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
     * @param \App\Model\Category\CategoryBlogArticle\CategoryBlogArticleRepository $categoryBlogArticleRepository
     * @param \App\Model\Category\CategoryBlogArticle\CategoryBlogArticleFactory $categoryBlogArticleFactory
     */
    public function __construct(EntityManagerInterface $em, CategoryBlogArticleRepository $categoryBlogArticleRepository, CategoryBlogArticleFactory $categoryBlogArticleFactory)
    {
        $this->em = $em;
        $this->categoryBlogArticleRepository = $categoryBlogArticleRepository;
        $this->categoryBlogArticleFactory = $categoryBlogArticleFactory;
    }

    /**
     * @param \App\Model\Category\Category $category
     * @param \App\Model\Blog\Article\BlogArticle[] $blogArticles
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
     * @param \App\Model\Category\Category $category
     * @return \App\Model\Blog\Article\BlogArticle[]
     */
    public function getAllBlogArticlesByCategory(Category $category): array
    {
        $categoriesBlogArticles = $this->categoryBlogArticleRepository->getAllByCategory($category);

        return $this->getBlogArticlesFromCategoriesBlogArticles($categoriesBlogArticles);
    }

    /**
     * @param \App\Model\Category\Category $category
     * @param int $domainId
     * @param int $limit
     * @return \App\Model\Blog\Article\BlogArticle[]
     */
    public function getVisibleBlogArticlesByCategoryAndDomainId(Category $category, int $domainId, int $limit): array
    {
        $categoriesBlogArticles = $this->categoryBlogArticleRepository->getVisibleByCategoryAndDomainId($category, $domainId);

        return $this->getBlogArticlesFromCategoriesBlogArticles($categoriesBlogArticles, true, $limit);
    }

    /**
     * @param \App\Model\Category\CategoryBlogArticle\CategoryBlogArticle[] $categoriesBlogArticles
     * @param bool $getOnlyVisibleArticles
     * @param int|null $limit
     * @return \App\Model\Blog\Article\BlogArticle[]
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
