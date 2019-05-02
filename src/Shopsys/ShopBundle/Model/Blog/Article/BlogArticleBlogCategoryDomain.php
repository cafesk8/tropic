<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Blog\Article;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\ShopBundle\Model\Blog\Category\BlogCategory;

/**
 * @ORM\Table(
 *     name="blog_article_blog_category_domains",
 *     indexes={@ORM\Index(columns={"blog_category_id", "domain_id"})}
 * )
 * @ORM\Entity
 */
class BlogArticleBlogCategoryDomain
{
    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Article\BlogArticle
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Shopsys\ShopBundle\Model\Blog\Article\BlogArticle", inversedBy="blogArticleBlogCategoryDomains")
     * @ORM\JoinColumn(name="blog_article_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $blogArticle;

    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Shopsys\ShopBundle\Model\Blog\Category\BlogCategory")
     * @ORM\JoinColumn(name="blog_category_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $blogCategory;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    private $domainId;

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticle $blogArticle
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory $blogCategory
     * @param int $domainId
     */
    public function __construct(BlogArticle $blogArticle, BlogCategory $blogCategory, int $domainId)
    {
        $this->blogArticle = $blogArticle;
        $this->blogCategory = $blogCategory;
        $this->domainId = $domainId;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory
     */
    public function getBlogCategory(): BlogCategory
    {
        return $this->blogCategory;
    }

    /**
     * @return int
     */
    public function getDomainId(): int
    {
        return $this->domainId;
    }
}
