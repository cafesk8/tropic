<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Blog\Article;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Component\Image\ImageFacade;
use Shopsys\FrameworkBundle\Component\Paginator\PaginationResult;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\ShopBundle\Model\Blog\BlogVisibilityRecalculationScheduler;
use Shopsys\ShopBundle\Model\Blog\Category\BlogCategory;

class BlogArticleFacade
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleRepository
     */
    private $blogArticleRepository;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade
     */
    private $friendlyUrlFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleFactory
     */
    private $blogArticleFactory;

    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleBlogCategoryDomainFactory
     */
    private $blogArticleBlogCategoryDomainFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Image\ImageFacade
     */
    private $imageFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Blog\BlogVisibilityRecalculationScheduler
     */
    private $blogVisibilityRecalculationScheduler;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleRepository $blogArticleRepository
     * @param \Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleFactory $blogArticleFactory
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleBlogCategoryDomainFactory $blogArticleBlogCategoryDomainFactory
     * @param \Shopsys\FrameworkBundle\Component\Image\ImageFacade $imageFacade
     * @param \Shopsys\ShopBundle\Model\Blog\BlogVisibilityRecalculationScheduler $blogVisibilityRecalculationScheduler
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
     * @return \Shopsys\ShopBundle\Model\Blog\Article\BlogArticle|null
     */
    public function findById(int $blogArticleId): ?BlogArticle
    {
        return $this->blogArticleRepository->findById($blogArticleId);
    }

    /**
     * @param int $blogArticleId
     * @return \Shopsys\ShopBundle\Model\Blog\Article\BlogArticle
     */
    public function getById(int $blogArticleId): BlogArticle
    {
        return $this->blogArticleRepository->getById($blogArticleId);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @param int $blogArticleId
     * @return \Shopsys\ShopBundle\Model\Blog\Article\BlogArticle
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
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleData $blogArticleData
     * @return \Shopsys\ShopBundle\Model\Blog\Article\BlogArticle
     */
    public function create(BlogArticleData $blogArticleData): BlogArticle
    {
        $blogArticle = $this->blogArticleFactory->create($blogArticleData);

        $this->em->persist($blogArticle);
        $this->em->flush($blogArticle);

        $blogArticle->setCategories($this->blogArticleBlogCategoryDomainFactory, $blogArticleData->blogCategoriesByDomainId);
        $blogArticle->createDomains($blogArticleData);

        $this->friendlyUrlFacade->createFriendlyUrls('front_blogarticle_detail', $blogArticle->getId(), $blogArticle->getNames());
        $this->imageFacade->uploadImage($blogArticle, $blogArticleData->images->uploadedFiles, null);
        $this->blogVisibilityRecalculationScheduler->scheduleRecalculation();

        $this->em->flush();

        return $blogArticle;
    }

    /**
     * @param int $blogArticleId
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleData $blogArticleData
     * @return \Shopsys\ShopBundle\Model\Blog\Article\BlogArticle
     */
    public function edit(int $blogArticleId, BlogArticleData $blogArticleData): BlogArticle
    {
        $blogArticle = $this->blogArticleRepository->getById($blogArticleId);
        $blogArticle->edit($blogArticleData, $this->blogArticleBlogCategoryDomainFactory);

        $this->em->flush();

        $this->friendlyUrlFacade->saveUrlListFormData('front_blogarticle_detail', $blogArticle->getId(), $blogArticleData->urls);
        $this->friendlyUrlFacade->createFriendlyUrls('front_blogarticle_detail', $blogArticle->getId(), $blogArticle->getNames());

        $this->imageFacade->saveImageOrdering($blogArticleData->images->orderedImages);
        $this->imageFacade->uploadImages($blogArticle, $blogArticleData->images->uploadedFiles, null);
        $this->imageFacade->deleteImages($blogArticle, $blogArticleData->images->imagesToDelete);

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
     * @return \Shopsys\ShopBundle\Model\Blog\Article\BlogArticle[]
     */
    public function getAllByDomainId(int $domainId): array
    {
        return $this->blogArticleRepository->getAllByDomainId($domainId);
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
        return $this->blogArticleRepository->getPaginationResultForListableInBlogCategory($blogCategory, $domainId, $locale, $page, $limit);
    }

    /**
     * @param int $domainId
     * @param string $locale
     * @param int $limit
     * @return \Shopsys\ShopBundle\Model\Blog\Article\BlogArticle[]
     */
    public function getHomepageBlogArticlesByDomainId(int $domainId, string $locale, int $limit): array
    {
        return $this->blogArticleRepository->getHomepageBlogArticlesByDomainId($domainId, $locale, $limit);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticle $blogArticle
     * @param int $domainId
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory
     */
    public function findBlogArticleMainCategoryOnDomain(BlogArticle $blogArticle, int $domainId): ?BlogCategory
    {
        return $this->blogArticleRepository->findBlogArticleMainCategoryOnDomain($blogArticle, $domainId);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @param int $domainId
     * @param string $locale
     * @param int $limit
     * @return \Shopsys\ShopBundle\Model\Blog\Article\BlogArticle[]
     */
    public function getVisibleByProduct(Product $product, int $domainId, string $locale, int $limit): array
    {
        return $this->blogArticleRepository->getVisibleByProduct($product, $domainId, $locale, $limit);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param string $locale
     * @return \Shopsys\ShopBundle\Model\Blog\Article\BlogArticle[]
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
}
