<?php

declare(strict_types=1);

namespace Tests\App\Test;

use App\Model\Product\Parameter\ParameterFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;

class ParameterTransactionFunctionalTestCase extends TransactionFunctionalTestCase
{
    /**
     * @param string $parameterValueNameId
     * @return int
     */
    protected function getParameterValueIdForFirstDomain(string $parameterValueNameId): int
    {
        /** @var \Shopsys\FrameworkBundle\Component\Domain\Domain $domain */
        $domain = $this->getContainer()->get(Domain::class);

        /** @var \App\Model\Product\Parameter\ParameterFacade $parameterFacade */
        $parameterFacade = $this->getContainer()->get(ParameterFacade::class);

        $firstDomainLocale = $domain->getDomainConfigById(Domain::FIRST_DOMAIN_ID)->getLocale();
        $parameterValueTranslatedName = t($parameterValueNameId, [], 'dataFixtures', $firstDomainLocale);

        return $parameterFacade->getParameterValueByValueTextAndLocale($parameterValueTranslatedName, $firstDomainLocale)->getId();
    }
}
