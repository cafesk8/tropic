<?php

declare(strict_types=1);

namespace App\Model\Category;

use App\Model\Advert\Advert;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Shopsys\FrameworkBundle\Model\Category\Category as BaseCategory;
use Shopsys\FrameworkBundle\Model\Category\CategoryData as BaseCategoryData;

/**
 * @Gedmo\Tree(type="nested")
 * @ORM\Table(name="categories")
 * @ORM\Entity
 *
 * @method \App\Model\Category\CategoryTranslation translation(?string $locale = null)
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
    public const SALE_TYPE = 'sale';

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
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $mallCategoryId;

    /**
     * @var \App\Model\Advert\Advert|null
     *
     * @ORM\ManyToOne(targetEntity="App\Model\Advert\Advert", inversedBy="categories")
     * @ORM\JoinColumn(name="advert_id", nullable=true, referencedColumnName="id", onDelete="SET NULL")
     */
    private $advert;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true, unique=true)
     */
    private $pohodaId;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    private $pohodaParentId;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedByPohodaAt;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    private $pohodaPosition;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true, length=20)
     */
    private $type;

    /**
     * @param \App\Model\Category\CategoryData $categoryData
     */
    public function __construct(BaseCategoryData $categoryData)
    {
        parent::__construct($categoryData);

        $this->pohodaId = $categoryData->pohodaId;
        $this->fillCommonProperties($categoryData);
    }

    /**
     * @param \App\Model\Category\CategoryData $categoryData
     */
    public function edit(BaseCategoryData $categoryData)
    {
        parent::edit($categoryData);

        $this->fillCommonProperties($categoryData);
    }

    /**
     * @param \App\Model\Category\CategoryData $categoryData
     */
    private function fillCommonProperties(CategoryData $categoryData): void
    {
        $this->listable = $categoryData->listable;
        $this->preListingCategory = $categoryData->preListingCategory;
        $this->mallCategoryId = $categoryData->mallCategoryId;
        $this->advert = $categoryData->advert;
        $this->updatedByPohodaAt = $categoryData->updatedByPohodaAt;
        $this->pohodaParentId = $categoryData->pohodaParentId;
        $this->pohodaPosition = $categoryData->pohodaPosition;
        $this->type = $categoryData->type;
        $this->setTranslations($categoryData);
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
    protected function setTranslations(BaseCategoryData $categoryData): void
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

    /**
     * @return \App\Model\Advert\Advert|null
     */
    public function getAdvert(): ?Advert
    {
        return $this->advert;
    }

    /**
     * @param \App\Model\Advert\Advert|null $advert
     */
    public function setAdvert(?Advert $advert): void
    {
        $this->advert = $advert;
    }

    /**
     * @return \DateTime|null
     */
    public function getUpdatedByPohodaAt(): ?DateTime
    {
        return $this->updatedByPohodaAt;
    }

    /**
     * @return int|null
     */
    public function getPohodaId(): ?int
    {
        return $this->pohodaId;
    }

    /**
     * @return int|null
     */
    public function getPohodaParentId(): ?int
    {
        return $this->pohodaParentId;
    }

    /**
     * @return int|null
     */
    public function getPohodaPosition(): ?int
    {
        return $this->pohodaPosition;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function isSaleType(): bool
    {
        return $this->type === self::SALE_TYPE;
    }
}
