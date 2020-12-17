<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Order\PromoCode\Grid\PromoCodeGridFactory;
use App\Model\Order\PromoCode\PromoCodeFacade;
use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Component\Router\Security\Annotation\CsrfProtection;
use Shopsys\FrameworkBundle\Controller\Admin\PromoCodeController as BasePromoCodeController;
use Shopsys\FrameworkBundle\Form\Admin\PromoCode\PromoCodeFormType;
use Shopsys\FrameworkBundle\Form\Admin\QuickSearch\QuickSearchFormData;
use Shopsys\FrameworkBundle\Form\Admin\QuickSearch\QuickSearchFormType;
use Shopsys\FrameworkBundle\Model\Administrator\AdministratorGridFacade;
use Shopsys\FrameworkBundle\Model\AdminNavigation\BreadcrumbOverrider;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeDataFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @property \App\Model\Order\PromoCode\PromoCodeFacade $promoCodeFacade
 * @method setPromoCodeGridFactory(\App\Model\Order\PromoCode\Grid\PromoCodeGridFactory $promoCodeGridFactory)
 * @method \App\Model\Administrator\Administrator getUser()
 * @property \App\Model\Order\PromoCode\Grid\PromoCodeGridFactory $promoCodeGridFactory
 */
class PromoCodeController extends BasePromoCodeController
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade
     */
    protected $adminDomainTabsFacade;

    /**
     * @param \App\Model\Order\PromoCode\PromoCodeFacade $promoCodeFacade
     * @param \Shopsys\FrameworkBundle\Model\Administrator\AdministratorGridFacade $administratorGridFacade
     * @param \Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeDataFactoryInterface $promoCodeDataFactory
     * @param \App\Model\Order\PromoCode\Grid\PromoCodeGridFactory $promoCodeGridFactory
     * @param \Shopsys\FrameworkBundle\Model\AdminNavigation\BreadcrumbOverrider $breadcrumbOverrider
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade
     */
    public function __construct(
        PromoCodeFacade $promoCodeFacade,
        AdministratorGridFacade $administratorGridFacade,
        PromoCodeDataFactoryInterface $promoCodeDataFactory,
        PromoCodeGridFactory $promoCodeGridFactory,
        BreadcrumbOverrider $breadcrumbOverrider,
        AdminDomainTabsFacade $adminDomainTabsFacade
    ) {
        parent::__construct($promoCodeFacade, $administratorGridFacade, $promoCodeDataFactory, $promoCodeGridFactory, $breadcrumbOverrider);

        $this->adminDomainTabsFacade = $adminDomainTabsFacade;
    }

    /**
     * @Route("/promo-code/list")
     * @param \Symfony\Component\HttpFoundation\Request|null $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(?Request $request = null): Response
    {
        $administrator = $this->getUser();

        $quickSearchForm = $this->createForm(QuickSearchFormType::class, new QuickSearchFormData());
        $quickSearchForm->handleRequest($request);

        $grid = $this->promoCodeGridFactory->create(true, $quickSearchForm->getData());
        $grid->enablePaging();

        $this->administratorGridFacade->restoreAndRememberGridLimit($administrator, $grid);

        return $this->render('@ShopsysFramework/Admin/Content/PromoCode/list.html.twig', [
            'gridView' => $grid->createView(),
            'quickSearchForm' => $quickSearchForm->createView(),
        ]);
    }

    /**
     * @Route("/promo-code/new-mass-generate")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newMassGenerateAction(Request $request): Response
    {
        /** @var \App\Model\Order\PromoCode\PromoCodeData $promoCodeData */
        $promoCodeData = $this->promoCodeDataFactory->create();
        $promoCodeData->massGenerate = true;

        $form = $this->createForm(PromoCodeFormType::class, $promoCodeData, [
            'promo_code' => null,
            'mass_generate' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->promoCodeFacade->massCreate($promoCodeData);

                $this->addSuccessFlashTwig(
                    t('Bylo vytvořeno <strong>{{ quantity }}</strong> slevových kupónů'),
                    [
                        'quantity' => $promoCodeData->quantity,
                    ]
                );

                return $this->redirectToRoute('admin_promocode_list');
            } else {
                $this->addErrorFlashTwig(t('Please check the correctness of all data filled.'));
            }
        }

        return $this->render('Admin/Content/PromoCode/newMassGenerate.html.twig', [
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
        $this->addSuccessFlash(t('Slevové kupóny byly smazány.'));

        return $this->redirectToRoute('admin_promocode_list');
    }
}
