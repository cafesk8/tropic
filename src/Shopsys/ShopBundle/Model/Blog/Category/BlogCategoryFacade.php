<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Blog\Category;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade;
use Shopsys\ShopBundle\Model\Blog\Article\BlogArticle;
use Shopsys\ShopBundle\Model\Blog\BlogVisibilityRecalculationScheduler;

class BlogCategoryFacade
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryRepository
     */
    private $blogCategoryRepository;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade
     */
    private $friendlyUrlFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryFactory
     */
    private $blogCategoryFactory;

    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryWithPreloadedChildrenFactory
     */
    private $blogCategoryWithPreloadedChildrenFactory;

    /**
     * @var \Shopsys\ShopBundle\Model\Blog\BlogVisibilityRecalculationScheduler
     */
    private $blogVisibilityRecalculationScheduler;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryRepository $blogCategoryRepository
     * @param \Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryFactory $blogCategoryFactory
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryWithPreloadedChildrenFactory $blogCategoryWithPreloadedChildrenFactory
     * @param \Shopsys\ShopBundle\Model\Blog\BlogVisibilityRecalculationScheduler $blogVisibilityRecalculationScheduler
     */
    public function __construct(
        EntityManagerInterface $em,
        BlogCategoryRepository $blogCategoryRepository,
        FriendlyUrlFacade $friendlyUrlFacade,
        BlogCategoryFactory $blogCategoryFactory,
        BlogCategoryWithPreloadedChildrenFactory $blogCategoryWithPreloadedChildrenFactory,
        BlogVisibilityRecalculationScheduler $blogVisibilityRecalculationScheduler
    ) {
        $this->em = $em;
        $this->blogCategoryRepository = $blogCategoryRepository;
        $this->friendlyUrlFacade = $friendlyUrlFacade;
        $this->blogCategoryFactory = $blogCategoryFactory;
        $this->blogCategoryWithPreloadedChildrenFactory = $blogCategoryWithPreloadedChildrenFactory;
        $this->blogVisibilityRecalculationScheduler = $blogVisibilityRecalculationScheduler;
    }

    /**
     * @param int $blogCategoryId
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory
     */
    public function getById(int $blogCategoryId): BlogCategory
    {
        return $this->blogCategoryRepository->getById($blogCategoryId);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryData $blogCategoryData
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory
     */
    public function create(BlogCategoryData $blogCategoryData): BlogCategory
    {
        $rootCategory = $this->getRootBlogCategory();
        $blogCategory = $this->blogCategoryFactory->create($blogCategoryData, $rootCategory);

        $this->em->persist($blogCategory);
        $this->em->flush($blogCategory);

        $blogCategory->createDomains($blogCategoryData);

        $this->friendlyUrlFacade->createFriendlyUrls('front_blogcategory_detail', $blogCategory->getId(), $blogCategory->getNames());
        $this->blogVisibilityRecalculationScheduler->scheduleRecalculation();

        $this->em->flush($blogCategory);

        return $blogCategory;
    }

    /**
     * @param int $blogCategoryId
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryData $blogCategoryData
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory
     */
    public function edit(int $blogCategoryId, BlogCategoryData $blogCategoryData): BlogCategory
    {
        $rootCategory = $this->getRootBlogCategory();
        $blogCategory = $this->blogCategoryRepository->getById($blogCategoryId);
        $blogCategory->edit($blogCategoryData);

        if ($blogCategory->getParent() === null) {
            $blogCategory->setParent($rootCategory);
        }

        $this->em->flush();

        $this->friendlyUrlFacade->saveUrlListFormData('front_blogcategory_detail', $blogCategory->getId(), $blogCategoryData->urls);
        $this->friendlyUrlFacade->createFriendlyUrls('front_blogcategory_detail', $blogCategory->getId(), $blogCategory->getNames());
        $this->blogVisibilityRecalculationScheduler->scheduleRecalculation();

        return $blogCategory;
    }

    /**
     * @param int $blogCategoryId
     */
    public function deleteById(int $blogCategoryId): void
    {
        $blogCategory = $this->blogCategoryRepository->getById($blogCategoryId);

        foreach ($blogCategory->getChildren() as $child) {
            $child->setParent($blogCategory->getParent());
        }

        $this->em->flush();
        $this->em->remove($blogCategory);
        $this->blogVisibilityRecalculationScheduler->scheduleRecalculation();
        $this->em->flush();
    }

    /**
     * @param int[] $parentIdByBlogCategoryId
     */
    public function editOrdering(array $parentIdByBlogCategoryId): void
    {
        // eager-load all categories into identity map
        $this->blogCategoryRepository->getAll();

        $rootCategory = $this->getRootBlogCategory();

        foreach ($parentIdByBlogCategoryId as $blogCategoryId => $parentId) {
            if ($parentId === null) {
                $parent = $rootCategory;
            } else {
                $parent = $this->blogCategoryRepository->getById($parentId);
            }

            $blogCategory = $this->blogCategoryRepository->getById($blogCategoryId);
            $blogCategory->setParent($parent);

            $this->em->flush($blogCategory);

            $this->blogCategoryRepository->moveDown($blogCategory, BlogCategoryRepository::MOVE_DOWN_TO_BOTTOM);
        }

        $this->em->flush();
    }

    /**
     * @param string $locale
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory[]
     */
    public function getTranslatedAll(string $locale): array
    {
        return $this->blogCategoryRepository->getAllByLocale($locale);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory[] $selectedCategories
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory[]
     */
    public function getAllBlogCategoriesOfCollapsedTree(array $selectedCategories): array
    {
        $blogCategories = $this->blogCategoryRepository->getAllBlogCategoriesOfCollapsedTree($selectedCategories);

        return $blogCategories;
    }

    /**
     * @param string $locale
     * @return \Shopsys\FrameworkBundle\Model\Category\CategoryWithPreloadedChildren[]
     */
    public function getAllBlogCategoriesWithPreloadedChildren(string $locale): array
    {
        $blogCategories = $this->blogCategoryRepository->getPreOrderTreeTraversalForAllBlogCategories($locale);
        $blogCategoriesWithPreloadedChildren = $this->blogCategoryWithPreloadedChildrenFactory->createBlogCategoriesWithPreloadedChildren($blogCategories);

        return $blogCategoriesWithPreloadedChildren;
    }

    /**
     * @param int $domainId
     * @param string $locale
     * @return \Shopsys\FrameworkBundle\Model\Category\CategoryWithPreloadedChildren[]
     */
    public function getVisibleBlogCategoriesWithPreloadedChildrenOnDomain(int $domainId, string $locale): array
    {
        $blogCategories = $this->blogCategoryRepository->getPreOrderTreeTraversalForVisibleBlogCategoriesOnDomain($domainId, $locale);

        $blogCategoriesWithPreloadedChildren = $this->blogCategoryWithPreloadedChildrenFactory->createBlogCategoriesWithPreloadedChildren($blogCategories);

        return $blogCategoriesWithPreloadedChildren;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory $blogCategory
     * @param int $domainId
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory[]
     */
    public function getVisibleBlogCategoriesInPathFromRootOnDomain(BlogCategory $blogCategory, int $domainId)
    {
        return $this->blogCategoryRepository->getVisibleBlogCategoriesInPathFromRootOnDomain($blogCategory, $domainId);
    }

    /**
     * @param int $domainId
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory[]
     */
    public function getAllVisibleChildrenByDomainId(int $domainId): array
    {
        return $this->blogCategoryRepository->getAllVisibleByDomainId($domainId);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory $blogCategory
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory[]
     */
    public function getTranslatedAllWithoutBranch(BlogCategory $blogCategory, DomainConfig $domainConfig): array
    {
        return $this->blogCategoryRepository->getTranslatedAllWithoutBranch($blogCategory, $domainConfig);
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory
     */
    public function getRootBlogCategory(): BlogCategory
    {
        return $this->blogCategoryRepository->getRootBlogCategory();
    }

    /**
     * @param int $domainId
     * @param int $blogCategoryId
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory
     */
    public function getVisibleOnDomainById(int $domainId, int $blogCategoryId): BlogCategory
    {
        $blogCategory = $this->getById($blogCategoryId);

        if (!$blogCategory->isVisible($domainId)) {
            $message = 'Blog category ID ' . $blogCategoryId . ' is not visible on domain ID ' . $domainId;
            throw new \Shopsys\ShopBundle\Model\Blog\Category\Exception\BlogCategoryNotFoundException($message);
        }

        return $blogCategory;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticle $blogArticle
     * @param int $domainId
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory
     */
    public function getBlogArticleMainBlogCategoryOnDomain(BlogArticle $blogArticle, int $domainId): BlogCategory
    {
        return $this->blogCategoryRepository->getBlogArticleMainBlogCategoryOnDomain($blogArticle, $domainId);
    }

    /**
     * @param int[] $blogCategoryIds
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory[]
     */
    public function getByIds(array $blogCategoryIds)
    {
        return $this->blogCategoryRepository->getByIds($blogCategoryIds);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticle[] $blogArticles
     * @param int $domainId
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory[]
     */
    public function getLastBlogCategoryForBlogArticlesByBlogArticleId(array $blogArticles, int $domainId): array
    {
        $lastBlogCategoriesByBlogArticleId = [];
        foreach ($blogArticles as $blogArticle) {
            $lastBlogCategoriesByBlogArticleId[$blogArticle->getId()] = $this->getBlogArticleMainBlogCategoryOnDomain($blogArticle, $domainId);
        }

        return $lastBlogCategoriesByBlogArticleId;
    }
}
