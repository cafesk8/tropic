<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Model\Category\Category;
use App\Model\Category\CategoryBrand\CategoryBrandRepository;
use App\Model\Product\Brand\BrandFacade;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class CategoryBrandFormType extends AbstractType
{
    public const NUMBER_OF_PRIORITIZED_BRANDS = 4;

    private BrandFacade $brandFacade;

    private CategoryBrandRepository $categoryBrandRepository;

    /**
     * @param \App\Model\Product\Brand\BrandFacade $brandFacade
     * @param \App\Model\Category\CategoryBrand\CategoryBrandRepository $categoryBrandRepository
     */
    public function __construct(BrandFacade $brandFacade, CategoryBrandRepository $categoryBrandRepository)
    {
        $this->brandFacade = $brandFacade;
        $this->categoryBrandRepository = $categoryBrandRepository;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $allBrands = $this->brandFacade->getAll();
        /** @var \App\Model\Category\Category|null $category */
        $category = $options['category'];
        $categoryBrands = $category !== null ? $this->categoryBrandRepository->getAllByCategory($category) : null;

        foreach(range(1, self::NUMBER_OF_PRIORITIZED_BRANDS) as $index => $priority) {
            $builder->add($priority, ChoiceType::class, [
                'choices' => $allBrands,
                'choice_label' => 'name',
                'choice_value' => 'name',
                'required' => false,
                'label' => t('Pozice') . ' ' . $priority,
                'data' => $categoryBrands[$index] ?? null,
            ]);
        }
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'category' => Category::class,
            'constraints' => [
                new Callback([$this, 'validateCategoryBrands']),
            ],
        ]);
    }

    /**
     * @param array|null $brands
     * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
     */
    public function validateCategoryBrands(?array $brands, ExecutionContextInterface $context): void
    {
        $filteredBrands = [];
        foreach($brands as $brand) {
            if ($brand !== null) {
                $filteredBrands[] = $brand->getId();
            }
        }

        $violationPathIds = array_diff_assoc($filteredBrands, array_unique($filteredBrands));

        if (!empty($violationPathIds)) {
            foreach(array_keys($violationPathIds) as $violationPathId) {
                $context->buildViolation('Vyberte unikátní značku')
                    ->atPath('[' . $violationPathId . ']')
                    ->addViolation();
            }
        }
    }

    /**
     * @return string|null
     */
    public function getParent(): ?string
    {
        return FormType::class;
    }
}
