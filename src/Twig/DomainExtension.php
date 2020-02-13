<?php

declare(strict_types=1);

namespace App\Twig;

use App\Component\Domain\DomainHelper;
use Shopsys\FrameworkBundle\Twig\DomainExtension as BaseDomainExtension;
use Twig\TwigFunction;

class DomainExtension extends BaseDomainExtension
{
    /**
     * @return array
     */
    public function getFunctions(): array
    {
        return array_merge(parent::getFunctions(), [
            new TwigFunction('isEnglishDomain', [$this, 'isEnglishDomain']),
            new TwigFunction('isSlovakDomain', [$this, 'isSlovakDomain']),
        ]);
    }

    /**
     * @return bool
     */
    public function isEnglishDomain(): bool
    {
        return DomainHelper::isEnglishDomain($this->domain);
    }

    /**
     * @return bool
     */
    public function isSlovakDomain(): bool
    {
        return DomainHelper::isSlovakDomain($this->domain);
    }
}
