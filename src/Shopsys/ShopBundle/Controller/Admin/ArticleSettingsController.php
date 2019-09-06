<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Admin;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Controller\Admin\AdminBaseController;
use Shopsys\ShopBundle\Component\Setting\Setting;
use Shopsys\ShopBundle\Form\Admin\ArticleSettingsFormType;
use Shopsys\ShopBundle\Model\Article\ArticleFacade;
use Symfony\Component\HttpFoundation\Request;

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
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade
     * @param \Shopsys\ShopBundle\Model\Article\ArticleFacade $articleFacade
     */
    public function __construct(
        AdminDomainTabsFacade $adminDomainTabsFacade,
        ArticleFacade $articleFacade
    ) {
        $this->adminDomainTabsFacade = $adminDomainTabsFacade;
        $this->articleFacade = $articleFacade;
    }

    /**
     * @Route("/bushman-club/setting/")
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function settingAction(Request $request)
    {
        $selectedDomainId = $this->adminDomainTabsFacade->getSelectedDomainId();

        $bushmanClubArticle = $this->articleFacade->findArticleBySettingValueAndDomainId(Setting::BUSHMAN_CLUB_ARTICLE_ID, $selectedDomainId);
        $ourValuesArticle = $this->articleFacade->findArticleBySettingValueAndDomainId(Setting::OUR_VALUES_ARTICLE_ID, $selectedDomainId);
        $ourStoryArticle = $this->articleFacade->findArticleBySettingValueAndDomainId(Setting::OUR_STORY_ARTICLE_ID, $selectedDomainId);
        $firstHeaderArticle = $this->articleFacade->findArticleBySettingValueAndDomainId(Setting::FIRST_ARTICLE_ON_HEADER_MENU_ARTICLE_ID, $selectedDomainId);
        $secondHeaderArticle = $this->articleFacade->findArticleBySettingValueAndDomainId(Setting::SECOND_ARTICLE_ON_HEADER_MENU_ARTICLE_ID, $selectedDomainId);

        $form = $this->createForm(ArticleSettingsFormType::class, [
            ArticleSettingsFormType::FIELD_BUSHMAN_ARTICLE => $bushmanClubArticle,
            ArticleSettingsFormType::FIELD_OUR_VALUES_ARTICLE => $ourValuesArticle,
            ArticleSettingsFormType::FIELD_OUR_STORY_ARTICLE => $ourStoryArticle,
            ArticleSettingsFormType::FIELD_FIRST_HEADER_ARTICLE => $firstHeaderArticle,
            ArticleSettingsFormType::FIELD_SECOND_HEADER_ARTICLE => $secondHeaderArticle,
        ], [
            'domain_id' => $selectedDomainId,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $bushmanClubArticle = $form->getData()[ArticleSettingsFormType::FIELD_BUSHMAN_ARTICLE];
            $ourValuesArticle = $form->getData()[ArticleSettingsFormType::FIELD_OUR_VALUES_ARTICLE];
            $ourStoryArticle = $form->getData()[ArticleSettingsFormType::FIELD_OUR_STORY_ARTICLE];
            $firstHeaderArticle = $form->getData()[ArticleSettingsFormType::FIELD_FIRST_HEADER_ARTICLE];
            $secondHeaderArticle = $form->getData()[ArticleSettingsFormType::FIELD_SECOND_HEADER_ARTICLE];

            $this->articleFacade->setArticleOnDomainInSettings(
                $bushmanClubArticle,
                Setting::BUSHMAN_CLUB_ARTICLE_ID,
                $selectedDomainId
            );

            $this->articleFacade->setArticleOnDomainInSettings(
                $ourValuesArticle,
                Setting::OUR_VALUES_ARTICLE_ID,
                $selectedDomainId
            );

            $this->articleFacade->setArticleOnDomainInSettings(
                $ourStoryArticle,
                Setting::OUR_STORY_ARTICLE_ID,
                $selectedDomainId
            );

            $this->articleFacade->setArticleOnDomainInSettings(
                $firstHeaderArticle,
                Setting::FIRST_ARTICLE_ON_HEADER_MENU_ARTICLE_ID,
                $selectedDomainId
            );

            $this->articleFacade->setArticleOnDomainInSettings(
                $secondHeaderArticle,
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
