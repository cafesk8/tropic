<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Form\Admin\OrderDiscountLevelFormType;
use App\Model\Order\Discount\OrderDiscountLevelDataFactory;
use App\Model\Order\Discount\OrderDiscountLevelFacade;
use App\Model\Order\Discount\OrderDiscountLevelGridFactory;
use App\Model\Order\Exception\OrderDiscountLevelNotFoundException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Component\Router\Security\Annotation\CsrfProtection;
use Shopsys\FrameworkBundle\Controller\Admin\AdminBaseController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OrderDiscountLevelController extends AdminBaseController
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade
     */
    private $adminDomainTabsFacade;

    /**
     * @var \App\Model\Order\Discount\OrderDiscountLevelDataFactory
     */
    private $orderDiscountLevelDataFactory;

    /**
     * @var \App\Model\Order\Discount\OrderDiscountLevelFacade
     */
    private $orderDiscountLevelFacade;

    /**
     * @var \App\Model\Order\Discount\OrderDiscountLevelGridFactory
     */
    private $orderDiscountLevelGridFactory;

    /**
     * @param \App\Model\Order\Discount\OrderDiscountLevelDataFactory $orderDiscountLevelDataFactory
     * @param \App\Model\Order\Discount\OrderDiscountLevelFacade $orderDiscountLevelFacade
     * @param \App\Model\Order\Discount\OrderDiscountLevelGridFactory $orderDiscountLevelGridFactory
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade
     */
    public function __construct(
        OrderDiscountLevelDataFactory $orderDiscountLevelDataFactory,
        OrderDiscountLevelFacade $orderDiscountLevelFacade,
        OrderDiscountLevelGridFactory $orderDiscountLevelGridFactory,
        AdminDomainTabsFacade $adminDomainTabsFacade
    ) {
        $this->adminDomainTabsFacade = $adminDomainTabsFacade;
        $this->orderDiscountLevelDataFactory = $orderDiscountLevelDataFactory;
        $this->orderDiscountLevelFacade = $orderDiscountLevelFacade;
        $this->orderDiscountLevelGridFactory = $orderDiscountLevelGridFactory;
    }

    /**
     * @Route("/pricing/order-discount-level/list/")
     */
    public function listAction()
    {
        $grid = $this->orderDiscountLevelGridFactory->createForDomain($this->adminDomainTabsFacade->getSelectedDomainId());

        return $this->render('Admin/Content/Order/DiscountLevel/list.html.twig', [
            'gridView' => $grid->createView(),
        ]);
    }

    /**
     * @Route("/pricing/order-discount-level/new/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request): Response
    {
        $orderDiscountLevelData = $this->orderDiscountLevelDataFactory->createForDomainId($this->adminDomainTabsFacade->getSelectedDomainId());

        $form = $this->createForm(OrderDiscountLevelFormType::class, $orderDiscountLevelData, [
            'orderDiscountLevel' => null,
            'domainId' => $orderDiscountLevelData->domainId,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $orderDiscountLevelData = $form->getData();

            $orderDiscountLevel = $this->orderDiscountLevelFacade->create($orderDiscountLevelData);

            $this->addSuccessFlashTwig(
                t('<a href="{{ url }}">Sleva na celý nákup</a> byla úspěšně vytvořena'),
                [
                        'url' => $this->generateUrl('admin_orderdiscountlevel_edit', ['id' => $orderDiscountLevel->getId()]),
                    ]
            );

            return $this->redirectToRoute('admin_orderdiscountlevel_list');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addErrorFlash(t('Please check the correctness of all data filled.'));
        }

        return $this->render('Admin/Content/Order/DiscountLevel/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/pricing/order-discount-level/edit/{id}")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, int $id): Response
    {
        try {
            $orderDiscountLevel = $this->orderDiscountLevelFacade->getById($id);
        } catch (OrderDiscountLevelNotFoundException $ex) {
            $this->addErrorFlash(t('Sleva na celý nákup neexistuje'));

            return $this->redirectToRoute('admin_orderdiscountlevel_list');
        }

        $orderDiscountLevelData = $this->orderDiscountLevelDataFactory->createFromOrderDiscountLevel($orderDiscountLevel);

        $form = $this->createForm(OrderDiscountLevelFormType::class, $orderDiscountLevelData, [
            'orderDiscountLevel' => $orderDiscountLevel,
            'domainId' => $orderDiscountLevel->getDomainId(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $orderDiscountLevelData = $form->getData();

            $this->orderDiscountLevelFacade->edit($orderDiscountLevel, $orderDiscountLevelData);

            $this->addSuccessFlashTwig(
                t('<a href="{{ url }}">Sleva na celý nákup</a> byla úspěšně upravena'),
                [
                        'url' => $this->generateUrl('admin_orderdiscountlevel_edit', ['id' => $orderDiscountLevel->getId()]),
                    ]
            );

            return $this->redirectToRoute('admin_orderdiscountlevel_list');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addErrorFlash(t('Please check the correctness of all data filled.'));
        }

        return $this->render('Admin/Content/Order/DiscountLevel/edit.html.twig', [
            'form' => $form->createView(),
            'orderDiscountLevel' => $orderDiscountLevel,
        ]);
    }

    /**
     * @CsrfProtection
     *
     * @Route("/pricing/order-discount-level/{id}")
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(int $id): Response
    {
        try {
            $this->orderDiscountLevelFacade->delete($id);

            $this->addSuccessFlashTwig(
                t('Sleva na celý nákup byla smazána')
            );
        } catch (OrderDiscountLevelNotFoundException $exception) {
            $this->addErrorFlash(t('Vybraná sleva na celý nákup neexistuje'));
        }

        return $this->redirectToRoute('admin_orderdiscountlevel_list');
    }
}
