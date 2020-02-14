<?php

declare(strict_types=1);

namespace App\Twig;

use App\Model\Customer\User;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UserExtension extends AbstractExtension
{
    /**
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('userExportStatusName', [$this, 'getUserExportStatusName']),
        ];
    }

    /**
     * @param string $exportStatus
     * @return string
     */
    public function getUserExportStatusName(string $exportStatus): string
    {
        return User::getExportStatusNameByExportStatus($exportStatus);
    }
}
