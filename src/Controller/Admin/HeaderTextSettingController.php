<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Form\Admin\HeaderTextSettingFormType;
use App\Model\HeaderText\HeaderTextSettingFacade;
use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Controller\Admin\AdminBaseController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HeaderTextSettingController extends AdminBaseController
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade
     */
    protected $adminDomainTabsFacade;

    /**
     * @var \App\Model\HeaderText\HeaderTextSettingFacade;
     */
    protected $headerTextSettingFacade;

    /**
     * @param \App\Model\HeaderText\HeaderTextSettingFacade $headerTextSettingFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade
     */
    public function __construct(
        HeaderTextSettingFacade $headerTextSettingFacade,
        AdminDomainTabsFacade $adminDomainTabsFacade
    ) {
        $this->headerTextSettingFacade = $headerTextSettingFacade;
        $this->adminDomainTabsFacade = $adminDomainTabsFacade;
    }

    /**
     * @Route("/header-text/setting/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function settingAction(Request $request): Response
    {
        $selectedDomainId = $this->adminDomainTabsFacade->getSelectedDomainId();

        $headerTextSettingData = [
            'headerTitle' => $this->headerTextSettingFacade->getHeaderTitle($selectedDomainId),
            'headerText' => $this->headerTextSettingFacade->getHeaderText($selectedDomainId),
            'headerLink' => $this->headerTextSettingFacade->getHeaderLink($selectedDomainId),
        ];

        $form = $this->createForm(HeaderTextSettingFormType::class, $headerTextSettingData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $headerTextSettingData = $form->getData();

            $this->headerTextSettingFacade->setHeaderTitle($headerTextSettingData['headerTitle'], $selectedDomainId);
            $this->headerTextSettingFacade->setHeaderText($headerTextSettingData['headerText'], $selectedDomainId);
            $this->headerTextSettingFacade->setHeaderLink($headerTextSettingData['headerLink'], $selectedDomainId);

            $this->addSuccessFlash(t('E-shop attributes settings modified'));

            return $this->redirectToRoute('admin_headertextsetting_setting');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addErrorFlashTwig(t('Please check the correctness of all data filled.'));
        }

        return $this->render('Admin/Content/HeaderTextSettings/headerText.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
