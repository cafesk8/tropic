<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Admin;

use Shopsys\FrameworkBundle\Controller\Admin\AdminBaseController;
use Shopsys\ShopBundle\Form\Admin\MainVariantGroupFormType;
use Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroupFacade;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MainVariantGroupController extends AdminBaseController
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroupFacade
     */
    private $mainVariantGroupFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroupFacade $mainVariantGroupFacade
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
            /** @var \Shopsys\ShopBundle\Model\Product\Product $firstProduct */
            $firstProduct = reset($products);

            $this->getFlashMessageSender()->addSuccessFlashTwig(t('Skupina produktů byla úspěšně vytvořena.'));
            return $this->redirectToRoute('admin_product_edit', ['id' => $firstProduct->getId()]);
        }

        return $this->render('@ShopsysShop/Admin/Content/MainVariantGroup/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
