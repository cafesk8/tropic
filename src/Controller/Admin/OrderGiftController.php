<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Form\Admin\OrderGiftFormType;
use App\Model\Order\Exception\OrderGiftNotFoundException;
use App\Model\Order\Gift\OrderGiftDataFactory;
use App\Model\Order\Gift\OrderGiftFacade;
use App\Model\Order\Gift\OrderGiftGridFactory;
use App\Model\Pricing\Currency\CurrencyFacade;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Component\Router\Security\Annotation\CsrfProtection;
use Shopsys\FrameworkBundle\Controller\Admin\AdminBaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OrderGiftController extends AdminBaseController
{
    /**
     * @var \App\Model\Order\Gift\OrderGiftDataFactory
     */
    protected $orderGiftDataFactory;

    /**
     * @var \App\Model\Order\Gift\OrderGiftFacade
     */
    protected $orderGiftFacade;

    /**
     * @var \App\Model\Order\Gift\OrderGiftGridFactory
     */
    protected $orderGiftGridFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade
     */
    protected $adminDomainTabsFacade;

    /**
     * @var \App\Model\Pricing\Currency\CurrencyFacade
     */
    protected $currencyFacade;

    /**
     * @param \App\Model\Order\Gift\OrderGiftDataFactory $orderGiftDataFactory
     * @param \App\Model\Order\Gift\OrderGiftFacade $orderGiftFacade
     * @param \App\Model\Order\Gift\OrderGiftGridFactory $orderGiftGridFactory
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade
     * @param \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     */
    public function __construct(
        OrderGiftDataFactory $orderGiftDataFactory,
        OrderGiftFacade $orderGiftFacade,
        OrderGiftGridFactory $orderGiftGridFactory,
        AdminDomainTabsFacade $adminDomainTabsFacade,
        CurrencyFacade $currencyFacade
    ) {
        $this->orderGiftDataFactory = $orderGiftDataFactory;
        $this->orderGiftFacade = $orderGiftFacade;
        $this->orderGiftGridFactory = $orderGiftGridFactory;
        $this->adminDomainTabsFacade = $adminDomainTabsFacade;
        $this->currencyFacade = $currencyFacade;
    }

    /**
     * @Route("/product/order-gift/list/")
     */
    public function listAction()
    {
        $grid = $this->orderGiftGridFactory->createForDomain($this->adminDomainTabsFacade->getSelectedDomainId());

        return $this->render('Admin/Content/Order/Gift/list.html.twig', [
            'gridView' => $grid->createView(),
        ]);
    }

    /**
     * @Route("/product/order-gift/new/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request): Response
    {
        $orderGiftData = $this->orderGiftDataFactory->createForDomainId($this->adminDomainTabsFacade->getSelectedDomainId());

        $form = $this->createForm(OrderGiftFormType::class, $orderGiftData, [
            'orderGift' => null,
            'domainId' => $orderGiftData->domainId,
        ]);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $orderGiftData = $form->getData();

            $orderGift = $this->orderGiftFacade->create($orderGiftData);

            $this->getFlashMessageSender()
                ->addSuccessFlashTwig(
                    t('<a href="{{ url }}">Dárek k objednávce</a> byl úspěšně vytvořen'),
                    [
                        'url' => $this->generateUrl('admin_ordergift_edit', ['id' => $orderGift->getId()]),
                    ]
                );

            return $this->redirectToRoute('admin_ordergift_list');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->getFlashMessageSender()->addErrorFlash(t('Please check the correctness of all data filled.'));
        }

        return $this->render('Admin/Content/Order/Gift/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/product/order-gift/edit/{id}")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, int $id): Response
    {
        try {
            $orderGift = $this->orderGiftFacade->getById($id);
        } catch (OrderGiftNotFoundException $ex) {
            $this->getFlashMessageSender()->addErrorFlash(t('Dárek k objednávce neexistuje'));

            return $this->redirectToRoute('admin_ordergift_list');
        }

        $orderGiftData = $this->orderGiftDataFactory->createFromOrderGift($orderGift);

        $form = $this->createForm(OrderGiftFormType::class, $orderGiftData, [
            'orderGift' => $orderGift,
            'domainId' => $orderGift->getDomainId(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $orderGiftData = $form->getData();

            $this->orderGiftFacade->edit($orderGift, $orderGiftData);

            $this->getFlashMessageSender()
                ->addSuccessFlashTwig(
                    t('<a href="{{ url }}">Dárek k objednávce</a> byl úspěšně upraven'),
                    [
                        'url' => $this->generateUrl('admin_ordergift_edit', ['id' => $orderGift->getId()]),
                    ]
                );

            return $this->redirectToRoute('admin_ordergift_list');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->getFlashMessageSender()->addErrorFlash(t('Please check the correctness of all data filled.'));
        }

        return $this->render('Admin/Content/Order/Gift/edit.html.twig', [
            'form' => $form->createView(),
            'orderGift' => $orderGift,
        ]);
    }

    /**
     * @CsrfProtection
     *
     * @Route("/product/order-gift/delete/{id}")
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(int $id): Response
    {
        try {
            $this->orderGiftFacade->delete($id);

            $this->getFlashMessageSender()->addSuccessFlashTwig(
                t('Dárek byl smazán')
            );
        } catch (OrderGiftNotFoundException $exception) {
            $this->getFlashMessageSender()->addErrorFlash(t('Vybraný dárek neexistuje'));
        }

        return $this->redirectToRoute('admin_ordergift_list');
    }

    /**
     * @Route("/product/order-gift/get-currency-symbol/", methods={"post"}, condition="request.isXmlHttpRequest()")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function currencySymbolAction(Request $request)
    {
        $domainId = $request->get('domainId');
        $currencySymbol = $this->currencyFacade->getDomainDefaultCurrencyByDomainId($domainId)->getCode();

        $responseData = ['success' => true, 'currencySymbol' => $currencySymbol];

        return new JsonResponse($responseData);
    }
}
