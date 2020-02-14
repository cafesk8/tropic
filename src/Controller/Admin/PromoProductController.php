<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Component\Router\Security\Annotation\CsrfProtection;
use Shopsys\FrameworkBundle\Controller\Admin\AdminBaseController;
use App\Form\Admin\PromoProductFormType;
use App\Model\Product\PromoProduct\Exception\PromoProductNotFoundException;
use App\Model\Product\PromoProduct\PromoProductDataFactory;
use App\Model\Product\PromoProduct\PromoProductFacade;
use App\Model\Product\PromoProduct\PromoProductGridFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PromoProductController extends AdminBaseController
{
    /**
     * @var \App\Model\Product\PromoProduct\PromoProductFacade
     */
    private $promoProductFacade;

    /**
     * @var \App\Model\Product\PromoProduct\PromoProductDataFactory
     */
    private $promoProductDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade
     */
    private $adminDomainTabsFacade;

    /**
     * @var \App\Model\Product\PromoProduct\PromoProductGridFactory
     */
    private $promoProductGridFactory;

    /**
     * @param \App\Model\Product\PromoProduct\PromoProductFacade $promoProductFacade
     * @param \App\Model\Product\PromoProduct\PromoProductDataFactory $promoProductDataFactory
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade
     * @param \App\Model\Product\PromoProduct\PromoProductGridFactory $promoProductGridFactory
     */
    public function __construct(
        PromoProductFacade $promoProductFacade,
        PromoProductDataFactory $promoProductDataFactory,
        AdminDomainTabsFacade $adminDomainTabsFacade,
        PromoProductGridFactory $promoProductGridFactory
    ) {
        $this->promoProductFacade = $promoProductFacade;
        $this->promoProductDataFactory = $promoProductDataFactory;
        $this->adminDomainTabsFacade = $adminDomainTabsFacade;
        $this->promoProductGridFactory = $promoProductGridFactory;
    }

    /**
     * @Route("/product/promo-product/new/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request): Response
    {
        $selectedDomainId = $this->adminDomainTabsFacade->getSelectedDomainId();
        $promoProductData = $this->promoProductDataFactory->createForDomainId($selectedDomainId);

        $form = $this->createForm(PromoProductFormType::class, $promoProductData);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $promoProductData = $form->getData();

            $promoProduct = $this->promoProductFacade->create($promoProductData);

            $this->getFlashMessageSender()
                ->addSuccessFlashTwig(
                    t('Promo produkt <strong><a href="{{ url }}">{{ name }}</a></strong> byl úspěšně vytvořen'),
                    [
                        'name' => $promoProduct->getProduct()->getName(),
                        'url' => $this->generateUrl('admin_promoproduct_edit', ['id' => $promoProduct->getId()]),
                    ]
                );

            return $this->redirectToRoute('admin_promoproduct_list');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->getFlashMessageSender()->addErrorFlash(t('Please check the correctness of all data filled.'));
        }

        return $this->render('Admin/Content/PromoProduct/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/product/promo-product/edit/{id}")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, int $id): Response
    {
        try {
            $promoProduct = $this->promoProductFacade->getById($id);
        } catch (PromoProductNotFoundException $ex) {
            $this->getFlashMessageSender()->addErrorFlash(t('Promo produkt neexistuje'));

            return $this->redirectToRoute('admin_promoproduct_list');
        }

        $promoProductData = $this->promoProductDataFactory->createFromPromoProduct($promoProduct);

        $form = $this->createForm(PromoProductFormType::class, $promoProductData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $promoProductData = $form->getData();

            $this->promoProductFacade->edit($promoProduct, $promoProductData);

            $this->getFlashMessageSender()
                ->addSuccessFlashTwig(
                    t('Promo produkt <strong><a href="{{ url }}">{{ name }}</a></strong> byl editován'),
                    [
                        'name' => $promoProduct->getProduct()->getName(),
                        'url' => $this->generateUrl('admin_promoproduct_edit', ['id' => $promoProduct->getId()]),
                    ]
                );
            return $this->redirectToRoute('admin_promoproduct_list');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->getFlashMessageSender()->addErrorFlash(t('Please check the correctness of all data filled.'));
        }

        return $this->render('Admin/Content/PromoProduct/edit.html.twig', [
            'form' => $form->createView(),
            'promoProduct' => $promoProduct,
        ]);
    }

    /**
     * @Route("/product/promo-product/list/")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(): Response
    {
        $grid = $this->promoProductGridFactory->create();

        return $this->render('Admin/Content/PromoProduct/list.html.twig', [
            'gridView' => $grid->createView(),
        ]);
    }

    /**
     * @CsrfProtection
     *
     * @Route("/product/promo-product/delete/{id}")
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(int $id): Response
    {
        try {
            $promoProductName = $this->promoProductFacade->getById($id)->getProduct()->getName();

            $this->promoProductFacade->delete($id);

            $this->getFlashMessageSender()->addSuccessFlashTwig(
                t('Promo produkt <strong>{{ name }}</strong> byl smazán'),
                [
                    'name' => $promoProductName,
                ]
            );
        } catch (PromoProductNotFoundException $exception) {
            $this->getFlashMessageSender()->addErrorFlash(t('Vybraný promo produkt neexistuje'));
        }

        return $this->redirectToRoute('admin_promoproduct_list');
    }
}
