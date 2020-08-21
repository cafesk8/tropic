<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Shopsys\FrameworkBundle\Controller\Admin\ShopInfoController as BaseShopInfoController;
use Shopsys\FrameworkBundle\Form\Admin\ShopInfo\ShopInfoSettingFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @property \App\Model\ShopInfo\ShopInfoSettingFacade $shopInfoSettingFacade
 * @method setOpeningHours($value, $domainId)
 * @method getOpeningHours($value, $domainId)
 */
class ShopInfoController extends BaseShopInfoController
{    
    /**
     * @Route("/shop-info/setting/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function settingAction(Request $request)
    {
        $selectedDomainId = $this->adminDomainTabsFacade->getSelectedDomainId();
        $shopInfoSettingData = [
            'phoneNumber' => $this->shopInfoSettingFacade->getPhoneNumber($selectedDomainId),
            'email' => $this->shopInfoSettingFacade->getEmail($selectedDomainId),
            'phoneHours' => $this->shopInfoSettingFacade->getPhoneHours($selectedDomainId),
            'openingHours' => $this->shopInfoSettingFacade->getOpeningHours($selectedDomainId),
        ];

        $form = $this->createForm(ShopInfoSettingFormType::class, $shopInfoSettingData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $shopInfoSettingData = $form->getData();

            $this->shopInfoSettingFacade->setPhoneNumber($shopInfoSettingData['phoneNumber'], $selectedDomainId);
            $this->shopInfoSettingFacade->setEmail($shopInfoSettingData['email'], $selectedDomainId);
            $this->shopInfoSettingFacade->setPhoneHours($shopInfoSettingData['phoneHours'], $selectedDomainId);
            $this->shopInfoSettingFacade->setOpeningHours($shopInfoSettingData['openingHours'], $selectedDomainId);

            $this->addSuccessFlash(t('E-shop attributes settings modified'));

            return $this->redirectToRoute('admin_shopinfo_setting');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addErrorFlashTwig(t('Please check the correctness of all data filled.'));
        }

        return $this->render('@ShopsysFramework/Admin/Content/ShopInfo/shopInfo.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}