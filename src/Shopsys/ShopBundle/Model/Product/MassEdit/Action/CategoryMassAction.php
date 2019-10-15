<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\MassEdit\Action;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Internal\Hydration\IterableResult;
use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Category\CategoryFacade;
use Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomainFactoryInterface;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade;
use Shopsys\ShopBundle\Model\Category\Category;
use Shopsys\ShopBundle\Model\Category\CategoryRepository;
use Shopsys\ShopBundle\Model\Product\MassEdit\MassEditActionInterface;
use Shopsys\ShopBundle\Model\Product\Product;
use Shopsys\ShopBundle\Model\Product\ProductFacade;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class CategoryMassAction implements MassEditActionInterface
{
    private const NAME = 'category';

    private const OPERATION_ADD = 'add';
    private const OPERATION_REMOVE = 'remove';
    private const OPERATION_SET = 'set';

    /**
     * @var \Shopsys\ShopBundle\Model\Category\CategoryFacade
     */
    private $categoryFacade;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomainFactoryInterface
     */
    private $productCategoryDomainFactory;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade
     */
    protected $productVisibilityFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Category\CategoryRepository
     */
    private $categoryRepository;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Category\CategoryFacade $categoryFacade
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomainFactoryInterface $productCategoryDomainFactory
     * @param \Shopsys\ShopBundle\Model\Product\ProductFacade $productFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade $productVisibilityFacade
     * @param \Shopsys\ShopBundle\Model\Category\CategoryRepository $categoryRepository
     */
    public function __construct(
        CategoryFacade $categoryFacade,
        EntityManagerInterface $entityManager,
        Domain $domain,
        ProductCategoryDomainFactoryInterface $productCategoryDomainFactory,
        ProductFacade $productFacade,
        ProductVisibilityFacade $productVisibilityFacade,
        CategoryRepository $categoryRepository
    ) {
        $this->categoryFacade = $categoryFacade;
        $this->entityManager = $entityManager;
        $this->domain = $domain;
        $this->productCategoryDomainFactory = $productCategoryDomainFactory;
        $this->productFacade = $productFacade;
        $this->productVisibilityFacade = $productVisibilityFacade;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return t('Kategorie');
    }

    /**
     * @inheritdoc
     */
    public function getOperations(): array
    {
        return [
            self::OPERATION_ADD => t('Přidat'),
            self::OPERATION_REMOVE => t('Odebrat'),
            self::OPERATION_SET => t('Nastavit'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getValueFormType(string $operation): string
    {
        return ChoiceType::class;
    }

    /**
     * @inheritdoc
     */
    public function getValueFormOptions(string $operation): array
    {
        $categories = $this->categoryFacade->getAll();

        return [
            'required' => true,
            'choices' => $categories,
            'choice_label' => 'nameWithLevelPad',
            'choice_value' => 'id',
        ];
    }

    /**
     * @inheritdoc
     */
    public function perform(QueryBuilder $selectedProductsQueryBuilder, string $operation, $value): void
    {
        $productsIterableResult = $selectedProductsQueryBuilder
            ->select('p')
            ->distinct()
            ->getQuery()->iterate();

        switch ($operation) {
            case self::OPERATION_ADD:
                $this->performOperationAdd($productsIterableResult, $value);
                break;
            case self::OPERATION_REMOVE:
                $this->performOperationRemove($productsIterableResult, $value);
                break;
            case self::OPERATION_SET:
                $this->performOperationSet($productsIterableResult, $value);
                break;
        }
    }

    /**
     * @param \Doctrine\ORM\Internal\Hydration\IterableResult $productsIterableResult
     * @param mixed $value
     */
    private function performOperationAdd(IterableResult $productsIterableResult, $value): void
    {
        foreach ($productsIterableResult as $row) {
            /** @var \Shopsys\ShopBundle\Model\Product\Product $product */
            $product = $row[0];
            $this->addCategoryToProduct($product, $value);
            $product->markForVisibilityRecalculation();
        }
        $this->entityManager->flush();
        $this->productVisibilityFacade->refreshProductsVisibilityForMarkedDelayed();
    }

    /**
     * @param \Doctrine\ORM\Internal\Hydration\IterableResult $productsIterableResult
     * @param mixed $value
     */
    private function performOperationRemove(IterableResult $productsIterableResult, $value): void
    {
        foreach ($productsIterableResult as $row) {
            /** @var \Shopsys\ShopBundle\Model\Product\Product $product */
            $product = $row[0];
            $this->removeCategoryFromProduct($product, $value);
            $product->markForVisibilityRecalculation();
        }
        $this->entityManager->flush();
        $this->productVisibilityFacade->refreshProductsVisibilityForMarkedDelayed();
    }

    /**
     * @param \Doctrine\ORM\Internal\Hydration\IterableResult $productsIterableResult
     * @param mixed $value
     */
    private function performOperationSet(IterableResult $productsIterableResult, $value): void
    {
        foreach ($productsIterableResult as $row) {
            /** @var \Shopsys\ShopBundle\Model\Product\Product $product */
            $product = $row[0];
            $this->setCategoryToProduct($product, $value);
            $product->markForVisibilityRecalculation();
        }
        $this->entityManager->flush();
        $this->productVisibilityFacade->refreshProductsVisibilityForMarkedDelayed();
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param \Shopsys\ShopBundle\Model\Category\Category $category
     */
    private function addCategoryToProduct(Product $product, Category $category): void
    {
        $categoriesByDomainId = $product->getCategoriesIndexedByDomainId();
        $path = $this->categoryRepository->getPath($category);

        foreach ($this->domain->getAllIds() as $domainId) {
            if (array_key_exists($domainId, $categoriesByDomainId) === false
                || in_array($category, $categoriesByDomainId[$domainId], true) === false
            ) {
                $categoriesByDomainId[$domainId][] = $category;
            }

            // add category parents too
            foreach ($path as $parentCategory) {
                if (in_array($parentCategory, $categoriesByDomainId[$domainId], true) === false) {
                    $categoriesByDomainId[$domainId][] = $parentCategory;
                }
            }
        }

        $product->editCategoriesByDomainId($this->productCategoryDomainFactory, $categoriesByDomainId);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param \Shopsys\ShopBundle\Model\Category\Category $category
     */
    private function removeCategoryFromProduct(Product $product, Category $category): void
    {
        $this->productFacade->removeProductCategoryDomainByProductAndCategory($product, $category);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param \Shopsys\ShopBundle\Model\Category\Category $category
     */
    private function setCategoryToProduct(Product $product, Category $category): void
    {
        $categoriesByDomainId = $product->getCategoriesIndexedByDomainId();
        foreach ($this->domain->getAllIds() as $domainId) {
            $categoriesByDomainId[$domainId][] = $category;
            $product->editCategoriesByDomainId($this->productCategoryDomainFactory, $categoriesByDomainId);
        }
    }
}
