<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Blog\Category;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Prezent\Doctrine\Translatable\Annotation as Prezent;
use Shopsys\FrameworkBundle\Model\Localization\AbstractTranslatableEntity;
use Shopsys\ShopBundle\Model\Blog\Category\Exception\BlogCategoryDomainNotFoundException;

/**
 * @Gedmo\Tree(type="nested")
 * @ORM\Table(name="blog_categories")
 * @ORM\Entity
 */
class BlogCategory extends AbstractTranslatableEntity
{
    public const BLOG_MAIN_PAGE_CATEGORY_ID = 2;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryTranslation[]
     *
     * @Prezent\Translations(targetEntity="Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryTranslation")
     */
    protected $translations;

    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory|null
     *
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="Shopsys\ShopBundle\Model\Blog\Category\BlogCategory", inversedBy="children")
     * @ORM\JoinColumn(nullable=true, name="parent_id", referencedColumnName="id")
     */
    private $parent;

    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory[]
     *
     * @ORM\OneToMany(targetEntity="Shopsys\ShopBundle\Model\Blog\Category\BlogCategory", mappedBy="parent")
     * @ORM\OrderBy({"lft" = "ASC"})
     */
    private $children;

    /**
     * @var int
     *
     * @Gedmo\TreeLevel
     * @ORM\Column(type="integer")
     */
    private $level;

    /**
     * @var int
     *
     * @Gedmo\TreeLeft
     * @ORM\Column(type="integer")
     */
    private $lft;

    /**
     * @var int
     *
     * @Gedmo\TreeRight
     * @ORM\Column(type="integer")
     */
    private $rgt;

    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryDomain[]|\Doctrine\Common\Collections\ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryDomain", mappedBy="blogCategory", cascade={"persist"}, fetch="EXTRA_LAZY")
     */
    private $domains;

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryData $blogCategoryData
     */
    public function __construct(BlogCategoryData $blogCategoryData)
    {
        $this->setParent($blogCategoryData->parent);
        $this->translations = new ArrayCollection();
        $this->domains = new ArrayCollection();

        $this->setTranslations($blogCategoryData);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryData $blogCategoryData
     */
    public function edit(BlogCategoryData $blogCategoryData): void
    {
        $this->setParent($blogCategoryData->parent);
        $this->setTranslations($blogCategoryData);
        $this->setDomains($blogCategoryData);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory $parent
     */
    public function setParent(?self $parent = null): void
    {
        $this->parent = $parent;
    }

    /**
     * @return int
     */
    public function getId()
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
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory|null
     */
    public function getParent(): ?self
    {
        return $this->parent;
    }

    /**
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * Method does not lazy load children
     *
     * @return bool
     */
    public function hasChildren(): bool
    {
        return $this->getRgt() - $this->getLft() > 1;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @return int
     */
    public function getLft(): int
    {
        return $this->lft;
    }

    /**
     * @return int
     */
    public function getRgt(): int
    {
        return $this->rgt;
    }

    /**
     * @param int $domainId
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryDomain
     */
    private function getDomain(int $domainId): BlogCategoryDomain
    {
        foreach ($this->domains as $blogCategoryDomain) {
            if ($blogCategoryDomain->getDomainId() === $domainId) {
                return $blogCategoryDomain;
            }
        }

        throw new BlogCategoryDomainNotFoundException($this->id, $domainId);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryData $blogCategoryData
     */
    private function setTranslations(BlogCategoryData $blogCategoryData): void
    {
        foreach ($blogCategoryData->names as $locale => $name) {
            $this->translation($locale)->setName($name);
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

    /**
     * @param int $domainId
     * @return bool
     */
    public function isEnabled(int $domainId): bool
    {
        return $this->getDomain($domainId)->isEnabled();
    }

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
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryTranslation
     */
    protected function createTranslation(): BlogCategoryTranslation
    {
        return new BlogCategoryTranslation();
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryData $blogCategoryData
     */
    private function setDomains(BlogCategoryData $blogCategoryData): void
    {
        foreach ($this->domains as $blogCategoryDomain) {
            $domainId = $blogCategoryDomain->getDomainId();
            $blogCategoryDomain->setSeoTitle($blogCategoryData->seoTitles[$domainId]);
            $blogCategoryDomain->setSeoH1($blogCategoryData->seoH1s[$domainId]);
            $blogCategoryDomain->setSeoMetaDescription($blogCategoryData->seoMetaDescriptions[$domainId]);
            $blogCategoryDomain->setEnabled($blogCategoryData->enabled[$domainId]);
        }
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryData $blogCategoryData
     */
    public function createDomains(BlogCategoryData $blogCategoryData): void
    {
        $domainIds = array_keys($blogCategoryData->seoTitles);

        foreach ($domainIds as $domainId) {
            $blogCategoryDomain = new BlogCategoryDomain($this, $domainId);
            $this->domains->add($blogCategoryDomain);
        }

        $this->setDomains($blogCategoryData);
    }

    /**
     * @return bool
     */
    public function isMainPage(): bool
    {
        return $this->id === self::BLOG_MAIN_PAGE_CATEGORY_ID;
    }
}
