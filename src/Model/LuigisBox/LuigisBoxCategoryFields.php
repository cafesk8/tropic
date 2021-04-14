<?php

declare(strict_types=1);

namespace App\Model\LuigisBox;

class LuigisBoxCategoryFields extends LuigisBoxObjectFields
{
    /**
     * @var \App\Model\LuigisBox\LuigisBoxObject[]
     */
    public array $ancestors;

    public ?string $image_link;
}