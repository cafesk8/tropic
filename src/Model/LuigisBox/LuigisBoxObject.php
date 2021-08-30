<?php

declare(strict_types=1);

namespace App\Model\LuigisBox;

class LuigisBoxObject
{
    public string $url;

    public string $web_url;

    public string $type;

    public LuigisBoxObjectFields $fields;

    /**
     * @var \App\Model\LuigisBox\LuigisBoxObject[]
     */
    public array $nested = [];
}