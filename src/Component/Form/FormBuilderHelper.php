<?php

declare(strict_types=1);

namespace App\Component\Form;

use Symfony\Component\Form\FormBuilderInterface;

class FormBuilderHelper
{
    /**
     * @var bool
     */
    private $disableFields;

    /**
     * @param bool $disableFields
     */
    public function __construct(bool $disableFields)
    {
        $this->disableFields = $disableFields;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $disabledFields
     */
    public function disableFieldsByConfigurations(FormBuilderInterface $builder, array $disabledFields): void
    {
        if (!$this->disableFields) {
            return;
        }

        $this->walkFormElements($builder->all(), $disabledFields);
    }

    /**
     * @param array $elements
     * @param array $disabledFields
     * @param string $path
     */
    private function walkFormElements(array $elements, array $disabledFields, string $path = ''): void
    {
        foreach ($elements as $element) {
            $currentPath = empty($path) ? $element->getName() : $path . '.' . $element->getName();

            /** @var \Ivory\OrderedForm\Builder\OrderedFormBuilder $element */
            if (in_array($currentPath, $disabledFields, true)) {
                $element->setDisabled(true);
            }

            $this->walkFormElements($element->all(), $disabledFields, $currentPath);
        }
    }

    /**
     * @return bool
     */
    public function getDisableFields(): bool
    {
        return $this->disableFields;
    }
}
