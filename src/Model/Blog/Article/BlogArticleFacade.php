<?php

declare(strict_types=1);

namespace App\Model\Blog\Article;

use App\Model\Blog\BlogVisibilityRecalculationScheduler;
use App\Model\Blog\Category\BlogCategory;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Component\Image\ImageFacade;
use Shopsys\FrameworkBundle\Component\Paginator\PaginationResult;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade;
use Shopsys\FrameworkBundle\Model\Product\Product;

class BlogArticleFacade
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator
     */
    private $em;

    /**
     * @var \App\Model\Blog\Article\BlogArticleRepository
     */
    private $blogArticleRepository;

    /**
     * @var \App\Component\Router\FriendlyUrl\FriendlyUrlFacade
     */
    private $friendlyUrlFacade;

    /**
     * @var \App\Model\Blog\Article\BlogArticleFactory
     */
    private $blogArticleFactory;

    /**
     * @var \App\Model\Blog\Article\BlogArticleBlogCategoryDomainFactory
     */
    private $blogArticleBlogCategoryDomainFactory;

    /**
     * @var \App\Component\Image\ImageFacade
     */
    private $imageFacade;

    /**
     * @var \App\Model\Blog\BlogVisibilityRecalculationScheduler
     */
    private $blogVisibilityRecalculationScheduler;

    /**
     * @param \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
     * @param \App\Model\Blog\Article\BlogArticleRepository $blogArticleRepository
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
     * @param \App\Model\Blog\Article\BlogArticleFactory $blogArticleFactory
     * @param \App\Model\Blog\Article\BlogArticleBlogCategoryDomainFactory $blogArticleBlogCategoryDomainFactory
     * @param \App\Component\Image\ImageFacade $imageFacade
     * @param \App\Model\Blog\BlogVisibilityRecalculationScheduler $blogVisibilityRecalculationScheduler
     */
    public function __construct(
        EntityManagerInterface $em,
        BlogArticleRepository $blogArticleRepository,
        FriendlyUrlFacade $friendlyUrlFacade,
        BlogArticleFactory $blogArticleFactory,
        BlogArticleBlogCategoryDomainFactory $blogArticleBlogCategoryDomainFactory,
        ImageFacade $imageFacade,
        BlogVisibilityRecalculationScheduler $blogVisibilityRecalculationScheduler
    ) {
        $this->em = $em;
        $this->blogArticleRepository = $blogArticleRepository;
        $this->friendlyUrlFacade = $friendlyUrlFacade;
        $this->blogArticleFactory = $blogArticleFactory;
        $this->blogArticleBlogCategoryDomainFactory = $blogArticleBlogCategoryDomainFactory;
        $this->imageFacade = $imageFacade;
        $this->blogVisibilityRecalculationScheduler = $blogVisibilityRecalculationScheduler;
    }

    /**
     * @param int $blogArticleId
     * @return \App\Model\Blog\Article\BlogArticle|null
     */
    public function findById(int $blogArticleId): ?BlogArticle
    {
        return $this->blogArticleRepository->findById($blogArticleId);
    }

    /**
     * @param int $blogArticleId
     * @return \App\Model\Blog\Article\BlogArticle
     */
    public function getById(int $blogArticleId): BlogArticle
    {
        return $this->blogArticleRepository->getById($blogArticleId);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @param int $blogArticleId
     * @return \App\Model\Blog\Article\BlogArticle
     */
    public function getVisibleOnDomainById(DomainConfig $domainConfig, int $blogArticleId): BlogArticle
    {
        return $this->blogArticleRepository->getVisibleOnDomainById($domainConfig, $blogArticleId);
    }

    /**
     * @param int $domainId
     * @return int
     */
    public function getAllArticlesCountByDomainId(int $domainId): int
    {
        return $this->blogArticleRepository->getAllBlogArticlesCountByDomainId($domainId);
    }

    /**
     * @param \App\Model\Blog\Article\BlogArticleData $blogArticleData
     * @return \App\Model\Blog\Article\BlogArticle
     */
    public function create(BlogArticleData $blogArticleData): BlogArticle
    {
        $blogArticle = $this->blogArticleFactory->create($blogArticleData);

        $this->em->persist($blogArticle);
        $this->em->flush($blogArticle);

        $blogArticle->setCategories($this->blogArticleBlogCategoryDomainFactory, $blogArticleData->blogCategoriesByDomainId);
        $blogArticle->createDomains($blogArticleData);

        $this->friendlyUrlFacade->createFriendlyUrls('front_blogarticle_detail', $blogArticle->getId(), $blogArticle->getNames());
        $this->imageFacade->manageImages($blogArticle, $blogArticleData->images, null);
        $this->blogVisibilityRecalculationScheduler->scheduleRecalculation();

        $this->em->flush();

        return $blogArticle;
    }

    /**
     * @param int $blogArticleId
     * @param \App\Model\Blog\Article\BlogArticleData $blogArticleData
     * @return \App\Model\Blog\Article\BlogArticle
     */
    public function edit(int $blogArticleId, BlogArticleData $blogArticleData): BlogArticle
    {
        $blogArticle = $this->blogArticleRepository->getById($blogArticleId);
        $blogArticle->edit($blogArticleData, $this->blogArticleBlogCategoryDomainFactory);

        $this->em->flush();

        $this->friendlyUrlFacade->saveUrlListFormData('front_blogarticle_detail', $blogArticle->getId(), $blogArticleData->urls);
        $this->friendlyUrlFacade->createFriendlyUrls('front_blogarticle_detail', $blogArticle->getId(), $blogArticle->getNames());

        $this->imageFacade->manageImages($blogArticle, $blogArticleData->images, null);

        $this->blogVisibilityRecalculationScheduler->scheduleRecalculation();

        $this->em->flush();

        return $blogArticle;
    }

    /**
     * @param int $blogArticleId
     */
    public function delete(int $blogArticleId): void
    {
        $blogArticle = $this->blogArticleRepository->getById($blogArticleId);

        $this->em->remove($blogArticle);
        $this->blogVisibilityRecalculationScheduler->scheduleRecalculation();
        $this->em->flush();
    }

    /**
     * @param int $domainId
     * @return \App\Model\Blog\Article\BlogArticle[]
     */
    public function getAllByDomainId(int $domainId): array
    {
        return $this->blogArticleRepository->getAllByDomainId($domainId);
    }

    /**
     * @param \App\Model\Blog\Category\BlogCategory $blogCategory
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
        return $this->blogArticleRepository->getPaginationResultForListableInBlogCategory($blogCategory, $domainId, $locale, $page, $limit);
    }

    /**
     * @param int $domainId
     * @param string $locale
     * @param int $limit
     * @return \App\Model\Blog\Article\BlogArticle[]
     */
    public function getHomepageBlogArticlesByDomainId(int $domainId, string $locale, int $limit): array
    {
        return $this->blogArticleRepository->getHomepageBlogArticlesByDomainId($domainId, $locale, $limit);
    }

    /**
     * @param \App\Model\Blog\Article\BlogArticle $blogArticle
     * @param int $domainId
     * @return \App\Model\Blog\Category\BlogCategory
     */
    public function findBlogArticleMainCategoryOnDomain(BlogArticle $blogArticle, int $domainId): ?BlogCategory
    {
        return $this->blogArticleRepository->findBlogArticleMainCategoryOnDomain($blogArticle, $domainId);
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param int $domainId
     * @param string $locale
     * @param int $limit
     * @return \App\Model\Blog\Article\BlogArticle[]
     */
    public function getVisibleByProduct(Product $product, int $domainId, string $locale, int $limit): array
    {
        return $this->blogArticleRepository->getVisibleByProduct($product, $domainId, $locale, $limit);
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param string $locale
     * @return \App\Model\Blog\Article\BlogArticle[]
     */
    public function getByProduct(Product $product, string $locale): array
    {
        return $this->blogArticleRepository->getByProduct($product, $locale);
    }

    /**
     * @param int $domainId
     * @param string $locale
     * @return string[]
     */
    public function getAllBlogArticlesNamesIndexedByIdByDomainId(int $domainId, string $locale): array
    {
        return $this->blogArticleRepository->getAllBlogArticlesNamesIndexedByIdByDomainId($domainId, $locale);
    }

    /**
     * @param \App\Model\Blog\Article\BlogArticle $article
     * @return int[]
     */
    public function getProductIds(BlogArticle $article): array
    {
        return $this->blogArticleRepository->getProductIds($article->getId());
    }
}
