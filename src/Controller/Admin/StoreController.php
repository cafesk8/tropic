<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Form\Admin\StoreFormType;
use App\Model\Store\Exception\StoreNotFoundException;
use App\Model\Store\StoreDataFactory;
use App\Model\Store\StoreFacade;
use App\Model\Store\StoreGridFactory;
use Shopsys\FrameworkBundle\Controller\Admin\AdminBaseController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StoreController extends AdminBaseController
{
    /**
     * @var \App\Model\Store\StoreFacade
     */
    private $storeFacade;

    /**
     * @var \App\Model\Store\StoreDataFactory
     */
    private $storeDataFactory;

    /**
     * @var \App\Model\Store\StoreGridFactory
     */
    private $storeGridFactory;

    /**
     * @param \App\Model\Store\StoreFacade $storeFacade
     * @param \App\Model\Store\StoreDataFactory $storeDataFactory
     * @param \App\Model\Store\StoreGridFactory $storeGridFactory
     */
    public function __construct(StoreFacade $storeFacade, StoreDataFactory $storeDataFactory, StoreGridFactory $storeGridFactory)
    {
        $this->storeFacade = $storeFacade;
        $this->storeDataFactory = $storeDataFactory;
        $this->storeGridFactory = $storeGridFactory;
    }

    /**
     * @Route("/store/new/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request): Response
    {
        $storeData = $this->storeDataFactory->create();

        $form = $this->createForm(StoreFormType::class, $storeData, [
            'store' => null,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $storeData = $form->getData();

            $store = $this->storeFacade->create($storeData);

            $this
                ->addSuccessFlashTwig(
                    t('Store <strong><a href="{{ url }}">{{ name }}</a></strong> successfully created'),
                    [
                        'name' => $store->getName(),
                        'url' => $this->generateUrl('admin_store_edit', ['id' => $store->getId()]),
                    ]
                );

            return $this->redirectToRoute('admin_store_list');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addErrorFlash(t('Please check the correctness of all data filled.'));
        }

        return $this->render('Admin/Content/Store/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/store/edit/{id}")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, int $id): Response
    {
        try {
            $store = $this->storeFacade->getById($id);
        } catch (StoreNotFoundException $ex) {
            $this->addErrorFlash(t('Store doesn\'t exist'));

            return $this->redirectToRoute('admin_store_list');
        }

        $storeData = $this->storeDataFactory->createFromStore($store);

        $form = $this->createForm(StoreFormType::class, $storeData, [
            'store' => $store,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $storeData = $form->getData();

            $this->storeFacade->edit($store, $storeData);

            $this
                ->addSuccessFlashTwig(
                    t('Store <strong><a href="{{ url }}">{{ name }}</a></strong> modified'),
                    [
                        'name' => $store->getName(),
                        'url' => $this->generateUrl('admin_store_edit', ['id' => $store->getId()]),
                    ]
                );
            return $this->redirectToRoute('admin_store_list');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addErrorFlash(t('Please check the correctness of all data filled.'));
        }

        return $this->render('Admin/Content/Store/edit.html.twig', [
            'form' => $form->createView(),
            'store' => $store,
        ]);
    }

    /**
     * @Route("/store/list/")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(): Response
    {
        $grid = $this->storeGridFactory->create();

        return $this->render('Admin/Content/Store/list.html.twig', [
            'gridView' => $grid->createView(),
        ]);
    }

    /**
     * @Route("/store/delete/{id}")
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(int $id): Response
    {
        try {
            $storeName = $this->storeFacade->getById($id)->getName();

            $this->storeFacade->delete($id);

            $this->addSuccessFlashTwig(
                t('Store <strong>{{ name }}</strong> has been removed'),
                [
                    'name' => $storeName,
                ]
            );
        } catch (StoreNotFoundException $exception) {
            $this->addErrorFlash(t('Selected store doesn\'t exist'));
        }

        return $this->redirectToRoute('admin_store_list');
    }
}
