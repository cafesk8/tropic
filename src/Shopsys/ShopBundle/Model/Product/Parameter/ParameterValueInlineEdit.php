<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Parameter;

use Shopsys\FrameworkBundle\Component\Grid\InlineEdit\AbstractGridInlineEdit;
use Shopsys\ShopBundle\Component\DataObject\Exception\NotImplementedException;
use Shopsys\ShopBundle\Form\Admin\ParameterValueFormType;
use Symfony\Component\Form\FormFactoryInterface;

class ParameterValueInlineEdit extends AbstractGridInlineEdit
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade
     */
    private $parameterFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Parameter\ParameterValueDataFactory
     */
    private $parameterValueDataFactory;

    /**
     * @var \Symfony\Component\Form\FormFactoryInterface
     */
    private $formFactory;

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\ParameterValueGridFactory $parameterValueGridFactory
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade $parameterFacade
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\ParameterValueDataFactory $parameterValueDataFactory
     * @param \Symfony\Component\Form\FormFactoryInterface $formFactory
     */
    public function __construct(
        ParameterValueGridFactory $parameterValueGridFactory,
        ParameterFacade $parameterFacade,
        ParameterValueDataFactory $parameterValueDataFactory,
        FormFactoryInterface $formFactory
    ) {
        parent::__construct($parameterValueGridFactory);
        $this->parameterFacade = $parameterFacade;
        $this->parameterValueDataFactory = $parameterValueDataFactory;
        $this->formFactory = $formFactory;
    }

    /**
     * @inheritDoc
     */
    public function getForm($parameterValueId)
    {
        $parameterValue = $this->parameterFacade->getParameterValueById($parameterValueId);
        $parameterValueData = $this->parameterValueDataFactory->createFromParameterValue($parameterValue);

        return $this->formFactory->create(ParameterValueFormType::class, $parameterValueData);
    }

    /**
     * @inheritDoc
     */
    protected function editEntity($parameterValueId, $parameterValueData)
    {
        $parameterValue = $this->parameterFacade->getParameterValueById($parameterValueId);
        $this->parameterFacade->editParameterValue($parameterValue, $parameterValueData);
    }

    /**
     * @inheritDoc
     */
    protected function createEntityAndGetId($formData)
    {
        throw new NotImplementedException('`ParameterValueInlineEdit::createEntityAndGetId` not implemented yet');
    }
}
