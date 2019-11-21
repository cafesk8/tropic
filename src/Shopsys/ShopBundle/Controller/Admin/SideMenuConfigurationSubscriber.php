<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Admin;

use Shopsys\FrameworkBundle\Model\AdminNavigation\ConfigureMenuEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SideMenuConfigurationSubscriber implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ConfigureMenuEvent::SIDE_MENU_PRODUCTS => 'configureProductsMenu',
            ConfigureMenuEvent::SIDE_MENU_PRICING => 'configurePricingMenu',
            ConfigureMenuEvent::SIDE_MENU_MARKETING => 'configureMarketingMenu',
            ConfigureMenuEvent::SIDE_MENU_SETTINGS => 'configureSettingsMenu',
        ];
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\AdminNavigation\ConfigureMenuEvent $event
     */
    public function configureProductsMenu(ConfigureMenuEvent $event): void
    {
        $productsMenu = $event->getMenu();

        $productGiftMenu = $productsMenu->addChild('admin_productgift_list', ['route' => 'admin_productgift_list', 'label' => t('Dárky')]);
        $productGiftMenu->addChild('new', ['route' => 'admin_productgift_new', 'label' => t('Nový dárek'), 'display' => false]);
        $productGiftMenu->addChild('edit', ['route' => 'admin_productgift_edit', 'label' => t('Editace dárku'), 'display' => false]);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\AdminNavigation\ConfigureMenuEvent $event
     */
    public function configurePricingMenu(ConfigureMenuEvent $event): void
    {
        $pricingMenu = $event->getMenu();
        $promoCodeMenu = $pricingMenu->getChild('promo_codes');
        $promoCodeMenu->addChild('admin_promocode_new', ['route' => 'admin_promocode_new', 'label' => t('Vytvoření slevového kupónu'), 'display' => false]);
        $promoCodeMenu->addChild('admin_promocode_edit', ['route' => 'admin_promocode_edit', 'label' => t('Editace slevového kupónu'), 'display' => false]);
        $promoCodeMenu->addChild('admin_promocode_newmassgenerate', ['route' => 'admin_promocode_newmassgenerate', 'label' => t('Hromadné vytvoření slevových kupónů'), 'display' => false]);

        $transportAndPaymentMenu = $pricingMenu->getChild('free_transport_and_payment');
        $transportAndPaymentMenu->setLabel(t('Doprava zdarma'));
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\AdminNavigation\ConfigureMenuEvent $event
     */
    public function configureSettingsMenu(ConfigureMenuEvent $event): void
    {
        $settingMenu = $event->getMenu();

        $storeMenu = $settingMenu->addChild('stores', ['label' => t('Stores')]);
        $storeMenu->addChild('store_list', ['route' => 'admin_store_list', 'label' => t('Stores')]);
        $storeMenu->addChild('new', ['route' => 'admin_store_new', 'label' => t('New store'), 'display' => false]);
        $storeMenu->addChild('edit', ['route' => 'admin_store_edit', 'label' => t('Editing store'), 'display' => false]);

        $listsMenu = $settingMenu->getChild('lists');
        $parameterMenu = $listsMenu->getChild('parameters');
        $parameterMenu->addChild('parametervalue_list', [
            'route' => 'admin_parametervalue_list',
            'label' => t('Hodnoty parametra'),
            'display' => false,
        ]);

        $otherSettingMenu = $settingMenu->addChild('other', ['label' => t('Ostatní')]);
        $otherSettingMenu->addChild('deliverydate_setting', ['route' => 'admin_deliverydate_setting', 'label' => t('Výpočet termínu dodání')]);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\AdminNavigation\ConfigureMenuEvent $event
     */
    public function configureMarketingMenu(ConfigureMenuEvent $event): void
    {
        $marketingMenu = $event->getMenu();

        $marketingMenu->addChild('articles_settings', ['route' => 'admin_articlesettings_setting', 'label' => t('Přiřazení článků')]);

        $marketingMenu->addChild('stores', [
            'route' => 'admin_inforow_detail',
            'label' => t('Informační řádek'),
        ]);

        $adverts = $marketingMenu->getChild('adverts');
        $adverts->setLabel('Bannery na hl. stránce');
        $adverts->getChild('new')->setLabel('Nový banner');
        $adverts->getChild('edit')->setLabel('Editace banneru');

        $blogMenu = $marketingMenu->addChild('blog', ['label' => t('Blog')]);

        $blogMenu->addChild('blog_category', ['route' => 'admin_blogcategory_list', 'label' => t('Blog categories')]);
        $blogMenu->addChild('blog_category_new', ['route' => 'admin_blogcategory_new', 'label' => t('New blog category'), 'display' => false]);
        $blogMenu->addChild('blog_category_edit', ['route' => 'admin_blogcategory_edit', 'label' => t('Editing blog category'), 'display' => false]);

        $blogMenu->addChild('blog_article', ['route' => 'admin_blogarticle_list', 'label' => t('Blog articles')]);
        $blogMenu->addChild('blog_article_new', ['route' => 'admin_blogarticle_new', 'label' => t('New blog article'), 'display' => false]);
        $blogMenu->addChild('blog_article_edit', ['route' => 'admin_blogarticle_edit', 'label' => t('Editing blog article'), 'display' => false]);
    }
}
