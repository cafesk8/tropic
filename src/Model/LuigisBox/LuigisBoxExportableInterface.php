<?php

declare(strict_types=1);

namespace App\Model\LuigisBox;

interface LuigisBoxExportableInterface
{
    /**
     * @param int $domainId
     */
    public function markAsExportedToLuigisBox(int $domainId): void;

    public function markForExportToLuigisBox(): void;
}