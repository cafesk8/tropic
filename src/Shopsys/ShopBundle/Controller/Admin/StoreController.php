<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Admin;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Controller\Admin\AdminBaseController;
use Shopsys\ShopBundle\Form\Admin\StoreFormType;
use Shopsys\ShopBundle\Model\Store\Exception\StoreNotFoundException;
use Shopsys\ShopBundle\Model\Store\StoreDataFactory;
use Shopsys\ShopBundle\Model\Store\StoreFacade;
use Shopsys\ShopBundle\Model\Store\StoreGridFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StoreController extends AdminBaseController
{
    /**
     * @var \Shopsys\ShopBundle\Model\Store\StoreFacade
     */
    private $storeFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Store\StoreDataFactory
     */
    private $storeDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade
     */
    private $adminDomainTabsFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Store\StoreGridFactory
     */
    private $storeGridFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \Shopsys\ShopBundle\Model\Store\StoreFacade $storeFacade
     * @param \Shopsys\ShopBundle\Model\Store\StoreDataFactory $storeDataFactory
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade
     * @param \Shopsys\ShopBundle\Model\Store\StoreGridFactory $storeGridFactory
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(
        StoreFacade $storeFacade,
        StoreDataFactory $storeDataFactory,
        AdminDomainTabsFacade $adminDomainTabsFacade,
        StoreGridFactory $storeGridFactory,
        Domain $domain
    ) {
        $this->storeFacade = $storeFacade;
        $this->storeDataFactory = $storeDataFactory;
        $this->adminDomainTabsFacade = $adminDomainTabsFacade;
        $this->storeGridFactory = $storeGridFactory;
        $this->domain = $domain;
    }

    /**
     * @Route("/store/new/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request): Response
    {
        $selectedDomainId = $this->adminDomainTabsFacade->getSelectedDomainId();
        $storeData = $this->storeDataFactory->createForDomainId($selectedDomainId);

        $form = $this->createForm(StoreFormType::class, $storeData, [
            'store' => null,
            'domain_id' => $this->domain->getId(),
        ]);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $storeData = $form->getData();

            $store = $this->storeFacade->create($storeData);

            $this->getFlashMessageSender()
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
            $this->getFlashMessageSender()->addErrorFlash(t('Please check the correctness of all data filled.'));
        }

        return $this->render('@ShopsysShop/Admin/Content/Store/new.html.twig', [
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
        $store = $this->storeFacade->getById($id);

        if ($store === null) {
            $this->getFlashMessageSender()->addErrorFlash(t('Store doesn\'t exist'));

            return $this->redirectToRoute('admin_store_list');
        }

        $storeData = $this->storeDataFactory->createFromStore($store);

        $form = $this->createForm(StoreFormType::class, $storeData, [
            'store' => $store,
            'domain_id' => $this->domain->getCurrentDomainConfig()->getId(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $storeData = $form->getData();

            $this->storeFacade->edit($store, $storeData);

            $this->getFlashMessageSender()
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
            $this->getFlashMessageSender()->addErrorFlash(t('Please check the correctness of all data filled.'));
        }

        return $this->render('@ShopsysShop/Admin/Content/Store/edit.html.twig', [
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

        return $this->render('@ShopsysShop/Admin/Content/Store/list.html.twig', [
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

            $this->getFlashMessageSender()->addSuccessFlashTwig(
                t('Store <strong>{{ name }}</strong> has been removed'),
                [
                    'name' => $storeName,
                ]
            );
        } catch (StoreNotFoundException $exception) {
            $this->getFlashMessageSender()->addErrorFlash(t('Selected store doesn\'t exist'));
        }

        return $this->redirectToRoute('admin_store_list');
    }
}
