<?php

declare(strict_types=1);

namespace App\Component\FlashMessage;

use Symfony\Component\Form\Form;

class ErrorExtractor
{
    /**
     * inspired by @see \Shopsys\FrameworkBundle\Component\FlashMessage\ErrorExtractor
     * @see https://github.com/shopsys/shopsys/pull/1776
     *
     * @param \Symfony\Component\Form\Form $form
     * @param array $errorFlashMessages
     * @return string[]
     */
    public function getAllErrorsAsArray(Form $form, array $errorFlashMessages): array
    {
        $errors = $errorFlashMessages;
        foreach ($form->getErrors(true) as $error) {
            /* @var $error \Symfony\Component\Form\FormError */
            $errors[] = $error->getMessage();
        }

        return $errors;
    }
}
