<?php

declare(strict_types=1);

namespace App\Model\Category;

use App\Model\Product\Product;
use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Category\Category;
use Shopsys\FrameworkBundle\Model\Category\CategoryDomain as BaseCategoryDomain;

/**
 * @ORM\Table(name="category_domains")
 * @ORM\Entity
 * @property \App\Model\Category\Category $category
 */
class CategoryDomain extends BaseCategoryDomain
{
    /**
     * @ORM\Column(type="boolean")
     */
    private bool $containsSaleProduct;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $containsNewsProduct;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $tipShown;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $tipName;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $tipText;

    /**
     * @ORM\ManyToOne(targetEntity="App\Model\Product\Product")
     * @ORM\JoinColumn(nullable=true, unique=false)
     */
    private ?Product $tipProduct;

    /**
     * @param \App\Model\Category\Category $category
     * @param int $domainId
     */
    public function __construct(Category $category, int $domainId)
    {
        parent::__construct($category, $domainId);
        $this->containsSaleProduct = false;
        $this->containsNewsProduct = false;
        $this->tipShown = false;
        $this->tipName = null;
        $this->tipText = null;
        $this->tipProduct = null;
    }

    /**
     * @return bool
     */
    public function containsSaleProduct(): bool
    {
        return $this->containsSaleProduct;
    }

    /**
     * @param bool $containsSaleProduct
     */
    public function setContainsSaleProduct(bool $containsSaleProduct): void
    {
        $this->containsSaleProduct = $containsSaleProduct;
    }

    /**
     * @return bool
     */
    public function containsNewsProduct(): bool
    {
        return $this->containsNewsProduct;
    }

    /**
     * @param bool $containsNewsProduct
     */
    public function setContainsNewsProduct(bool $containsNewsProduct): void
    {
        $this->containsNewsProduct = $containsNewsProduct;
    }

    /**
     * @return bool
     */
    public function isTipShown(): bool
    {
        return $this->tipShown;
    }

    /**
     * @param bool $tipShown
     */
    public function setTipShown(bool $tipShown): void
    {
        $this->tipShown = $tipShown;
    }

    /**
     * @return string|null
     */
    public function getTipName(): ?string
    {
        return $this->tipName;
    }

    /**
     * @param string|null $tipName
     */
    public function setTipName(?string $tipName): void
    {
        $this->tipName = $tipName;
    }

    /**
     * @return string|null
     */
    public function getTipText(): ?string
    {
        return $this->tipText;
    }

    /**
     * @param string|null $tipText
     */
    public function setTipText(?string $tipText): void
    {
        $this->tipText = $tipText;
    }

    /**
     * @return \App\Model\Product\Product|null
     */
    public function getTipProduct(): ?Product
    {
        return $this->tipProduct;
    }

    /**
     * @param \App\Model\Product\Product|null $tipProduct
     */
    public function setTipProduct(?Product $tipProduct): void
    {
        $this->tipProduct = $tipProduct;
    }
}
