<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\ContactForm;

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
