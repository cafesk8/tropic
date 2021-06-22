<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Product\Bestseller\BestsellerFacade;
use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Controller\Admin\AdminBaseController;
use Shopsys\FrameworkBundle\Form\Admin\Product\TopProduct\TopProductsFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BestsellerController extends AdminBaseController
{
    private BestsellerFacade $bestsellerFacade;

    private AdminDomainTabsFacade $adminDomainTabsFacade;

    /**
     * @param \App\Model\Product\Bestseller\BestsellerFacade $bestsellerFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade
     */
    public function __construct(BestsellerFacade $bestsellerFacade, AdminDomainTabsFacade $adminDomainTabsFacade)
    {
        $this->bestsellerFacade = $bestsellerFacade;
        $this->adminDomainTabsFacade = $adminDomainTabsFacade;
    }

    /**
     * @Route("/product/bestseller/list/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(Request $request): Response
    {
        $domainId = $this->adminDomainTabsFacade->getSelectedDomainId();
        $formData = [
            'products' => $this->getProductsForDomain($domainId),
        ];

        $form = $this->createForm(TopProductsFormType::class, $formData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $products = $form->getData()['products'];

            $this->bestsellerFacade->saveBestsellerForDomain($domainId, $products);

            $this->addSuccessFlash(t('Product settings on the main page successfully changed'));
        }

        return $this->render('Admin/Content/Bestseller/list.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param int $domainId
     * @return \App\Model\Product\Product[]
     */
    private function getProductsForDomain(int $domainId): array
    {
        $bestsellers = $this->bestsellerFacade->getAllByDomainId($domainId);
        $products = [];

        foreach ($bestsellers as $bestseller) {
            $products[] = $bestseller->getProduct();
        }

        return $products;
    }
}