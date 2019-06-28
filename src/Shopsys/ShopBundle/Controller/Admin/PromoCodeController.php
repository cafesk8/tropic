<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Admin;

use Shopsys\FrameworkBundle\Controller\Admin\PromoCodeController as BasePromoCodeController;
use Shopsys\FrameworkBundle\Form\Admin\PromoCode\PromoCodeFormType;
use Shopsys\FrameworkBundle\Model\Administrator\AdministratorGridFacade;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\Grid\PromoCodeGridFactory;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\Grid\PromoCodeInlineEdit;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeFacade;
use Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeDataFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PromoCodeController extends BasePromoCodeController
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Order\PromoCode\Grid\PromoCodeGridFactory
     */
    private $promoCodeGridFactory;

    /**
     * @var \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeDataFactory
     */
    private $promoCodeDataFactory;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeFacade $promoCodeFacade
     * @param \Shopsys\FrameworkBundle\Model\Order\PromoCode\Grid\PromoCodeInlineEdit $promoCodeInlineEdit
     * @param \Shopsys\FrameworkBundle\Model\Administrator\AdministratorGridFacade $administratorGridFacade
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeDataFactory $promoCodeDataFactory
     * @param \Shopsys\FrameworkBundle\Model\Order\PromoCode\Grid\PromoCodeGridFactory $promoCodeGridFactory
     */
    public function __construct(
        PromoCodeFacade $promoCodeFacade,
        PromoCodeInlineEdit $promoCodeInlineEdit,
        AdministratorGridFacade $administratorGridFacade,
        PromoCodeDataFactory $promoCodeDataFactory,
        PromoCodeGridFactory $promoCodeGridFactory
    ) {
        parent::__construct($promoCodeFacade, $promoCodeInlineEdit, $administratorGridFacade);

        $this->promoCodeDataFactory = $promoCodeDataFactory;
        $this->promoCodeGridFactory = $promoCodeGridFactory;
    }

    /**
     * @Route("/promo-code/list")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(): Response
    {
        $administrator = $this->getUser();
        /* @var $administrator \Shopsys\FrameworkBundle\Model\Administrator\Administrator */

        $grid = $this->promoCodeGridFactory->create();

        $grid->enablePaging();

        $this->administratorGridFacade->restoreAndRememberGridLimit($administrator, $grid);

        return $this->render('@ShopsysShop/Admin/Content/PromoCode/list.html.twig', [
            'gridView' => $grid->createView(),
        ]);
    }

    /**
     * @Route("/promo-code/new")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request): Response
    {
        $promoCodeData = $this->promoCodeDataFactory->create();

        $form = $this->createForm(PromoCodeFormType::class, $promoCodeData, [
            'promo_code' => null,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $promoCode = $this->promoCodeFacade->create($promoCodeData);

                $this->getFlashMessageSender()->addSuccessFlashTwig(
                    t('Slevový kupón <strong>{{ name }}</strong> byl vytvořen'),
                    [
                        'name' => $promoCode->getCode(),
                    ]
                );

                return $this->redirectToRoute('admin_promocode_list');
            } else {
                $this->getFlashMessageSender()->addErrorFlashTwig(t('Please check the correctness of all data filled.'));
            }
        }

        return $this->render('@ShopsysShop/Admin/Content/PromoCode/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/promo-code/edit/{id}", requirements={"id" = "\d+"})
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, int $id): Response
    {
        $promoCode = $this->promoCodeFacade->getById($id);
        $promoCodeData = $this->promoCodeDataFactory->createFromPromoCode($promoCode);

        $form = $this->createForm(PromoCodeFormType::class, $promoCodeData, [
            'promo_code' => $promoCode,
        ]);
        $form->handleRequest($request);

        if ($form->isValid()) {
            if ($form->isSubmitted()) {
                $this->promoCodeFacade->edit($id, $promoCodeData);

                $this->getFlashMessageSender()->addSuccessFlashTwig(
                    t('Slevový kupón <strong>{{ name }}</strong> byl modifikován'),
                    [
                        'name' => $promoCode->getCode(),
                    ]
                );

                return $this->redirectToRoute('admin_promocode_list');
            } else {
                $this->getFlashMessageSender()->addErrorFlash(t('Please check the correctness of all data filled.'));
            }
        }

        return $this->render('@ShopsysShop/Admin/Content/PromoCode/edit.html.twig', [
            'form' => $form->createView(),
            'promoCode' => $promoCode,
        ]);
    }
}
