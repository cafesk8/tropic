<?php

declare(strict_types=1);

namespace App\DataFixtures\Demo;

use App\Component\DiscountExclusion\DiscountExclusionFacade;
use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Component\Domain\Domain;

class DiscountExclusionDataFixture extends AbstractReferenceFixture
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \App\Component\DiscountExclusion\DiscountExclusionFacade
     */
    private $discountExclusionFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Component\DiscountExclusion\DiscountExclusionFacade $discountExclusionFacade
     */
    public function __construct(Domain $domain, DiscountExclusionFacade $discountExclusionFacade)
    {
        $this->domain = $domain;
        $this->discountExclusionFacade = $discountExclusionFacade;
    }

    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->domain->getAll() as $domainConfig) {
            $this->discountExclusionFacade->setRegistrationDiscountExclusionText(
                t('Na tento produkt se nevztahuje sleva pro registrované zákazníky', [], 'dataFixtures', $domainConfig->getLocale()),
                $domainConfig->getId()
            );

            $this->discountExclusionFacade->setPromoDiscountExclusionText(
                t('Na tento produkt se nevztahuje sleva za slevové kupóny', [], 'dataFixtures', $domainConfig->getLocale()),
                $domainConfig->getId()
            );

            $this->discountExclusionFacade->setAllDiscountExclusionText(
                t('Na tento produkt se nevztahují žádné slevy', [], 'dataFixtures', $domainConfig->getLocale()),
                $domainConfig->getId()
            );
        }
    }
}
