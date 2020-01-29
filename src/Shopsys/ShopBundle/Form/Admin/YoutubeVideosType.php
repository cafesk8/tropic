<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class YoutubeVideosType extends AbstractType
{
    /**
     * @return string|null
     */
    public function getParent()
    {
        return CollectionType::class;
    }
}
