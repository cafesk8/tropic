<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Component\InfoRow\InfoRowFacade;
use App\Form\Admin\InfoRowFormType;
use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Controller\Admin\AdminBaseController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class InfoRowController extends AdminBaseController
{
    /**
     * @var \App\Component\InfoRow\InfoRowFacade
     */
    private $infoRowFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade
     */
    private $adminDomainTabsFacade;

    /**
     * @param \App\Component\InfoRow\InfoRowFacade $infoRowFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade
     */
    public function __construct(
        InfoRowFacade $infoRowFacade,
        AdminDomainTabsFacade $adminDomainTabsFacade
    ) {
        $this->infoRowFacade = $infoRowFacade;
        $this->adminDomainTabsFacade = $adminDomainTabsFacade;
    }

    /**
     * @Route("/info-row/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function detailAction(Request $request): Response
    {
        $selectedDomainId = $this->adminDomainTabsFacade->getSelectedDomainId();

        $infoRowFormData = [
            'visibility' => $this->infoRowFacade->isRowVisible($selectedDomainId),
            'text' => $this->infoRowFacade->getRowText($selectedDomainId),
        ];
        $form = $this->createForm(InfoRowFormType::class, $infoRowFormData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $infoRowFormData = $form->getData();
            $this->infoRowFacade->setInfoRow(
                $infoRowFormData['visibility'],
                $infoRowFormData['text'],
                $selectedDomainId
            );
            $this->addSuccessFlashTwig(t('Změny v informačním řádky byly úspěšně uloženy'));

            return $this->redirectToRoute('admin_inforow_detail');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addErrorFlash(t('Please check the correctness of all data filled.'));
        }

        return $this->render('Admin/Content/infoRow/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
