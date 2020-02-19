<?php

declare(strict_types=1);

namespace App\Model\Category;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Shopsys\FrameworkBundle\Model\Category\Category as BaseCategory;
use Shopsys\FrameworkBundle\Model\Category\CategoryData;
use Shopsys\FrameworkBundle\Model\Category\CategoryData as BaseCategoryData;

/**
 * @Gedmo\Tree(type="nested")
 * @ORM\Table(name="categories")
 * @ORM\Entity
 *
 * @method CategoryTranslation translation(?string $locale = null)
 * @property \App\Model\Category\CategoryTranslation[]|\Doctrine\Common\Collections\Collection $translations
 * @property \App\Model\Category\Category|null $parent
 * @property \App\Model\Category\Category[]|\Doctrine\Common\Collections\Collection $children
 * @method setParent(\App\Model\Category\Category|null $parent)
 * @method \App\Model\Category\Category|null getParent()
 * @method \App\Model\Category\Category[] getChildren()
 * @method setDomains(\App\Model\Category\CategoryData $categoryData)
 * @method createDomains(\App\Model\Category\CategoryData $categoryData)
 */
class Category extends BaseCategory
{
    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $displayedInHorizontalMenu;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $listable;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $preListingCategory;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $displayedInFirstColumn;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $legendaryCategory;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $mallCategoryId;

    /**
     * @param \App\Model\Category\CategoryData $categoryData
     */
    public function __construct(BaseCategoryData $categoryData)
    {
        parent::__construct($categoryData);

        $this->displayedInHorizontalMenu = $categoryData->displayedInHorizontalMenu;
        $this->listable = $categoryData->listable;
        $this->preListingCategory = $categoryData->preListingCategory;
        $this->displayedInFirstColumn = $categoryData->displayedInFirstColumn;
        $this->legendaryCategory = $categoryData->legendaryCategory;
        $this->mallCategoryId = $categoryData->mallCategoryId;

        $this->setTranslations($categoryData);
    }

    /**
     * @param \App\Model\Category\CategoryData $categoryData
     */
    public function edit(BaseCategoryData $categoryData)
    {
        parent::edit($categoryData);

        $this->displayedInHorizontalMenu = $categoryData->displayedInHorizontalMenu;
        $this->listable = $categoryData->listable;
        $this->preListingCategory = $categoryData->preListingCategory;
        $this->displayedInFirstColumn = $categoryData->displayedInFirstColumn;
        $this->legendaryCategory = $categoryData->legendaryCategory;
        $this->mallCategoryId = $categoryData->mallCategoryId;

        $this->setTranslations($categoryData);
    }

    /**
     * @return bool
     */
    public function isDisplayedInHorizontalMenu(): bool
    {
        return $this->displayedInHorizontalMenu;
    }

    /**
     * @return bool
     */
    public function isPreListingCategory(): bool
    {
        return $this->preListingCategory;
    }

    /**
     * @param string|null $locale
     * @return string
     */
    public function getNameWithLevelPad(?string $locale = null): string
    {
        return str_repeat('-', $this->level < 1 ? 0 : $this->level - 1) . ' ' . parent::getName($locale);
    }

    /**
     * @return bool
     */
    public function isDisplayedInFirstColumn(): bool
    {
        return $this->displayedInFirstColumn;
    }

    /**
     * @return bool
     */
    public function isLegendaryCategory(): bool
    {
        return $this->legendaryCategory;
    }

    /**
     * @return string|null
     */
    public function getMallCategoryId(): ?string
    {
        return $this->mallCategoryId;
    }

    /**
     * @return \App\Model\Category\CategoryTranslation
     */
    protected function createTranslation(): CategoryTranslation
    {
        return new CategoryTranslation();
    }

    /**
     * @param string|null $locale
     * @return string|null
     */
    public function getLeftBannerText($locale = null): ?string
    {
        return $this->translation($locale)->getLeftBannerText();
    }

    /**
     * @param string|null $locale
     * @return string|null
     */
    public function getRightBannerText($locale = null): ?string
    {
        return $this->translation($locale)->getRightBannerText();
    }

    /**
     * @return string[]|null[]
     */
    public function getLeftBannerTexts(): array
    {
        $textsByLocale = [];

        foreach ($this->translations as $translation) {
            $textsByLocale[$translation->getLocale()] = $translation->getLeftBannerText();
        }

        return $textsByLocale;
    }

    /**
     * @return string[]|null[]
     */
    public function getRightBannerTexts(): array
    {
        $textsByLocale = [];

        foreach ($this->translations as $translation) {
            $textsByLocale[$translation->getLocale()] = $translation->getRightBannerText();
        }

        return $textsByLocale;
    }

    /**
     * @param \App\Model\Category\CategoryData $categoryData
     */
    protected function setTranslations(CategoryData $categoryData): void
    {
        parent::setTranslations($categoryData);

        foreach ($categoryData->leftBannerTexts as $locale => $text) {
            $this->translation($locale)->setLeftBannerText($text);
        }
        foreach ($categoryData->rightBannerTexts as $locale => $text) {
            $this->translation($locale)->setRightBannerText($text);
        }
    }

    /**
     * @return bool
     */
    public function isListable(): bool
    {
        return $this->listable;
    }

    /**
     * @param bool $listable
     */
    public function setListable(bool $listable): void
    {
        $this->listable = $listable;
    }
}