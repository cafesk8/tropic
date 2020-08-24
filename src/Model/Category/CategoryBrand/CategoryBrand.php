<?php

declare(strict_types=1);

namespace App\Model\Category\CategoryBrand;

use App\Model\Category\Category;
use App\Model\Product\Brand\Brand;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="category_brands")
 * @ORM\Entity
 */
class CategoryBrand
{
    /**
     * @var \App\Model\Category\Category
     *
     * @ORM\ManyToOne(targetEntity="App\Model\Category\Category", inversedBy="categoryBrands")
     * @ORM\JoinColumn(nullable=false, name="category_id", referencedColumnName="id", onDelete="CASCADE")
     * @ORM\Id
     */
    private $category;

    /**
     * @var \App\Model\Product\Brand\Brand
     *
     * @ORM\ManyToOne(targetEntity="App\Model\Product\Brand\Brand")
     * @ORM\JoinColumn(nullable=false, name="brand_id", referencedColumnName="id", onDelete="CASCADE")
     * @ORM\Id
     */
    private $brand;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $priority;

    /**
     * @param \App\Model\Category\Category $category
     * @param \App\Model\Product\Brand\Brand $brand
     * @param int $priority
     */
    public function __construct(Category $category, Brand $brand, int $priority)
    {
        $this->category = $category;
        $this->brand = $brand;
        $this->priority = $priority;
    }

    /**
     * @return \App\Model\Product\Brand\Brand
     */
    public function getBrand(): Brand
    {
        return $this->brand;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->getBrand()->getName();
    }

    /**
     * @return \App\Model\Category\Category
     */
    public function getCategory(): Category
    {
        return $this->category;
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }
}
