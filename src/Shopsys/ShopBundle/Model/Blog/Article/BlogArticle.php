<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Blog\Article;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Prezent\Doctrine\Translatable\Annotation as Prezent;
use Shopsys\FrameworkBundle\Model\Localization\AbstractTranslatableEntity;
use Shopsys\ShopBundle\Model\Blog\Article\Exception\BlogArticleDomainNotFoundException;

/**
 * @ORM\Table(name="blog_articles")
 * @ORM\Entity
 */
class BlogArticle extends AbstractTranslatableEntity
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleBlogCategoryDomain[]|\Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(
     *   targetEntity="Shopsys\ShopBundle\Model\Blog\Article\BlogArticleBlogCategoryDomain",
     *   mappedBy="blogArticle",
     *   orphanRemoval=true,
     *   cascade={"persist"}
     * )
     */
    private $blogArticleBlogCategoryDomains;

    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleTranslation[]
     *
     * @Prezent\Translations(targetEntity="Shopsys\ShopBundle\Model\Blog\Article\BlogArticleTranslation")
     */
    protected $translations;

    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleDomain[]|\Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Shopsys\ShopBundle\Model\Blog\Article\BlogArticleDomain", mappedBy="blogArticle", cascade={"persist"}, fetch="EXTRA_LAZY")
     */
    private $domains;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $hidden;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $visibleOnHomepage;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="date")
     */
    private $publishDate;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Product[]|\Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Shopsys\ShopBundle\Model\Product\Product", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="blog_article_products")
     */
    protected $products;

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleData $blogArticleData
     */
    public function __construct(BlogArticleData $blogArticleData)
    {
        $this->translations = new ArrayCollection();
        $this->domains = new ArrayCollection();
        $this->blogArticleBlogCategoryDomains = new ArrayCollection();

        $this->setTranslations($blogArticleData);

        $this->hidden = $blogArticleData->hidden;
        $this->createdAt = $blogArticleData->createdAt ?? new DateTime();
        $this->visibleOnHomepage = $blogArticleData->visibleOnHomepage;
        $this->publishDate = $blogArticleData->publishDate ?? new DateTime();
        $this->products = new ArrayCollection($blogArticleData->products);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleData $blogArticleData
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleBlogCategoryDomainFactory $blogArticleBlogCategoryDomainFactory
     */
    public function edit(BlogArticleData $blogArticleData, BlogArticleBlogCategoryDomainFactory $blogArticleBlogCategoryDomainFactory): void
    {
        $this->setTranslations($blogArticleData);
        $this->setDomains($blogArticleData);
        $this->setCategories($blogArticleBlogCategoryDomainFactory, $blogArticleData->blogCategoriesByDomainId);

        $this->hidden = $blogArticleData->hidden;
        $this->visibleOnHomepage = $blogArticleData->visibleOnHomepage;
        $this->publishDate = $blogArticleData->publishDate ?? new DateTime();
        $this->products = new ArrayCollection($blogArticleData->products);
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param string|null $locale
     * @return string
     */
    public function getName(?string $locale = null): ?string
    {
        return $this->translation($locale)->getName();
    }

    /**
     * @return string[]
     */
    public function getNames(): array
    {
        $namesByLocale = [];
        foreach ($this->translations as $translation) {
            $namesByLocale[$translation->getLocale()] = $translation->getName();
        }

        return $namesByLocale;
    }

    /**
     * @param int $domainId
     * @return \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleDomain
     */
    private function getDomain(int $domainId): BlogArticleDomain
    {
        foreach ($this->domains as $blogArticleDomain) {
            if ($blogArticleDomain->getDomainId() === $domainId) {
                return $blogArticleDomain;
            }
        }

        throw new BlogArticleDomainNotFoundException($this->id, $domainId);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleBlogCategoryDomainFactory $blogArticleBlogCategoryDomainFactory
     * @param array $blogCategoriesByDomainId
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory[]
     */
    public function setCategories(
        BlogArticleBlogCategoryDomainFactory $blogArticleBlogCategoryDomainFactory,
        array $blogCategoriesByDomainId
    ): void {
        foreach ($blogCategoriesByDomainId as $domainId => $blogCategories) {
            $this->removeOldBlogArticleBlogCategoryDomains($blogCategories, $domainId);
            $this->createNewBlogArticleBlogCategoryDomains($blogArticleBlogCategoryDomainFactory, $blogCategories, $domainId);
        }
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleBlogCategoryDomainFactory $blogArticleBlogCategoryDomainFactory
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory[] $newBlogCategories
     * @param int $domainId
     */
    private function createNewBlogArticleBlogCategoryDomains(
        BlogArticleBlogCategoryDomainFactory $blogArticleBlogCategoryDomainFactory,
        array $newBlogCategories,
        int $domainId
    ): void {
        $currentBlogArticleBlogCategoryDomainsOnDomainByCategoryId = $this->getBlogArticleBlogCategoryDomainsByDomainIdIndexedByCategoryId($domainId);

        foreach ($newBlogCategories as $newBlogCategory) {
            if (!array_key_exists($newBlogCategory->getId(), $currentBlogArticleBlogCategoryDomainsOnDomainByCategoryId)) {
                $blogArticleBlogCategoryDomain = $blogArticleBlogCategoryDomainFactory->create($this, $newBlogCategory, $domainId);
                $this->blogArticleBlogCategoryDomains->add($blogArticleBlogCategoryDomain);
            }
        }
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Category\Category[] $newBlogCategories
     * @param int $domainId
     */
    private function removeOldBlogArticleBlogCategoryDomains(array $newBlogCategories, int $domainId): void
    {
        $currentBlogArticleBlogCategoryDomains = $this->getBlogArticleBlogCategoryDomainsByDomainIdIndexedByCategoryId($domainId);

        foreach ($currentBlogArticleBlogCategoryDomains as $currentBlogArticleBlogCategoryDomain) {
            if (!in_array($currentBlogArticleBlogCategoryDomain->getBlogCategory(), $newBlogCategories, true)) {
                $this->blogArticleBlogCategoryDomains->removeElement($currentBlogArticleBlogCategoryDomain);
            }
        }
    }

    /**
     * @param int $domainId
     * @return \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleBlogCategoryDomain[]
     */
    private function getBlogArticleBlogCategoryDomainsByDomainIdIndexedByCategoryId(int $domainId): array
    {
        $blogArticleBlogCategoryDomainsByCategoryId = [];

        foreach ($this->blogArticleBlogCategoryDomains as $blogArticleBlogCategoryDomain) {
            if ($blogArticleBlogCategoryDomain->getDomainId() === $domainId) {
                $blogArticleBlogCategoryDomainsByCategoryId[$blogArticleBlogCategoryDomain->getBlogCategory()->getId()] = $blogArticleBlogCategoryDomain;
            }
        }

        return $blogArticleBlogCategoryDomainsByCategoryId;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Category\Category[]
     */
    public function getBlogCategoriesIndexedByDomainId()
    {
        $blogCategoriesByDomainId = [];

        foreach ($this->blogArticleBlogCategoryDomains as $blogArticleBlogCategoryDomain) {
            $blogCategoriesByDomainId[$blogArticleBlogCategoryDomain->getDomainId()][] = $blogArticleBlogCategoryDomain->getBlogCategory();
        }

        return $blogCategoriesByDomainId;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleData $blogArticleData
     */
    private function setTranslations(BlogArticleData $blogArticleData): void
    {
        foreach ($blogArticleData->names as $locale => $name) {
            $this->translation($locale)->setName($name);
        }
        foreach ($blogArticleData->descriptions as $locale => $name) {
            $this->translation($locale)->setDescription($name);
        }
        foreach ($blogArticleData->perexes as $locale => $name) {
            $this->translation($locale)->setPerex($name);
        }
        foreach ($blogArticleData->mainPhotoTitles as $locale => $mainPhotoTitle) {
            $this->translation($locale)->setMainPhotoTitle($mainPhotoTitle);
        }
    }

    /**
     * @param int $domainId
     * @return string|null
     */
    public function getSeoTitle(int $domainId): ?string
    {
        return $this->getDomain($domainId)->getSeoTitle();
    }

    /**
     * @param int $domainId
     * @return string|null
     */
    public function getSeoH1(int $domainId): ?string
    {
        return $this->getDomain($domainId)->getSeoH1();
    }

    /**;
     * @param $domainId
     * @return bool
     */

    /**
     * @param int $domainId
     * @return bool
     */
    public function isVisible(int $domainId): bool
    {
        return $this->getDomain($domainId)->isVisible();
    }

    /**
     * @param int $domainId
     * @return string|null
     */
    public function getSeoMetaDescription(int $domainId): ?string
    {
        return $this->getDomain($domainId)->getSeoMetaDescription();
    }

    /**
     * @param string|null $locale
     * @param string locale
     * @return string|null
     */
    public function getDescription(?string $locale = null): ?string
    {
        return $this->translation($locale)->getDescription();
    }

    /**
     * @return string[]
     */
    public function getDescriptions(): array
    {
        $descriptionsByLocale = [];
        foreach ($this->translations as $translation) {
            $descriptionsByLocale[$translation->getLocale()] = $translation->getDescription();
        }

        return $descriptionsByLocale;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleTranslation
     */
    protected function createTranslation(): BlogArticleTranslation
    {
        return new BlogArticleTranslation();
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleData $blogArticleData
     */
    private function setDomains(BlogArticleData $blogArticleData): void
    {
        foreach ($this->domains as $blogArticleDomain) {
            $domainId = $blogArticleDomain->getDomainId();
            $blogArticleDomain->setSeoTitle($blogArticleData->seoTitles[$domainId]);
            $blogArticleDomain->setSeoH1($blogArticleData->seoH1s[$domainId]);
            $blogArticleDomain->setSeoMetaDescription($blogArticleData->seoMetaDescriptions[$domainId]);
        }
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleData $blogArticleData
     */
    public function createDomains(BlogArticleData $blogArticleData): void
    {
        $domainIds = array_keys($blogArticleData->seoTitles);

        foreach ($domainIds as $domainId) {
            $categoryDomain = new BlogArticleDomain($this, $domainId);
            $this->domains->add($categoryDomain);
        }

        $this->setDomains($blogArticleData);
    }

    /**
     * @return bool $visible
     */
    public function isHidden()
    {
        return $this->hidden;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @return bool
     */
    public function isVisibleOnHomepage(): bool
    {
        return $this->visibleOnHomepage;
    }

    /**
     * @return \DateTime
     */
    public function getPublishDate(): DateTime
    {
        return $this->publishDate;
    }

    /**
     * @return string[]
     */
    public function getPerexes(): array
    {
        $perexesByLocale = [];
        foreach ($this->translations as $translation) {
            $perexesByLocale[$translation->getLocale()] = $translation->getPerex();
        }

        return $perexesByLocale;
    }

    /**
     * @param string|null $locale
     * @return string|null
     */
    public function getPerex(?string $locale = null): ?string
    {
        return $this->translation($locale)->getPerex();
    }

    /**
     * @return string[]
     */
    public function getMainPhotosTitles(): array
    {
        $mainPhotoTitlesByLocale = [];
        foreach ($this->translations as $translation) {
            $mainPhotoTitlesByLocale[$translation->getLocale()] = $translation->getMainPhotoTitle();
        }

        return $mainPhotoTitlesByLocale;
    }

    /**
     * @param string|null $locale
     * @return string|null
     */
    public function getMainPhotoTitle(?string $locale = null): ?string
    {
        return $this->translation($locale)->getMainPhotoTitle();
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Product\Product[]
     */
    public function getProducts(): array
    {
        return $this->products->toArray();
    }
}
