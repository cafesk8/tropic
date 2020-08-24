<?php

declare(strict_types=1);

namespace App\Model\Category\CategoryBrand;

use App\Model\Product\Brand\Brand;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator;
use Shopsys\FrameworkBundle\Model\Category\Category;

class CategoryBrandFacade
{
    private EntityManagerDecorator $em;

    private CategoryBrandFactory $categoryBrandFactory;

    private CategoryBrandRepository $categoryBrandRepository;

    /**
     * @param \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
     * @param \App\Model\Category\CategoryBrand\CategoryBrandFactory $categoryBrandFactory
     * @param \App\Model\Category\CategoryBrand\CategoryBrandRepository $categoryBrandRepository
     */
    public function __construct(EntityManagerInterface $em, CategoryBrandFactory $categoryBrandFactory, CategoryBrandRepository $categoryBrandRepository)
    {
        $this->em = $em;
        $this->categoryBrandFactory = $categoryBrandFactory;
        $this->categoryBrandRepository = $categoryBrandRepository;
    }

    /**
     * @param \App\Model\Category\Category $category
     * @param \App\Model\Product\Brand\Brand[] $brands
     */
    public function saveCategoryBrandsToCategory(Category $category, array $brands): void
    {
        $oldCategoryBrands = $this->categoryBrandRepository->getAllByCategory($category);
        foreach ($oldCategoryBrands as $oldCategoryBrand) {
            $this->em->remove($oldCategoryBrand);
        }
        $this->em->flush($oldCategoryBrands);

        $brandsToSave = [];
        foreach($brands as $priority => $brand) {
            if ($brand instanceof Brand) {
                $categoryBrand = $this->categoryBrandFactory->create($category, $brand, $priority);
                $this->em->persist($categoryBrand);
                $brandsToSave[$priority] = $brand;
            }
        }
        $this->em->flush($brandsToSave);
    }
}
