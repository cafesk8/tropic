<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Form\Admin\MainVariantGroupFormType;
use App\Model\Product\MainVariantGroup\MainVariantGroupFacade;
use Shopsys\FrameworkBundle\Controller\Admin\AdminBaseController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MainVariantGroupController extends AdminBaseController
{
    /**
     * @var \App\Model\Product\MainVariantGroup\MainVariantGroupFacade
     */
    private $mainVariantGroupFacade;

    /**
     * @param \App\Model\Product\MainVariantGroup\MainVariantGroupFacade $mainVariantGroupFacade
     */
    public function __construct(MainVariantGroupFacade $mainVariantGroupFacade)
    {
        $this->mainVariantGroupFacade = $mainVariantGroupFacade;
    }

    /**
     * @Route("/product/create-main-variant-group/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function createMainVariantGroupAction(Request $request)
    {
        $form = $this->createForm(MainVariantGroupFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();

            $this->mainVariantGroupFacade->createMainVariantGroup($formData[MainVariantGroupFormType::DISTINGUISHING_PARAMETER], $formData[MainVariantGroupFormType::PRODUCTS]);
            $products = $formData[MainVariantGroupFormType::PRODUCTS];
            /** @var \App\Model\Product\Product $firstProduct */
            $firstProduct = reset($products);

            $this->getFlashMessageSender()->addSuccessFlashTwig(t('Skupina produktů byla úspěšně vytvořena.'));
            return $this->redirectToRoute('admin_product_edit', ['id' => $firstProduct->getId()]);
        }

        return $this->render('Admin/Content/MainVariantGroup/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
