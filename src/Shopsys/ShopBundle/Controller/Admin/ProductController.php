<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Admin;

use Shopsys\FrameworkBundle\Controller\Admin\ProductController as BaseProductController;
use Shopsys\FrameworkBundle\Form\Admin\Product\VariantFormType;
use Shopsys\ShopBundle\Form\Admin\VariantFormTypeExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends BaseProductController
{
    /**
     * @Route("/product/create-variant/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function createVariantAction(Request $request)
    {
        $form = $this->createForm(VariantFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            /** @var \Shopsys\ShopBundle\Model\Product\Product $mainVariant */
            $mainVariant = $formData[VariantFormType::MAIN_VARIANT];
            $mainVariant->setDistinguishingParameter($formData[VariantFormTypeExtension::DISTINGUISHING_PARAMETER]);
            try {
                $newMainVariant = $this->productVariantFacade->createVariant($mainVariant, $formData[VariantFormType::VARIANTS]);

                $this->getFlashMessageSender()->addSuccessFlashTwig(
                    t('Variant <strong>{{ productVariant|productDisplayName }}</strong> successfully created.'),
                    [
                        'productVariant' => $newMainVariant,
                    ]
                );

                return $this->redirectToRoute('admin_product_edit', ['id' => $newMainVariant->getId()]);
            } catch (\Shopsys\FrameworkBundle\Model\Product\Exception\VariantException $ex) {
                $this->getFlashMessageSender()->addErrorFlash(
                    t('Not possible to create variations of products that are already variant or main variant.')
                );
            }
        }

        return $this->render('@ShopsysFramework/Admin/Content/Product/createVariant.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
