<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\MassEdit;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class MassEditFormFactory
{
    /**
     * @var \Symfony\Component\Form\FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\MassEdit\MassEditActionProvider
     */
    private $massEditActionProvider;

    /**
     * @param \Symfony\Component\Form\FormFactoryInterface $formFactory
     * @param \Shopsys\ShopBundle\Model\Product\MassEdit\MassEditActionProvider $massEditActionProvider
     */
    public function __construct(FormFactoryInterface $formFactory, MassEditActionProvider $massEditActionProvider)
    {
        $this->formFactory = $formFactory;
        $this->massEditActionProvider = $massEditActionProvider;
    }

    /**
     * @param string $name
     * @param array $ruleViewData
     * @return \Symfony\Component\Form\FormInterface
     */
    public function createMassEditForm(string $name, array $ruleViewData): FormInterface
    {
        $options = [
            'csrf_protection' => false,
            'attr' => ['novalidate' => 'novalidate'],
        ];
        $formBuilder = $this->formFactory->createNamedBuilder($name, FormType::class, null, $options);

        $selectedAction = $this->massEditActionProvider->getAction($ruleViewData['subject']);
        $selectedOperation = array_key_exists('operation', $ruleViewData) ? array_keys($selectedAction->getOperations())[0] : $ruleViewData['operation'];

        $formBuilder
            ->add('selectType', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    t('Only checked products') => MassEditFacade::SELECT_TYPE_CHECKED,
                    t('All search results') => MassEditFacade::SELECT_TYPE_ALL_RESULTS,
                ],
            ])
            ->add('subject', ChoiceType::class, [
                'choices' => $this->getSubjectChoices(),
                'expanded' => false,
                'multiple' => false,
            ])
            ->add('operation', ChoiceType::class, [
                'choices' => $this->getOperationChoices($selectedAction),
                'expanded' => false,
                'multiple' => false,
            ]);

        $valueFormType = $selectedAction->getValueFormType($selectedOperation);
        $valueFormOptions = $selectedAction->getValueFormOptions($selectedOperation);

        if (is_array($valueFormType)) {
            foreach ($valueFormType as $inputName => $inputType) {
                $inputOptions = array_key_exists($inputName, $valueFormOptions) ? $valueFormOptions[$inputName] : [];
                $formBuilder->add(
                    $inputName,
                    $inputType,
                    $inputOptions
                );
            }
        } else {
            $formBuilder->add(
                'value',
                $valueFormType,
                $valueFormOptions
            );
        }

        $formBuilder->add('submit', SubmitType::class);

        $form = $formBuilder->getForm();
        $form->submit($ruleViewData);

        return $form;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\MassEdit\MassEditActionInterface $action
     * @return array
     */
    private function getSubjectChoices(): array
    {
        $choices = [];

        foreach ($this->massEditActionProvider->getAllActions() as $action) {
            $choices[$action->getLabel()] = $action->getName();
        }

        return $choices;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\MassEdit\MassEditActionInterface $action
     * @return array
     */
    private function getOperationChoices(MassEditActionInterface $action): array
    {
        $choices = [];

        foreach ($action->getOperations() as $name => $label) {
            $choices[$label] = $name;
        }

        return $choices;
    }
}
