<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Shopsys\FrameworkBundle\Controller\Admin\AccessController as BaseAccessController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AccessController extends BaseAccessController
{
    /**
     * @Route("/access-denied/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deniedAction(Request $request): Response
    {
        $this->addErrorFlashTwig(
            t('Nemáte oprávnění vidět požadovanou stránku. Požádejte svého administrátora o udělení přístupu.')
        );
        $referer = $request->headers->get('referer');
        $urlToRedirect = $referer === null ? $this->generateUrl('admin_default_dashboard') : $referer;
        return new RedirectResponse($urlToRedirect);
    }
}
