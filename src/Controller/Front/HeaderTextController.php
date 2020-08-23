<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Model\HeaderText\HeaderTextSettingFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Symfony\Component\HttpFoundation\Response;

class HeaderTextController extends FrontBaseController
{ 
    /**
     * @var \App\Model\HeaderText\HeaderTextSettingFacade
     */
    private $headerTextSettingFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \App\Model\HeaderText\HeaderTextSettingFacade $headerTextSettingFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(HeaderTextSettingFacade $headerTextSettingFacade, Domain $domain)
    {
        $this->headerTextSettingFacade = $headerTextSettingFacade;
        $this->domain = $domain;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function headerTextAction(): Response
    {
        $headerTitle = $this->headerTextSettingFacade->getHeaderTitle($this->domain->getId());
        $headerText = $this->headerTextSettingFacade->getHeaderText($this->domain->getId());
        $headerLink = $this->headerTextSettingFacade->getHeaderLink($this->domain->getId());

        return $this->render('Front/Content/Advert/headerText.html.twig', [
            'headerTitle' => $headerTitle,
            'headerText' => $headerText,
            'headerLink' => $headerLink
        ]);
    }
}
