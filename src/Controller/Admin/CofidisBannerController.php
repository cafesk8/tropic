<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Component\Setting\Setting;
use App\Form\Admin\CofidisBannerSettingFormType;
use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Controller\Admin\AdminBaseController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CofidisBannerController extends AdminBaseController
{
    /**
     * @var \App\Component\Setting\Setting
     */
    private $setting;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade
     */
    private $adminDomainTabsFacade;

    /**
     * @param \App\Component\Setting\Setting $setting
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade
     */
    public function __construct(
        Setting $setting,
        AdminDomainTabsFacade $adminDomainTabsFacade
    ) {
        $this->setting = $setting;
        $this->adminDomainTabsFacade = $adminDomainTabsFacade;
    }

    /**
     * @Route("/cofidis-banner/setting/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function settingAction(Request $request): Response
    {
        $formData = [
            'minimumPrice' => $this->setting->getForDomain(Setting::COFIDIS_BANNER_MINIMUM_SHOW_PRICE_ID, $this->adminDomainTabsFacade->getSelectedDomainId()),
        ];

        $form = $this->createForm(CofidisBannerSettingFormType::class, $formData, ['domainId' => $this->adminDomainTabsFacade->getSelectedDomainId()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $minimumPrice = $form->get('cofidisBanner')->get('minimumPrice')->getData();
            $this->setting->setForDomain(Setting::COFIDIS_BANNER_MINIMUM_SHOW_PRICE_ID, $minimumPrice, $this->adminDomainTabsFacade->getSelectedDomainId());

            $this->addSuccessFlash(t('Nastavení bylo uloženo.'));
        }

        return $this->render('Admin/Content/CofidisBanner/setting.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
