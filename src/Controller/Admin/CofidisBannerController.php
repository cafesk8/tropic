<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Component\Domain\DomainHelper;
use App\Component\Setting\Setting;
use App\Form\Admin\CofidisBannerSettingFormType;
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
     * @param \App\Component\Setting\Setting $setting
     */
    public function __construct(Setting $setting)
    {
        $this->setting = $setting;
    }

    /**
     * @Route("/cofidis-banner/setting/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function settingAction(Request $request): Response
    {
        $formData = [
            'minimumPrice' => $this->setting->getForDomain(Setting::COFIDIS_BANNER_MINIMUM_SHOW_PRICE_ID, DomainHelper::CZECH_DOMAIN),
        ];
        $form = $this->createForm(CofidisBannerSettingFormType::class, $formData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $minimumPrice = $form->get('minimumPrice')->getData();
            $this->setting->setForDomain(Setting::COFIDIS_BANNER_MINIMUM_SHOW_PRICE_ID, $minimumPrice, DomainHelper::CZECH_DOMAIN);

            $this->addSuccessFlash(t('Nastavení bylo uloženo.'));
        }

        return $this->render('Admin/Content/CofidisBanner/setting.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
