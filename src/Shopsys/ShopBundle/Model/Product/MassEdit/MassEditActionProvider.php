<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\MassEdit;

use Shopsys\ShopBundle\Model\Product\MassEdit\Action\CategoryMassAction;
use Shopsys\ShopBundle\Model\Product\MassEdit\Action\FlagsMassAction;
use Shopsys\ShopBundle\Model\Product\MassEdit\Action\HiddenMassAction;
use Shopsys\ShopBundle\Model\Product\MassEdit\Action\ProductParameterMassAction;

class MassEditActionProvider
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\MassEdit\MassEditActionInterface[]
     */
    private $actions = [];

    /**
     * @param \Shopsys\ShopBundle\Model\Product\MassEdit\Action\HiddenMassAction $hiddenMassAction
     * @param \Shopsys\ShopBundle\Model\Product\MassEdit\Action\FlagsMassAction $flagsMassAction
     * @param \Shopsys\ShopBundle\Model\Product\MassEdit\Action\CategoryMassAction $categoryMassAction
     * @param \Shopsys\ShopBundle\Model\Product\MassEdit\Action\ProductParameterMassAction $productParameterMassAction
     * @throws \Shopsys\ShopBundle\Model\Product\MassEdit\Exception\MassEditActionAlreadyExistsException
     */
    public function __construct(
        HiddenMassAction $hiddenMassAction,
        FlagsMassAction $flagsMassAction,
        CategoryMassAction $categoryMassAction,
        ProductParameterMassAction $productParameterMassAction
    ) {
        $this->registerAction($hiddenMassAction);
        $this->registerAction($flagsMassAction);
        $this->registerAction($categoryMassAction);
        $this->registerAction($productParameterMassAction);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\MassEdit\MassEditActionInterface $action
     */
    public function registerAction(MassEditActionInterface $action): void
    {
        if (array_key_exists($action->getName(), $this->actions)) {
            $message = 'Action "' . $action->getName() . '" already exists.';
            throw new \Shopsys\ShopBundle\Model\Product\MassEdit\Exception\MassEditActionAlreadyExistsException($message);
        }

        $this->actions[$action->getName()] = $action;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Product\MassEdit\MassEditActionInterface[]
     */
    public function getAllActions(): array
    {
        return $this->actions;
    }

    /**
     * @param string $actionName
     * @return \Shopsys\ShopBundle\Model\Product\MassEdit\MassEditActionInterface
     */
    public function getAction($actionName): MassEditActionInterface
    {
        if (!array_key_exists($actionName, $this->actions)) {
            $message = 'Action "' . $actionName . '" not found.';
            throw new \Shopsys\ShopBundle\Model\Product\MassEdit\Exception\MassEditActionNotFoundException($message);
        }

        return $this->actions[$actionName];
    }
}
