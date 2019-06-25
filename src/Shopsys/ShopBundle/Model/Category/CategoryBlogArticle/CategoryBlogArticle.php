<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Category\CategoryBlogArticle;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\ShopBundle\Model\Blog\Article\BlogArticle;
use Shopsys\ShopBundle\Model\Category\Category;

/**
 * @ORM\Table(name="category_blog_articles")
 * @ORM\Entity
 */
class CategoryBlogArticle
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Category\Category
     *
     * @ORM\ManyToOne(targetEntity="Shopsys\FrameworkBundle\Model\Category\Category")
     * @ORM\JoinColumn(nullable=false, name="category_id", referencedColumnName="id", onDelete="CASCADE")
     * @ORM\Id
     */
    private $category;

    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Article\BlogArticle
     *
     * @ORM\ManyToOne(targetEntity="Shopsys\ShopBundle\Model\Blog\Article\BlogArticle")
     * @ORM\JoinColumn(nullable=false, name="blog_article_id", referencedColumnName="id", onDelete="CASCADE")
     * @ORM\Id
     */
    private $blogArticle;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $position;

    /**
     * @param \Shopsys\ShopBundle\Model\Category\Category $category
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticle $blogArticle
     * @param int $position
     */
    public function __construct(Category $category, BlogArticle $blogArticle, int $position)
    {
        $this->category = $category;
        $this->blogArticle = $blogArticle;
        $this->position = $position;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Blog\Article\BlogArticle
     */
    public function getBlogArticle(): BlogArticle
    {
        return $this->blogArticle;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Category\Category
     */
    public function getCategory(): Category
    {
        return $this->category;
    }

    /**
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position;
    }
}
