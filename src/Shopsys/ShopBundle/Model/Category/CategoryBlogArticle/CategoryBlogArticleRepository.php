<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Category\CategoryBlogArticle;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Shopsys\ShopBundle\Model\Category\Category;

class CategoryBlogArticleRepository
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    private function getCategoryBlogArticleRepository(): EntityRepository
    {
        return $this->em->getRepository(CategoryBlogArticle::class);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Category\Category $category
     * @param int $domainId
     * @return \Shopsys\ShopBundle\Model\Category\CategoryBlogArticle\CategoryBlogArticle[]
     */
    public function getVisibleByCategoryAndDomainId(Category $category, int $domainId): array
    {
        return $this->getCategoryBlogArticleRepository()
            ->createQueryBuilder('cba')
            ->select('cba')
            ->join('cba.blogArticle', 'ba')
            ->join('ba.domains', 'bad')
            ->where('bad.domainId = :domainId')
            ->andWhere('cba.category = :category')
            ->andWhere('bad.visible = TRUE')
            ->orderBy('cba.position')
            ->setParameter('domainId', $domainId)
            ->setParameter('category', $category)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Category\Category $category
     * @return \Shopsys\ShopBundle\Model\Category\CategoryBlogArticle\CategoryBlogArticle[]
     */
    public function getAllByCategory(Category $category): array
    {
        return $this->getCategoryBlogArticleRepository()->findBy(['category' => $category], ['position' => 'ASC']);
    }
}
