<?php

declare(strict_types=1);

namespace App\Model\Category;

use App\Model\Advert\Advert;
use App\Model\Product\Product;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
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
 * @property \App\Model\Category\CategoryDomain[]|\Doctrine\Common\Collections\Collection $domains
 * @method \App\Model\Category\CategoryDomain getCategoryDomain(int $domainId)
 */
class Category extends BaseCategory
{
    public const SALE_TYPE = 'sale';
    public const NEWS_TYPE = 'news';

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
     * @var \App\Model\Product\Parameter\Parameter[]|\Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="App\Model\Product\Parameter\Parameter", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="category_parameters")
     */
    private $filterParameters;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $unavailableProductsShown;

    /**
     * @var \App\Model\Category\CategoryBrand\CategoryBrand[]|\Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="App\Model\Category\CategoryBrand\CategoryBrand", mappedBy="category")
     */
    private $categoryBrands;

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
        $this->updatedByPohodaAt = $categoryData->updatedByPohodaAt;
        $this->pohodaParentId = $categoryData->pohodaParentId;
        $this->pohodaPosition = $categoryData->pohodaPosition;
        $this->type = $categoryData->type;
        $this->setTranslations($categoryData);
        $this->filterParameters = new ArrayCollection($categoryData->filterParameters);
        $this->setDomains($categoryData);
        $this->unavailableProductsShown = $categoryData->unavailableProductsShown;
        foreach ($categoryData->adverts as $advert) {
            $this->setAdvert($advert);
        }
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
     * @param \App\Model\Category\CategoryData $categoryData
     */
    protected function createDomains(BaseCategoryData $categoryData): void
    {
        $domainIds = array_keys($categoryData->seoTitles);

        foreach ($domainIds as $domainId) {
            $categoryDomain = new CategoryDomain($this, $domainId);
            $this->domains->add($categoryDomain);
        }

        $this->setDomains($categoryData);
    }

    /**
     * @param \App\Model\Category\CategoryData $categoryData
     */
    protected function setDomains(BaseCategoryData $categoryData): void
    {
        parent::setDomains($categoryData);

        foreach ($this->domains as $categoryDomain) {
            $domainId = $categoryDomain->getDomainId();
            $categoryDomain->setContainsSaleProduct($categoryData->containsSaleProducts[$domainId]);
            $categoryDomain->setContainsNewsProduct($categoryData->containsNewsProducts[$domainId]);
            $categoryDomain->setTipShown($categoryData->tipShown[$domainId]);
            $categoryDomain->setTipName($categoryData->tipName[$domainId]);
            $categoryDomain->setTipText($categoryData->tipText[$domainId]);
            $categoryDomain->setTipProduct($categoryData->tipProduct[$domainId]);
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
     * @param int $domainId
     * @return \App\Model\Advert\Advert|null
     */
    public function getAdvert(int $domainId): ?Advert
    {
        return $this->getCategoryDomain($domainId)->getAdvert();
    }

    /**
     * @return \App\Model\Advert\Advert[]
     */
    public function getAdverts(): array
    {
        $adverts = [];

        foreach ($this->domains as $domain) {
            $adverts[$domain->getDomainId()] = $domain->getAdvert();
        }

        return array_filter($adverts, fn (?Advert $advert) => $advert !== null);
    }

    /**
     * @param \App\Model\Advert\Advert|null $advert
     */
    public function setAdvert(?Advert $advert): void
    {
        $this->getCategoryDomain($advert->getDomainId())->setAdvert($advert);
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

    /**
     * @return bool
     */
    public function isNewsType(): bool
    {
        return $this->type === self::NEWS_TYPE;
    }

    /**
     * @return \App\Model\Product\Parameter\Parameter[]
     */
    public function getFilterParameters(): array
    {
        return $this->filterParameters->toArray();
    }

    /**
     * @param int $domainId
     * @return bool
     */
    public function containsSaleProduct(int $domainId): bool
    {
        return $this->getCategoryDomain($domainId)->containsSaleProduct();
    }

    /**
     * @return bool[]
     */
    public function containsSaleProducts(): array
    {
        $containsSaleProductByDomain = [];

        foreach ($this->domains as $domain) {
            $containsSaleProductByDomain[$domain->getDomainId()] = $this->containsSaleProduct($domain->getDomainId());
        }

        return $containsSaleProductByDomain;
    }

    /**
     * @param int $domainId
     * @return bool
     */
    public function containsNewsProduct(int $domainId): bool
    {
        return $this->getCategoryDomain($domainId)->containsNewsProduct();
    }

    /**
     * @return bool[]
     */
    public function containsNewsProducts(): array
    {
        $containsNewsProductByDomain = [];

        foreach ($this->domains as $domain) {
            $containsNewsProductByDomain[$domain->getDomainId()] = $this->containsNewsProduct($domain->getDomainId());
        }

        return $containsNewsProductByDomain;
    }

    /**
     * @return bool
     */
    public function isUnavailableProductsShown(): bool
    {
        return $this->unavailableProductsShown;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @return string
     */
    public function getTitle(Domain $domain): string
    {
        return $this->getSeoH1($domain->getId()) ?? $this->getName($domain->getLocale());
    }

    /**
     * @return \App\Model\Category\CategoryBrand\CategoryBrand[]
     */
    public function getCategoryBrands(): array
    {
        return $this->categoryBrands->toArray();
    }

    /**
     * @return bool[]
     */
    public function areTipsShown(): array
    {
        $tipShown = [];

        foreach ($this->domains as $categoryDomain) {
            $tipShown[$categoryDomain->getDomainId()] = $categoryDomain->isTipShown();
        }

        return $tipShown;
    }

    /**
     * @return string[]
     */
    public function getTipNames(): array
    {
        $tipName = [];

        foreach ($this->domains as $categoryDomain) {
            $tipName[$categoryDomain->getDomainId()] = $categoryDomain->getTipName();
        }

        return $tipName;
    }

    /**
     * @return string[]
     */
    public function getTipTexts(): array
    {
        $tipTexts = [];

        foreach ($this->domains as $categoryDomain) {
            $tipTexts[$categoryDomain->getDomainId()] = $categoryDomain->getTipText();
        }

        return $tipTexts;
    }

    /**
     * @return \App\Model\Product\Product[]
     */
    public function getTipProducts(): array
    {
        $tipProducts = [];

        foreach ($this->domains as $categoryDomain) {
            $tipProducts[$categoryDomain->getDomainId()] = $categoryDomain->getTipProduct();
        }

        return $tipProducts;
    }

    /**
     * @param int $domainId
     * @return bool
     */
    public function isTipShown(int $domainId): bool
    {
        return $this->getCategoryDomain($domainId)->isTipShown();
    }

    /**
     * @param int $domainId
     * @return string|null
     */
    public function getTipName(int $domainId): ?string
    {
        return $this->getCategoryDomain($domainId)->getTipName();
    }

    /**
     * @param int $domainId
     * @return string|null
     */
    public function getTipText(int $domainId): ?string
    {
        return $this->getCategoryDomain($domainId)->getTipText();
    }

    /**
     * @param int $domainId
     * @return \App\Model\Product\Product|null
     */
    public function getTipProduct(int $domainId): ?Product
    {
        return $this->getCategoryDomain($domainId)->getTipProduct();
    }
}
