<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Admin;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Controller\Admin\AdminBaseController;
use Shopsys\ShopBundle\Form\Admin\BushmanClubFormType;
use Shopsys\ShopBundle\Model\BushmanClub\BushmanClubFacade;
use Symfony\Component\HttpFoundation\Request;

class BushmanClubController extends AdminBaseController
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade
     */
    protected $adminDomainTabsFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\BushmanClub\BushmanClubFacade
     */
    private $bushmanClubFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade
     * @param \Shopsys\ShopBundle\Model\BushmanClub\BushmanClubFacade $bushmanClubFacade
     */
    public function __construct(
        AdminDomainTabsFacade $adminDomainTabsFacade,
        BushmanClubFacade $bushmanClubFacade
    ) {
        $this->adminDomainTabsFacade = $adminDomainTabsFacade;
        $this->bushmanClubFacade = $bushmanClubFacade;
    }

    /**
     * @Route("/bushman-club/setting/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function settingAction(Request $request)
    {
        $selectedDomainId = $this->adminDomainTabsFacade->getSelectedDomainId();
        $bushmanClubArticle = $this->bushmanClubFacade->findBushmanClubArticleByDomainId($selectedDomainId);

        $form = $this->createForm(BushmanClubFormType::class, [BushmanClubFormType::FIELD_BUSHMAN_ARTICLE => $bushmanClubArticle], [
            'domain_id' => $selectedDomainId,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $bushmanClubArticle = $form->getData()[BushmanClubFormType::FIELD_BUSHMAN_ARTICLE];

            $this->bushmanClubFacade->setBushmanClubArticleOnDomain(
                $bushmanClubArticle,
                $selectedDomainId
            );

            $this->getFlashMessageSender()->addSuccessFlashTwig(t('Článek pro Bushman Club uložen.'));
            return $this->redirectToRoute('admin_bushmanclub_setting');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->getFlashMessageSender()->addErrorFlashTwig(t('Please check the correctness of all data filled.'));
        }

        return $this->render('@ShopsysShop/Admin/Content/BushmanClub/setting.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
