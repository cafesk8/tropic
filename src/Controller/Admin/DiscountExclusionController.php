<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Component\DiscountExclusion\DiscountExclusionFacade;
use App\Form\Admin\DiscountExclusionFormType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Shopsys\FrameworkBundle\Controller\Admin\AdminBaseController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DiscountExclusionController extends AdminBaseController
{
    /**
     * @var \App\Component\DiscountExclusion\DiscountExclusionFacade
     */
    private $discountExclusionFacade;

    /**
     * @param \App\Component\DiscountExclusion\DiscountExclusionFacade $discountExclusionFacade
     */
    public function __construct(DiscountExclusionFacade $discountExclusionFacade)
    {
        $this->discountExclusionFacade = $discountExclusionFacade;
    }

    /**
     * @Route("/discount-exclusion/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function detailAction(Request $request): Response
    {
        $form = $this->createForm(DiscountExclusionFormType::class, [
            'registrationDiscountExclusion' => $this->discountExclusionFacade->getRegistrationDiscountExclusionTexts(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $formData = $form->getData();

                foreach ($formData['registrationDiscountExclusion'] as $domainId => $discountExclusionText) {
                    $this->discountExclusionFacade->setRegistrationDiscountExclusionText($discountExclusionText, $domainId);
                }

                $this->addSuccessFlashTwig(t('Změny byly úspěšně uloženy'));

                return $this->redirectToRoute('admin_discountexclusion_detail');
            } else {
                $this->addErrorFlash(t('Please check the correctness of all data filled.'));
            }
        }

        return $this->render('Admin/Content/DiscountExclusion/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
