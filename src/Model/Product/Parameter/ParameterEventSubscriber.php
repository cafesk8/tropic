<?php

declare(strict_types=1);

namespace App\Model\Product\Parameter;

use App\Model\Category\CategoryFacade;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ParameterEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var \App\Model\Category\CategoryFacade
     */
    private $categoryFacade;

    /**
     * @param \App\Model\Category\CategoryFacade $categoryFacade
     */
    public function __construct(CategoryFacade $categoryFacade)
    {
        $this->categoryFacade = $categoryFacade;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterEvent $parameterEvent
     */
    public function parameterCreated(ParameterEvent $parameterEvent): void
    {
        /** @var \App\Model\Product\Parameter\Parameter $parameter */
        $parameter = $parameterEvent->getParameter();
        $this->categoryFacade->addParameterToAllCategories($parameter);
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            ParameterEvent::CREATE => 'parameterCreated',
        ];
    }
}
