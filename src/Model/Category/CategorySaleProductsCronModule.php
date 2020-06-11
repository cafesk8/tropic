<?php

declare(strict_types=1);

namespace App\Model\Category;

use Shopsys\Plugin\Cron\SimpleCronModuleInterface;
use Symfony\Bridge\Monolog\Logger;

class CategorySaleProductsCronModule implements SimpleCronModuleInterface
{
    /**
     * @var \App\Model\Category\CategoryFacade
     */
    protected $categoryFacade;

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
    }
}
