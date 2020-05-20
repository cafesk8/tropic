<?php

declare(strict_types=1);

namespace App\Model\Product\MassEdit;

use App\Model\Product\MassEdit\Action\CategoryMassAction;
use App\Model\Product\MassEdit\Action\HiddenMassAction;
use App\Model\Product\MassEdit\Action\ProductParameterMassAction;

class MassEditActionProvider
{
    /**
     * @var \App\Model\Product\MassEdit\MassEditActionInterface[]
     */
    private $actions = [];

    /**
     * @param \App\Model\Product\MassEdit\Action\HiddenMassAction $hiddenMassAction
     * @param \App\Model\Product\MassEdit\Action\CategoryMassAction $categoryMassAction
     * @param \App\Model\Product\MassEdit\Action\ProductParameterMassAction $productParameterMassAction
     * @throws \App\Model\Product\MassEdit\Exception\MassEditActionAlreadyExistsException
     */
    public function __construct(
        HiddenMassAction $hiddenMassAction,
        CategoryMassAction $categoryMassAction,
        ProductParameterMassAction $productParameterMassAction
    ) {
        $this->registerAction($hiddenMassAction);
        $this->registerAction($categoryMassAction);
        $this->registerAction($productParameterMassAction);
    }

    /**
     * @param \App\Model\Product\MassEdit\MassEditActionInterface $action
     */
    public function registerAction(MassEditActionInterface $action): void
    {
        if (array_key_exists($action->getName(), $this->actions)) {
            $message = 'Action "' . $action->getName() . '" already exists.';
            throw new \App\Model\Product\MassEdit\Exception\MassEditActionAlreadyExistsException($message);
        }

        $this->actions[$action->getName()] = $action;
    }

    /**
     * @return \App\Model\Product\MassEdit\MassEditActionInterface[]
     */
    public function getAllActions(): array
    {
        return $this->actions;
    }

    /**
     * @param string $actionName
     * @return \App\Model\Product\MassEdit\MassEditActionInterface
     */
    public function getAction($actionName): MassEditActionInterface
    {
        if (!array_key_exists($actionName, $this->actions)) {
            $message = 'Action "' . $actionName . '" not found.';
            throw new \App\Model\Product\MassEdit\Exception\MassEditActionNotFoundException($message);
        }

        return $this->actions[$actionName];
    }
}
