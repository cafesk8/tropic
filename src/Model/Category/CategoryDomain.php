<?php

declare(strict_types = 1);

namespace App\Model\Category;

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
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $containsSaleProduct;

    /**
     * @param \App\Model\Category\Category $category
     * @param int $domainId
     */
    public function __construct(Category $category, $domainId)
    {
        parent::__construct($category, $domainId);
        $this->containsSaleProduct = false;
    }

    /**
     * @return bool
     */
    public function containsSaleProduct(): bool
    {
        return $this->containsSaleProduct;
    }
}
