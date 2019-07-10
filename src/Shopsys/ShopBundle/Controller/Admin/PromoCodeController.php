<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Admin;

use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Component\Router\Security\Annotation\CsrfProtection;
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
     * @var \Shopsys\ShopBundle\Model\Order\PromoCode\Grid\PromoCodeGridFactory
     */
    private $promoCodeGridFactory;

    /**
     * @var \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeDataFactory
     */
    private $promoCodeDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade
     */
    private $adminDomainTabsFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeFacade
     */
    protected $promoCodeFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeFacade $promoCodeFacade
     * @param \Shopsys\FrameworkBundle\Model\Order\PromoCode\Grid\PromoCodeInlineEdit $promoCodeInlineEdit
     * @param \Shopsys\FrameworkBundle\Model\Administrator\AdministratorGridFacade $administratorGridFacade
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeDataFactory $promoCodeDataFactory
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\Grid\PromoCodeGridFactory $promoCodeGridFactory
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade
     */
    public function __construct(
        PromoCodeFacade $promoCodeFacade,
        PromoCodeInlineEdit $promoCodeInlineEdit,
        AdministratorGridFacade $administratorGridFacade,
        PromoCodeDataFactory $promoCodeDataFactory,
        PromoCodeGridFactory $promoCodeGridFactory,
        AdminDomainTabsFacade $adminDomainTabsFacade
    ) {
        parent::__construct($promoCodeFacade, $promoCodeInlineEdit, $administratorGridFacade);

        $this->promoCodeDataFactory = $promoCodeDataFactory;
        $this->promoCodeGridFactory = $promoCodeGridFactory;
        $this->adminDomainTabsFacade = $adminDomainTabsFacade;
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
            'domain_id' => $this->adminDomainTabsFacade->getSelectedDomainId(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $promoCode = $this->promoCodeFacade->create($promoCodeData);

                $this->getFlashMessageSender()->addSuccessFlashTwig(
                    t('Slevový kupón <strong><a href="{{ url }}">{{ name }}</a></strong> byl vytvořen'),
                    [
                        'name' => $promoCode->getCode(),
                        'url' => $this->generateUrl('admin_promocode_edit', ['id' => $promoCode->getId()]),
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
            'domain_id' => $this->adminDomainTabsFacade->getSelectedDomainId(),
        ]);
        $form->handleRequest($request);

        if ($form->isValid()) {
            if ($form->isSubmitted()) {
                $this->promoCodeFacade->edit($id, $promoCodeData);

                $this->getFlashMessageSender()->addSuccessFlashTwig(
                    t('Slevový kupón <strong><a href="{{ url }}">{{ name }}</a></strong> byl modifikován'),
                    [
                        'name' => $promoCode->getCode(),
                        'url' => $this->generateUrl('admin_promocode_edit', ['id' => $promoCode->getId()]),
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

    /**
     * @Route("/promo-code/new-mass-generate")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newMassGenerateAction(Request $request): Response
    {
        /** @var \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeData $promoCodeData */
        $promoCodeData = $this->promoCodeDataFactory->create();
        $promoCodeData->massGenerated = true;

        $form = $this->createForm(PromoCodeFormType::class, $promoCodeData, [
            'promo_code' => null,
            'mass_generate' => true,
            'domain_id' => $this->adminDomainTabsFacade->getSelectedDomainId(),
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
