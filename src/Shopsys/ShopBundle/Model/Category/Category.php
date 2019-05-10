<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Category;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Shopsys\FrameworkBundle\Model\Category\Category as BaseCategory;
use Shopsys\FrameworkBundle\Model\Category\CategoryData as BaseCategoryData;

/**
 * @Gedmo\Tree(type="nested")
 * @ORM\Table(name="categories")
 * @ORM\Entity
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
    protected $preListingCategory;

    /**
     * @param \Shopsys\ShopBundle\Model\Category\CategoryData $categoryData
     */
    public function __construct(BaseCategoryData $categoryData)
    {
        parent::__construct($categoryData);

        $this->displayedInHorizontalMenu = $categoryData->displayedInHorizontalMenu;
        $this->preListingCategory = $categoryData->preListingCategory;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Category\CategoryData $categoryData
     */
    public function edit(BaseCategoryData $categoryData)
    {
        parent::edit($categoryData);

        $this->displayedInHorizontalMenu = $categoryData->displayedInHorizontalMenu;
        $this->preListingCategory = $categoryData->preListingCategory;
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
}
