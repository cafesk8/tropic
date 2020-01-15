<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Admin;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Component\Router\Security\Annotation\CsrfProtection;
use Shopsys\FrameworkBundle\Controller\Admin\AdminBaseController;
use Shopsys\ShopBundle\Form\Admin\ProductGiftFormType;
use Shopsys\ShopBundle\Model\Product\ProductGift\Exception\ProductGiftNotFoundException;
use Shopsys\ShopBundle\Model\Product\ProductGift\ProductGiftDataFactory;
use Shopsys\ShopBundle\Model\Product\ProductGift\ProductGiftFacade;
use Shopsys\ShopBundle\Model\Product\ProductGift\ProductGiftGridFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductGiftController extends AdminBaseController
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductGift\ProductGiftFacade
     */
    private $productGiftFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductGift\ProductGiftDataFactory
     */
    private $productGiftDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade
     */
    private $adminDomainTabsFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductGift\ProductGiftGridFactory
     */
    private $productGiftGridFactory;

    /**
     * @param \Shopsys\ShopBundle\Model\Product\ProductGift\ProductGiftFacade $productGiftFacade
     * @param \Shopsys\ShopBundle\Model\Product\ProductGift\ProductGiftDataFactory $productGiftDataFactory
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade
     * @param \Shopsys\ShopBundle\Model\Product\ProductGift\ProductGiftGridFactory $productGiftGridFactory
     */
    public function __construct(
        ProductGiftFacade $productGiftFacade,
        ProductGiftDataFactory $productGiftDataFactory,
        AdminDomainTabsFacade $adminDomainTabsFacade,
        ProductGiftGridFactory $productGiftGridFactory
    ) {
        $this->productGiftFacade = $productGiftFacade;
        $this->productGiftDataFactory = $productGiftDataFactory;
        $this->adminDomainTabsFacade = $adminDomainTabsFacade;
        $this->productGiftGridFactory = $productGiftGridFactory;
    }

    /**
     * @Route("/product/product-gift/new/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request): Response
    {
        $selectedDomainId = $this->adminDomainTabsFacade->getSelectedDomainId();
        $productGiftData = $this->productGiftDataFactory->createForDomainId($selectedDomainId);

        $form = $this->createForm(ProductGiftFormType::class, $productGiftData, [
            'productGift' => null,
        ]);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $productGiftData = $form->getData();

            $productGift = $this->productGiftFacade->create($productGiftData);

            $this->getFlashMessageSender()
                ->addSuccessFlashTwig(
                    t('Dárek <strong><a href="{{ url }}">{{ name }}</a></strong> byl úspěšně vytvořen'),
                    [
                        'name' => $productGift->getGift()->getName(),
                        'url' => $this->generateUrl('admin_productgift_edit', ['id' => $productGift->getId()]),
                    ]
                );

            return $this->redirectToRoute('admin_productgift_list');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->getFlashMessageSender()->addErrorFlash(t('Please check the correctness of all data filled.'));
        }

        return $this->render('@ShopsysShop/Admin/Content/ProductGift/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/product/product-gift/edit/{id}")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, int $id): Response
    {
        $productGift = $this->productGiftFacade->getById($id);

        if ($productGift === null) {
            $this->getFlashMessageSender()->addErrorFlash(t('Dárek neexistuje'));

            return $this->redirectToRoute('admin_productgift_list');
        }

        $productGiftData = $this->productGiftDataFactory->createFromProductGift($productGift);

        $form = $this->createForm(ProductGiftFormType::class, $productGiftData, [
            'productGift' => $productGift,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $productGiftData = $form->getData();

            $this->productGiftFacade->edit($productGift, $productGiftData);

            $this->getFlashMessageSender()
                ->addSuccessFlashTwig(
                    t('Dárek <strong><a href="{{ url }}">{{ name }}</a></strong> byl editován'),
                    [
                        'name' => $productGift->getGift()->getName(),
                        'url' => $this->generateUrl('admin_productgift_edit', ['id' => $productGift->getId()]),
                    ]
                );
            return $this->redirectToRoute('admin_productgift_list');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->getFlashMessageSender()->addErrorFlash(t('Please check the correctness of all data filled.'));
        }

        return $this->render('@ShopsysShop/Admin/Content/ProductGift/edit.html.twig', [
            'form' => $form->createView(),
            'productGift' => $productGift,
        ]);
    }

    /**
     * @Route("/product/product-gift/list/")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(): Response
    {
        $grid = $this->productGiftGridFactory->create();

        return $this->render('@ShopsysShop/Admin/Content/ProductGift/list.html.twig', [
            'gridView' => $grid->createView(),
        ]);
    }

    /**
     * @CsrfProtection
     *
     * @Route("/product/product-gift/delete/{id}")
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(int $id): Response
    {
        try {
            $productGiftName = $this->productGiftFacade->getById($id)->getGift()->getName();

            $this->productGiftFacade->delete($id);

            $this->getFlashMessageSender()->addSuccessFlashTwig(
                t('Dárek <strong>{{ name }}</strong> byl smazán'),
                [
                    'name' => $productGiftName,
                ]
            );
        } catch (ProductGiftNotFoundException $exception) {
            $this->getFlashMessageSender()->addErrorFlash(t('Vybraný dárek neexistuje'));
        }

        return $this->redirectToRoute('admin_productgift_list');
    }
}
