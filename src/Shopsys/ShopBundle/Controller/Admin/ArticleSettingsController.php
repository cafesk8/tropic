<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Admin;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Controller\Admin\AdminBaseController;
use Shopsys\ShopBundle\Component\Setting\Setting;
use Shopsys\ShopBundle\Form\Admin\ArticleSettingsFormType;
use Shopsys\ShopBundle\Model\Article\ArticleFacade;
use Shopsys\ShopBundle\Model\Article\ArticleSettingDataFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ArticleSettingsController extends AdminBaseController
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade
     */
    protected $adminDomainTabsFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Article\ArticleFacade
     */
    private $articleFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Article\ArticleSettingDataFactory
     */
    private $articleSettingDataFactory;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade
     * @param \Shopsys\ShopBundle\Model\Article\ArticleFacade $articleFacade
     * @param \Shopsys\ShopBundle\Model\Article\ArticleSettingDataFactory $articleSettingDataFactory
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
     * @Route("/bushman-club/setting/")
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
                $articleSettingData->bushmanClubArticle,
                Setting::BUSHMAN_CLUB_ARTICLE_ID,
                $selectedDomainId
            );

            $this->articleFacade->setArticleOnDomainInSettings(
                $articleSettingData->ourValuesArticle,
                Setting::OUR_VALUES_ARTICLE_ID,
                $selectedDomainId
            );

            $this->articleFacade->setArticleOnDomainInSettings(
                $articleSettingData->ourStoryArticle,
                Setting::OUR_STORY_ARTICLE_ID,
                $selectedDomainId
            );

            $this->articleFacade->setArticleOnDomainInSettings(
                $articleSettingData->firstArticleOnHeaderMenu,
                Setting::FIRST_ARTICLE_ON_HEADER_MENU_ARTICLE_ID,
                $selectedDomainId
            );

            $this->articleFacade->setArticleOnDomainInSettings(
                $articleSettingData->secondArticleOnHeaderMenu,
                Setting::SECOND_ARTICLE_ON_HEADER_MENU_ARTICLE_ID,
                $selectedDomainId
            );

            $this->getFlashMessageSender()->addSuccessFlashTwig(t('Nastavení bylo uloženo.'));
            return $this->redirectToRoute('admin_articlesettings_setting');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->getFlashMessageSender()->addErrorFlashTwig(t('Please check the correctness of all data filled.'));
        }

        return $this->render('@ShopsysShop/Admin/Content/ArticleSettings/setting.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
