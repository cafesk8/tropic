<?php

declare(strict_types=1);

namespace App\Model\ContactForm;

use Shopsys\FrameworkBundle\Model\ContactForm\ContactFormData as BaseContactFormData;

class ContactFormData extends BaseContactFormData
{
    /**
     * @var string|null
     */
    public $surname;

    /**
     * @var string|null
     */
    public $telephone;
}
