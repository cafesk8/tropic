<?php

namespace Shopsys\ShopBundle\DataFixtures\Demo;

use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\ShopBundle\Model\Blog\Article\BlogArticleDataFactory;
use Shopsys\ShopBundle\Model\Blog\Article\BlogArticleFacade;
use Shopsys\ShopBundle\Model\Blog\BlogVisibilityFacade;
use Shopsys\ShopBundle\Model\Blog\Category\BlogCategory;
use Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryFacade;

class BlogArticleDataFixture extends AbstractReferenceFixture
{
    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleFacade
     */
    private $blogArticleFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleDataFactory
     */
    private $blogArticleDataFactory;

    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryFacade
     */
    private $blogCategoryFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \Shopsys\ShopBundle\Model\Blog\BlogVisibilityFacade
     */
    private $blogVisibilityFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleFacade $blogArticleFacade
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleDataFactory $blogArticleDataFactory
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryFacade $blogCategoryFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\ShopBundle\Model\Blog\BlogVisibilityFacade $blogVisibilityFacade
     */
    public function __construct(
        BlogArticleFacade $blogArticleFacade,
        BlogArticleDataFactory $blogArticleDataFactory,
        BlogCategoryFacade $blogCategoryFacade,
        Domain $domain,
        BlogVisibilityFacade $blogVisibilityFacade
    ) {
        $this->blogArticleFacade = $blogArticleFacade;
        $this->blogArticleDataFactory = $blogArticleDataFactory;
        $this->blogCategoryFacade = $blogCategoryFacade;
        $this->domain = $domain;
        $this->blogVisibilityFacade = $blogVisibilityFacade;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $mainPageBlogCategory = $this->blogCategoryFacade->getById(BlogCategory::BLOG_MAIN_PAGE_CATEGORY_ID);

        $blogArticleData = $this->blogArticleDataFactory->create();

        foreach ($this->domain->getAll() as $domain) {
            $blogArticleData->blogCategoriesByDomainId[$domain->getId()] = [$mainPageBlogCategory];
        }

        foreach ($this->domain->getAllLocales() as $locale) {
            $blogArticleData->names[$locale] = 'Ukázkový článek blogu ' . $locale;
            $blogArticleData->descriptions[$locale] = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus felis nisi, tincidunt sollicitudin augue eu, laoreet blandit sem. Donec rutrum augue a elit imperdiet, eu vehicula tortor porta. Vivamus pulvinar sem non auctor dictum. Morbi eleifend semper enim, eu faucibus tortor posuere vitae. Donec tincidunt ipsum ullamcorper nisi accumsan tincidunt. Aenean sed velit massa. Nullam interdum eget est ut convallis. Vestibulum et mauris condimentum, rutrum sem congue, suscipit arcu.\nSed tristique vehicula ipsum, ut vulputate tortor feugiat eu. Vivamus convallis quam vulputate faucibus facilisis. Curabitur tincidunt pulvinar leo, eu dapibus augue lacinia a. Fusce sed tincidunt nunc. Morbi a nisi a odio pharetra laoreet nec eget quam. In in nisl tortor. Ut fringilla vitae lectus eu venenatis. Nullam interdum sed odio a posuere. Fusce pellentesque dui vel tortor blandit, a dictum nunc congue.';
            $blogArticleData->perexes[$locale] = $locale . ' perex lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus felis nisi, tincidunt sollicitudin augue eu.';
        }

        $this->blogArticleFacade->create($blogArticleData);

        $this->blogVisibilityFacade->refreshBlogArticlesVisibility();
    }
}
