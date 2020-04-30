<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Shopsys\FrameworkBundle\Component\Router\Security\Annotation\CsrfProtection;
use Shopsys\FrameworkBundle\Controller\Admin\PromoCodeController as BasePromoCodeController;
use Shopsys\FrameworkBundle\Form\Admin\PromoCode\PromoCodeFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @property \App\Model\Order\PromoCode\PromoCodeFacade $promoCodeFacade
 * @property \App\Model\Order\PromoCode\Grid\PromoCodeGridFactory|null $promoCodeGridFactory
 * @method __construct(\App\Model\Order\PromoCode\PromoCodeFacade $promoCodeFacade, \Shopsys\FrameworkBundle\Model\Order\PromoCode\Grid\PromoCodeInlineEdit $promoCodeInlineEdit, \Shopsys\FrameworkBundle\Model\Administrator\AdministratorGridFacade $administratorGridFacade, \Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeDataFactoryInterface|null $promoCodeDataFactory, \App\Model\Order\PromoCode\Grid\PromoCodeGridFactory|null $promoCodeGridFactory, \Shopsys\FrameworkBundle\Model\AdminNavigation\BreadcrumbOverrider|null $breadcrumbOverrider, bool $useInlineEditation)
 * @method setPromoCodeGridFactory(\App\Model\Order\PromoCode\Grid\PromoCodeGridFactory $promoCodeGridFactory)
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
        /** @var \App\Model\Order\PromoCode\PromoCodeData $promoCodeData */
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

                $this->addSuccessFlashTwig(
                    t('Bylo vytvořeno <strong>{{ quantity }}</strong> slevových kupónů'),
                    [
                        'quantity' => $promoCodeData->quantity,
                    ]
                );

                return $this->redirectToRoute('admin_promocode_list');
            } else {
                $this->addErrorFlashTwig(t('Please check the correctness of all data filled.'));
            }
        }

        return $this->render('Admin/Content/PromoCode/newMassGenerate.html.twig', [
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
        $this->addSuccessFlash(t('Slevové kupóny byly smazány.'));

        return $this->redirectToRoute('admin_promocode_list');
    }
}
