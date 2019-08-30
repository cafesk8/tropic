<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Admin;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Controller\Admin\AdminBaseController;
use Shopsys\ShopBundle\Component\Setting\Setting;
use Shopsys\ShopBundle\Form\Admin\DeliveryDateSettingFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DeliveryDateController extends AdminBaseController
{
    /**
     * @var \Shopsys\ShopBundle\Component\Setting\Setting
     */
    private $setting;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade
     */
    private $adminDomainTabsFacade;

    /**
     * @param \Shopsys\ShopBundle\Component\Setting\Setting $setting
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
     * @Route("/delivery-date/setting/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function settingAction(Request $request): Response
    {
        $formData = [
            'hours' => $this->setting->getForDomain(Setting::ORDER_TRANSPORT_DEADLINE_HOURS, $this->adminDomainTabsFacade->getSelectedDomainId()),
            'minutes' => $this->setting->getForDomain(Setting::ORDER_TRANSPORT_DEADLINE_MINUTES, $this->adminDomainTabsFacade->getSelectedDomainId()),
        ];

        $form = $this->createForm(DeliveryDateSettingFormType::class, $formData);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $hours = $form->get('deadline')->get('hours')->getData();
            $minutes = $form->get('deadline')->get('minutes')->getData();
            $this->setting->setForDomain(Setting::ORDER_TRANSPORT_DEADLINE_HOURS, $hours, $this->adminDomainTabsFacade->getSelectedDomainId());
            $this->setting->setForDomain(Setting::ORDER_TRANSPORT_DEADLINE_MINUTES, $minutes, $this->adminDomainTabsFacade->getSelectedDomainId());

            $this->getFlashMessageSender()->addSuccessFlash(t('Nastavení bylo uloženo.'));
        }

        return $this->render('@ShopsysShop/Admin/Content/DeliveryDate/setting.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
