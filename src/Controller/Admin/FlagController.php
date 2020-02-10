<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Form\Admin\FlagSettingsFormType;
use App\Model\Product\Flag\FlagFacade;
use Shopsys\FrameworkBundle\Controller\Admin\AdminBaseController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FlagController extends AdminBaseController
{
    /**
     * @var \App\Model\Product\Flag\FlagFacade
     */
    protected $flagFacade;

    /**
     * @param \App\Model\Product\Flag\FlagFacade $flagFacade
     */
    public function __construct(FlagFacade $flagFacade)
    {
        $this->flagFacade = $flagFacade;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function settingsAction(Request $request): Response
    {
        $form = $this->createForm(FlagSettingsFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $flag = $form->getData()['defaultFreeTransportAndPaymentFlag'];
            $flagId = $flag === null ? null : $flag->getId();
            $this->flagFacade->setDefaultFlagForFreeTransportAndPayment($flagId);

            return $this->redirectToRoute('admin_flag_list');
        }

        return $this->render('Admin/Content/Flag/freeTransportAndPaymentSetting.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
