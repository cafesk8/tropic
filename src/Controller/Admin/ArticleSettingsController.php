<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Component\Setting\Setting;
use App\Form\Admin\ArticleSettingsFormType;
use App\Model\Article\ArticleFacade;
use App\Model\Article\ArticleSettingDataFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Controller\Admin\AdminBaseController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ArticleSettingsController extends AdminBaseController
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade
     */
    protected $adminDomainTabsFacade;

    /**
     * @var \App\Model\Article\ArticleFacade
     */
    private $articleFacade;

    /**
     * @var \App\Model\Article\ArticleSettingDataFactory
     */
    private $articleSettingDataFactory;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade
     * @param \App\Model\Article\ArticleFacade $articleFacade
     * @param \App\Model\Article\ArticleSettingDataFactory $articleSettingDataFactory
     */
    public function __construct(
        AdminDomainTabsFacade $adminDomainTabsFacade,
        ArticleFacade $articleFacade,
        ArticleSettingDataFactory $articleSettingDataFactory
    ) {
        $this->adminDomainTabsFacade = $adminDomainTabsFacade;
        $this->articleFacade = $articleFacade;
        $this->articleSettingDataFactory = $articleSettingDataFactory;
    }

    /**
     * @Route("/loyalty-club/setting/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function settingAction(Request $request): Response
    {
        $selectedDomainId = $this->adminDomainTabsFacade->getSelectedDomainId();

        $articleSettingData = $this->articleSettingDataFactory->createFromSettingDataByDomainId($selectedDomainId);

        $form = $this->createForm(ArticleSettingsFormType::class, $articleSettingData, [
            'domain_id' => $selectedDomainId,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->articleFacade->setArticleOnDomainInSettings(
                $articleSettingData->loyaltyProgramArticle,
                Setting::LOYALTY_PROGRAM_ARTICLE_ID,
                $selectedDomainId
            );

            $this->getFlashMessageSender()->addSuccessFlashTwig(t('Nastavení bylo uloženo.'));
            return $this->redirectToRoute('admin_articlesettings_setting');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->getFlashMessageSender()->addErrorFlashTwig(t('Please check the correctness of all data filled.'));
        }

        return $this->render('Admin/Content/ArticleSettings/setting.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
