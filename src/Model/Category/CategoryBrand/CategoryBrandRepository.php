<?php

declare(strict_types=1);

namespace App\Model\Category\CategoryBrand;

use App\Model\Category\Category;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class CategoryBrandRepository
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
    private function getCategoryBrandRepository(): EntityRepository
    {
        return $this->em->getRepository(CategoryBrand::class);
    }

    /**
     * @param \App\Model\Category\Category $category
     * @return \App\Model\Category\CategoryBrand\CategoryBrand[]
     */
    public function getAllByCategory(Category $category): array
    {
        return $this->getCategoryBrandRepository()->findBy(['category' => $category], ['priority' => 'ASC']);
    }
}