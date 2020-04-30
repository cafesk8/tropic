<?php

declare(strict_types=1);

namespace Tests\App\Functional\Model\Category;

use App\Model\Category\Category;
use Shopsys\FrameworkBundle\Model\Category\CategoryDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Category\CategoryFactoryInterface;
use Tests\App\Test\TransactionFunctionalTestCase;

class CategoryDomainTest extends TransactionFunctionalTestCase
{
    public const FIRST_DOMAIN_ID = 1;
    public const SECOND_DOMAIN_ID = 2;
    public const DEMONSTRATIVE_SEO_TITLE = 'Demonstrative seo title';
    public const DEMONSTRATIVE_SEO_META_DESCRIPTION = 'Demonstrative seo description';
    public const DEMONSTRATIVE_SEO_H1 = 'Demonstrative seo H1';

    /**
     * @var \App\Model\Category\CategoryDataFactory
     */
    private $categoryDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Category\CategoryFactory
     */
    private $categoryFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator
     */
    private $em;

    public function setUp(): void
    {
        parent::setUp();
        $this->categoryDataFactory = $this->getContainer()->get(CategoryDataFactoryInterface::class);
        $this->categoryFactory = $this->getContainer()->get(CategoryFactoryInterface::class);
        $this->em = $this->getEntityManager();
    }

    public function testCreateCategoryEnabledOnDomain()
    {
        $categoryData = $this->categoryDataFactory->create();

        $categoryData->enabled[self::FIRST_DOMAIN_ID] = true;

        $category = $this->categoryFactory->create($categoryData, null);

        $refreshedCategory = $this->getRefreshedCategoryFromDatabase($category);

        $this->assertTrue($refreshedCategory->isEnabled(self::FIRST_DOMAIN_ID));
    }

    public function testCreateCategoryDisabledOnDomain()
    {
        $categoryData = $this->categoryDataFactory->create();

        $categoryData->enabled[self::FIRST_DOMAIN_ID] = false;

        $category = $this->categoryFactory->create($categoryData, null);

        $refreshedCategory = $this->getRefreshedCategoryFromDatabase($category);

        $this->assertFalse($refreshedCategory->isEnabled(self::FIRST_DOMAIN_ID));
    }

    /**
     * @group multidomain
     */
    public function testCreateCategoryWithDifferentVisibilityOnDomains()
    {
        $categoryData = $this->categoryDataFactory->create();

        $categoryData->enabled[self::FIRST_DOMAIN_ID] = true;
        $categoryData->enabled[self::SECOND_DOMAIN_ID] = false;

        $category = $this->categoryFactory->create($categoryData, null);

        $refreshedCategory = $this->getRefreshedCategoryFromDatabase($category);

        $this->assertTrue($refreshedCategory->isEnabled(self::FIRST_DOMAIN_ID));
        $this->assertFalse($refreshedCategory->isEnabled(self::SECOND_DOMAIN_ID));
    }

    /**
     * @group multidomain
     */
    public function testCreateCategoryDomainWithData()
    {
        $categoryData = $this->categoryDataFactory->create();

        $categoryData->seoTitles[self::FIRST_DOMAIN_ID] = self::DEMONSTRATIVE_SEO_TITLE;
        $categoryData->seoMetaDescriptions[self::SECOND_DOMAIN_ID] = self::DEMONSTRATIVE_SEO_META_DESCRIPTION;
        $categoryData->seoH1s[self::FIRST_DOMAIN_ID] = self::DEMONSTRATIVE_SEO_H1;

        $category = $this->categoryFactory->create($categoryData, null);

        $refreshedCategory = $this->getRefreshedCategoryFromDatabase($category);

        $this->assertSame(self::DEMONSTRATIVE_SEO_TITLE, $refreshedCategory->getSeoTitle(self::FIRST_DOMAIN_ID));
        $this->assertNull($refreshedCategory->getSeoTitle(self::SECOND_DOMAIN_ID));
        $this->assertSame(self::DEMONSTRATIVE_SEO_META_DESCRIPTION, $refreshedCategory->getSeoMetaDescription(self::SECOND_DOMAIN_ID));
        $this->assertNull($refreshedCategory->getSeoMetaDescription(self::FIRST_DOMAIN_ID));
        $this->assertSame(self::DEMONSTRATIVE_SEO_H1, $refreshedCategory->getSeoH1(self::FIRST_DOMAIN_ID));
        $this->assertNull($refreshedCategory->getSeoH1(self::SECOND_DOMAIN_ID));
    }

    /**
     * @group singledomain
     */
    public function testCreateCategoryDomainWithDataForSingleDomain()
    {
        $categoryData = $this->categoryDataFactory->create();

        $categoryData->seoTitles[self::FIRST_DOMAIN_ID] = self::DEMONSTRATIVE_SEO_TITLE;
        $categoryData->seoMetaDescriptions[self::FIRST_DOMAIN_ID] = self::DEMONSTRATIVE_SEO_META_DESCRIPTION;
        $categoryData->seoH1s[self::FIRST_DOMAIN_ID] = self::DEMONSTRATIVE_SEO_H1;

        $category = $this->categoryFactory->create($categoryData, null);

        $refreshedCategory = $this->getRefreshedCategoryFromDatabase($category);

        $this->assertSame(self::DEMONSTRATIVE_SEO_TITLE, $refreshedCategory->getSeoTitle(self::FIRST_DOMAIN_ID));
        $this->assertSame(self::DEMONSTRATIVE_SEO_META_DESCRIPTION, $refreshedCategory->getSeoMetaDescription(self::FIRST_DOMAIN_ID));
        $this->assertSame(self::DEMONSTRATIVE_SEO_H1, $refreshedCategory->getSeoH1(self::FIRST_DOMAIN_ID));
    }

    /**
     * @param \App\Model\Category\Category $category
     * @return \App\Model\Category\Category
     */
    private function getRefreshedCategoryFromDatabase(Category $category)
    {
        $this->em->persist($category);
        $this->em->flush();

        $categoryId = $category->getId();

        $this->em->clear();

        return $this->em->getRepository(Category::class)->find($categoryId);
    }
}
