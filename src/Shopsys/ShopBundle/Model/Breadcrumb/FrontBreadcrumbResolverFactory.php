<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Breadcrumb;

use Shopsys\FrameworkBundle\Model\Article\ArticleBreadcrumbGenerator;
use Shopsys\FrameworkBundle\Model\Breadcrumb\ErrorPageBreadcrumbGenerator;
use Shopsys\FrameworkBundle\Model\Breadcrumb\FrontBreadcrumbResolverFactory as BaseFrontBreadcrumbResolverFactory;
use Shopsys\FrameworkBundle\Model\Breadcrumb\SimpleBreadcrumbGenerator;
use Shopsys\FrameworkBundle\Model\Category\CategoryBreadcrumbGenerator;
use Shopsys\FrameworkBundle\Model\PersonalData\PersonalDataBreadcrumbGenerator;
use Shopsys\FrameworkBundle\Model\Product\Brand\BrandBreadcrumbGenerator;
use Shopsys\FrameworkBundle\Model\Product\ProductBreadcrumbGenerator;
use Shopsys\ShopBundle\Model\Blog\Article\BlogArticleBreadcrumbGenerator;
use Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryBreadcrumbGenerator;
use Shopsys\ShopBundle\Model\Store\StoreBreadcrumbGenerator;

class FrontBreadcrumbResolverFactory extends BaseFrontBreadcrumbResolverFactory
{
    /**
     * @param \Shopsys\FrameworkBundle\Model\Article\ArticleBreadcrumbGenerator $articleBreadcrumbGenerator
     * @param \Shopsys\FrameworkBundle\Model\Category\CategoryBreadcrumbGenerator $categoryBreadcrumbGenerator
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductBreadcrumbGenerator $productBreadcrumbGenerator
     * @param \Shopsys\FrameworkBundle\Model\Breadcrumb\SimpleBreadcrumbGenerator $frontBreadcrumbGenerator
     * @param \Shopsys\FrameworkBundle\Model\Product\Brand\BrandBreadcrumbGenerator $brandBreadcrumbGenerator
     * @param \Shopsys\FrameworkBundle\Model\Breadcrumb\ErrorPageBreadcrumbGenerator $errorPageBreadcrumbGenerator
     * @param \Shopsys\FrameworkBundle\Model\PersonalData\PersonalDataBreadcrumbGenerator $personalDataBreadcrumbGenerator
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryBreadcrumbGenerator $blogCategoryBreadcrumbGenerator
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleBreadcrumbGenerator $blogArticleBreadcrumbGenerator
     * @param \Shopsys\ShopBundle\Model\Store\StoreBreadcrumbGenerator $storeBreadcrumbGenerator
     */
    public function __construct(
        ArticleBreadcrumbGenerator $articleBreadcrumbGenerator,
        CategoryBreadcrumbGenerator $categoryBreadcrumbGenerator,
        ProductBreadcrumbGenerator $productBreadcrumbGenerator,
        SimpleBreadcrumbGenerator $frontBreadcrumbGenerator,
        BrandBreadcrumbGenerator $brandBreadcrumbGenerator,
        ErrorPageBreadcrumbGenerator $errorPageBreadcrumbGenerator,
        PersonalDataBreadcrumbGenerator $personalDataBreadcrumbGenerator,
        BlogCategoryBreadcrumbGenerator $blogCategoryBreadcrumbGenerator,
        BlogArticleBreadcrumbGenerator $blogArticleBreadcrumbGenerator,
        StoreBreadcrumbGenerator $storeBreadcrumbGenerator
    ) {
        parent::__construct(
            $articleBreadcrumbGenerator,
            $categoryBreadcrumbGenerator,
            $productBreadcrumbGenerator,
            $frontBreadcrumbGenerator,
            $brandBreadcrumbGenerator,
            $errorPageBreadcrumbGenerator,
            $personalDataBreadcrumbGenerator
        );

        $this->breadcrumbGenerators[] = $blogCategoryBreadcrumbGenerator;
        $this->breadcrumbGenerators[] = $blogArticleBreadcrumbGenerator;
        $this->breadcrumbGenerators[] = $storeBreadcrumbGenerator;
    }
}
