<?php

declare(strict_types=1);

namespace App\Model\Category;

use Shopsys\Plugin\Cron\SimpleCronModuleInterface;
use Symfony\Bridge\Monolog\Logger;

class SpecialCategoriesCronModule implements SimpleCronModuleInterface
{
    protected CategoryFacade $categoryFacade;

    /**
     * @param \App\Model\Category\CategoryFacade $categoryFacade
     */
    public function __construct(CategoryFacade $categoryFacade)
    {
        $this->categoryFacade = $categoryFacade;
    }

    /**
     * @inheritdoc
     */
    public function setLogger(Logger $logger)
    {
    }

    public function run()
    {
        $this->categoryFacade->markSaleCategories();
        $this->categoryFacade->markNewsCategories();
    }
}
