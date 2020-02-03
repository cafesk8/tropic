<?php

declare(strict_types=1);

namespace App\Twig;

use Shopsys\FrameworkBundle\Twig\DomainExtension as BaseDomainExtension;
use App\Component\Domain\DomainHelper;
use Twig\TwigFunction;

class DomainExtension extends BaseDomainExtension
{
    /**
     * @return array
     */
    public function getFunctions(): array
    {
        return array_merge(parent::getFunctions(), [
            new TwigFunction('isGermanyDomain', [$this, 'isGermanyDomain']),
            new TwigFunction('isSlovakDomain', [$this, 'isSlovakDomain']),
        ]);
    }

    /**
     * @return bool
     */
    public function isGermanyDomain(): bool
    {
        return DomainHelper::isGermanDomain($this->domain);
    }

    /**
     * @return bool
     */
    public function isSlovakDomain(): bool
    {
        return DomainHelper::isSlovakDomain($this->domain);
    }
}
