<?php

declare(strict_types=1);

namespace App\Command;

use App\Model\Category\CategoryFacade;
use Shopsys\FrameworkBundle\Command\RecalculationsCommand as BaseRecalculationsCommand;
use Shopsys\FrameworkBundle\Model\Category\CategoryVisibilityRepository;
use Shopsys\FrameworkBundle\Model\Product\Availability\ProductAvailabilityRecalculator;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceRecalculator;
use Shopsys\FrameworkBundle\Model\Product\ProductHiddenRecalculator;
use Shopsys\FrameworkBundle\Model\Product\ProductSellingDeniedRecalculator;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @property \App\Model\Product\ProductSellingDeniedRecalculator $productSellingDeniedRecalculator
 * @property \App\Model\Product\ProductHiddenRecalculator $productHiddenRecalculator
 */
class RecalculationsCommand extends BaseRecalculationsCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'shopsys:recalculations';

    /**
     * @var \App\Model\Category\CategoryFacade
     */
    private $categoryFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Category\CategoryVisibilityRepository
     */
    private $categoryVisibilityRepository;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Category\CategoryVisibilityRepository $categoryVisibilityRepository
     * @param \App\Model\Product\ProductHiddenRecalculator $productHiddenRecalculator
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceRecalculator $productPriceRecalculator
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade $productVisibilityFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Availability\ProductAvailabilityRecalculator $productAvailabilityRecalculator
     * @param \App\Model\Product\ProductSellingDeniedRecalculator $productSellingDeniedRecalculator
     * @param \App\Model\Category\CategoryFacade $categoryFacade
     */
    public function __construct(
        CategoryVisibilityRepository $categoryVisibilityRepository,
        ProductHiddenRecalculator $productHiddenRecalculator,
        ProductPriceRecalculator $productPriceRecalculator,
        ProductVisibilityFacade $productVisibilityFacade,
        ProductAvailabilityRecalculator $productAvailabilityRecalculator,
        ProductSellingDeniedRecalculator $productSellingDeniedRecalculator,
        CategoryFacade $categoryFacade
    ) {
        parent::__construct($categoryVisibilityRepository, $productHiddenRecalculator, $productPriceRecalculator, $productVisibilityFacade, $productAvailabilityRecalculator, $productSellingDeniedRecalculator);
        $this->categoryVisibilityRepository = $categoryVisibilityRepository;
        $this->categoryFacade = $categoryFacade;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $output->writeln('<fg=green>Sale category visibility.</fg=green>');
        $this->categoryFacade->refreshSaleCategoryVisibility();
        $this->categoryVisibilityRepository->refreshCategoriesVisibility();

        return 0;
    }
}
