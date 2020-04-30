<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Form\Admin\PriceBombProductsFormType;
use App\Model\Product\PriceBombProduct\PriceBombProductFacade;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Controller\Admin\AdminBaseController;
use Symfony\Component\HttpFoundation\Request;

class PriceBombProductController extends AdminBaseController
{
    /**
     * @var \App\Model\Product\PriceBombProduct\PriceBombProductFacade
     */
    protected $priceBombProductFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade
     */
    protected $adminDomainTabsFacade;

    /**
     * @param \App\Model\Product\PriceBombProduct\PriceBombProductFacade $priceBombProductFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade
     */
    public function __construct(
        PriceBombProductFacade $priceBombProductFacade,
        AdminDomainTabsFacade $adminDomainTabsFacade
    ) {
        $this->priceBombProductFacade = $priceBombProductFacade;
        $this->adminDomainTabsFacade = $adminDomainTabsFacade;
    }

    /**
     * @Route("/product/price-bomb-product/list/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function listAction(Request $request)
    {
        $domainId = $this->adminDomainTabsFacade->getSelectedDomainId();
        $formData = [
            'products' => $this->getProductsForDomain($domainId),
        ];

        $form = $this->createForm(PriceBombProductsFormType::class, $formData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $products = $form->getData()['products'];

            $this->priceBombProductFacade->savePriceBombProductsForDomain($domainId, $products);

            $this->addSuccessFlash(t('Product settings on the main page successfully changed'));
        }

        return $this->render('Admin/Content/PriceBombProduct/list.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param int $domainId
     * @return \App\Model\Product\Product[]
     */
    protected function getProductsForDomain(int $domainId): array
    {
        $priceBombProducts = $this->priceBombProductFacade->getAll($domainId);
        $products = [];

        foreach ($priceBombProducts as $priceBombProduct) {
            $products[] = $priceBombProduct->getProduct();
        }

        return $products;
    }
}
