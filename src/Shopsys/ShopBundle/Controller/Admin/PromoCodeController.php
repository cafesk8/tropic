<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Admin;

use Shopsys\FrameworkBundle\Component\Router\Security\Annotation\CsrfProtection;
use Shopsys\FrameworkBundle\Controller\Admin\PromoCodeController as BasePromoCodeController;
use Shopsys\FrameworkBundle\Form\Admin\PromoCode\PromoCodeFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @property \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeFacade $promoCodeFacade
 */
class PromoCodeController extends BasePromoCodeController
{
    /**
     * @Route("/promo-code/new-mass-generate")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newMassGenerateAction(Request $request): Response
    {
        /** @var \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeData $promoCodeData */
        $promoCodeData = $this->promoCodeDataFactory->create();
        $promoCodeData->massGenerate = true;

        $form = $this->createForm(PromoCodeFormType::class, $promoCodeData, [
            'promo_code' => null,
            'mass_generate' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->promoCodeFacade->massCreate($promoCodeData);

                $this->getFlashMessageSender()->addSuccessFlashTwig(
                    t('Bylo vytvořeno <strong>{{ quantity }}</strong> slevových kupónů'),
                    [
                        'quantity' => $promoCodeData->quantity,
                    ]
                );

                return $this->redirectToRoute('admin_promocode_list');
            } else {
                $this->getFlashMessageSender()->addErrorFlashTwig(t('Please check the correctness of all data filled.'));
            }
        }

        return $this->render('@ShopsysShop/Admin/Content/PromoCode/newMassGenerate.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/promo-code/mass-delete/{prefix}")
     *
     * @CsrfProtection
     *
     * @param string $prefix
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteMassAction(string $prefix): Response
    {
        $this->promoCodeFacade->deleteByPrefix($prefix);
        $this->getFlashMessageSender()->addSuccessFlash(t('Slevové kupóny byly smazány.'));

        return $this->redirectToRoute('admin_promocode_list');
    }
}
