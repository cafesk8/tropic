<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Component\LuigisBox\LuigisBoxApiKeysProvider;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchController extends FrontBaseController
{
    private string $luigisBoxTrackerId;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Component\LuigisBox\LuigisBoxApiKeysProvider $luigisBoxKeysProvider
     */
    public function __construct(Domain $domain, LuigisBoxApiKeysProvider $luigisBoxKeysProvider)
    {
        $this->luigisBoxTrackerId = $luigisBoxKeysProvider->getPublicKey($domain->getLocale());
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function boxAction(Request $request): Response
    {
        $searchText = $request->query->get(ProductController::SEARCH_TEXT_PARAMETER);

        return $this->render('Front/Content/Search/searchBox.html.twig', [
            'luigisTrackerId' => $this->luigisBoxTrackerId,
            'searchText' => $searchText,
            'SEARCH_TEXT_PARAMETER' => ProductController::SEARCH_TEXT_PARAMETER,
        ]);
    }
}
