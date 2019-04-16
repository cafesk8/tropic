<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Admin;

use Shopsys\ShopBundle\Model\Store\StoreFacade;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class StoreStockType extends AbstractType
{
    /**
     * @var \Shopsys\ShopBundle\Model\Store\StoreFacade
     */
    private $storeFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Store\StoreFacade $storeFacade
     */
    public function __construct(StoreFacade $storeFacade)
    {
        $this->storeFacade = $storeFacade;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($this->storeFacade->getAll() as $store) {
            $builder->add($store->getId(), IntegerType::class, [
                'required' => false,
                'invalid_message' => 'Please enter stock in correct format (positive number >= 0)',
                'constraints' => [
                    new GreaterThanOrEqual(['value' => 0]),
                ],
            ]);
        }
    }

    /**
     * @inheritDoc
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

        foreach ($this->storeFacade->getAll() as $store) {
            $view->vars['stores'][] = $store;
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
