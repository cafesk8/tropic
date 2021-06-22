<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Knp\Menu\Util\MenuManipulator;
use Shopsys\FrameworkBundle\Model\AdminNavigation\ConfigureMenuEvent;
use Shopsys\FrameworkBundle\Model\Security\Roles;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SideMenuConfigurationSubscriber implements EventSubscriberInterface
{
    private AuthorizationCheckerInterface $authorizationChecker;

    /**
     * @param \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

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

        $productGiftMenu = $productsMenu->addChild('admin_productgift_list', ['route' => 'admin_productgift_list', 'label' => t('Dárky k produktům')]);
        $productGiftMenu->addChild('new', ['route' => 'admin_productgift_new', 'label' => t('Nový dárek'), 'display' => false]);
        $productGiftMenu->addChild('edit', ['route' => 'admin_productgift_edit', 'label' => t('Editace dárku'), 'display' => false]);

        $orderGiftsMenu = $productsMenu->addChild('admin_ordergift_list', ['route' => 'admin_ordergift_list', 'label' => t('Dárky k objednávce')]);
        $orderGiftsMenu->addChild('new', ['route' => 'admin_ordergift_new', 'label' => t('Nový dárek k objednávce'), 'display' => false]);
        $orderGiftsMenu->addChild('edit', ['route' => 'admin_ordergift_edit', 'label' => t('Editace dárku k objednávce'), 'display' => false]);

        $productsMenu->addChild('admin_watchdog_list', ['route' => 'admin_watchdog_list', 'label' => t('Hlídač dostupnosti')]);
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

        $pricingMenu->removeChild('free_transport_and_payment');

        $pricingMenu->addChild('admin_discount_exclusion', [
            'label' => t('Vyjmutí ze slev'),
            'route' => 'admin_discountexclusion_detail',
        ]);

        $orderDiscountLevelMenu = $pricingMenu->addChild('admin_orderdiscountlevel_list', ['route' => 'admin_orderdiscountlevel_list', 'label' => t('Sleva na celý nákup')]);
        $orderDiscountLevelMenu->addChild('new', ['route' => 'admin_orderdiscountlevel_new', 'label' => t('Nová sleva na celý nákup'), 'display' => false]);
        $orderDiscountLevelMenu->addChild('edit', ['route' => 'admin_orderdiscountlevel_edit', 'label' => t('Editace slevy na celý nákup'), 'display' => false]);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\AdminNavigation\ConfigureMenuEvent $event
     */
    public function configureSettingsMenu(ConfigureMenuEvent $event): void
    {
        $settingMenu = $event->getMenu();

        $storeMenu = $settingMenu->addChild('stores', ['label' => t('Sklady a prodejny')]);
        $storeMenu->addChild('store_list', ['route' => 'admin_store_list', 'label' => t('Sklady a prodejny')]);
        $storeMenu->addChild('new', ['route' => 'admin_store_new', 'label' => t('Nový sklad'), 'display' => false]);
        $storeMenu->addChild('edit', ['route' => 'admin_store_edit', 'label' => t('Upravit sklad'), 'display' => false]);

        $listsMenu = $settingMenu->getChild('lists');
        $parameterMenu = $listsMenu->getChild('parameters');
        $parameterMenu->addChild('parametervalue_list', [
            'route' => 'admin_parametervalue_list',
            'label' => t('Hodnoty parametra'),
            'display' => false,
        ]);

        $superadminMenu = $settingMenu->getChild('superadmin');
        if ($superadminMenu !== null && $this->authorizationChecker->isGranted(Roles::ROLE_SUPER_ADMIN)) {
            $superadminMenu->addChild('migrations', ['route' => 'admin_superadmin_migrations', 'label' => t('Migrace')]);
        }

        $otherSettingMenu = $settingMenu->addChild('other', ['label' => t('Ostatní')]);
        $otherSettingMenu->addChild('deliverydate_setting', ['route' => 'admin_deliverydate_setting', 'label' => t('Výpočet termínu dodání')]);
        $otherSettingMenu->addChild('cofidisbanner_setting', ['route' => 'admin_cofidisbanner_setting', 'label' => t('Cofidis banner')]);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\AdminNavigation\ConfigureMenuEvent $event
     */
    public function configureMarketingMenu(ConfigureMenuEvent $event): void
    {
        $marketingMenu = $event->getMenu();

        $marketingMenu->addChild('header_text', [
            'route' => 'admin_headertextsetting_setting',
            'label' => t('Text v hlavičce'),
        ]);

        $priceBombProducts = $marketingMenu->addChild('price_bomb_products', [
            'route' => 'admin_pricebombproduct_list',
            'label' => t('Cenové bomby na titulní stránce'),
        ]);

        $marketingMenu->addChild('stores', [
            'route' => 'admin_inforow_detail',
            'label' => t('Informační řádek'),
        ]);

        $marketingMenu->addChild('top_products', ['route' => 'admin_topproduct_list', 'label' => t('Akce na titulní stránce')]);

        $marketingMenu->addChild('bestsellers', ['route' => 'admin_bestseller_list', 'label' => t('Bestsellery')]);

        $marketingMenu->removeChild('top_categories');
        $marketingMenu->removeChild('slider');

        $adverts = $marketingMenu->getChild('adverts');
        $adverts->setLabel(t('Bannery'));
        $adverts->getChild('new')->setLabel('Nový banner');
        $adverts->getChild('edit')->setLabel('Editace banneru');

        $blogMenu = $marketingMenu->addChild('blog', ['label' => t('Blog')]);

        $blogMenu->addChild('blog_category', ['route' => 'admin_blogcategory_list', 'label' => t('Blog categories')]);
        $blogMenu->addChild('blog_category_new', ['route' => 'admin_blogcategory_new', 'label' => t('New blog category'), 'display' => false]);
        $blogMenu->addChild('blog_category_edit', ['route' => 'admin_blogcategory_edit', 'label' => t('Editing blog category'), 'display' => false]);

        $blogMenu->addChild('blog_article', ['route' => 'admin_blogarticle_list', 'label' => t('Blog articles')]);
        $blogMenu->addChild('blog_article_new', ['route' => 'admin_blogarticle_new', 'label' => t('New blog article'), 'display' => false]);
        $blogMenu->addChild('blog_article_edit', ['route' => 'admin_blogarticle_edit', 'label' => t('Editing blog article'), 'display' => false]);

        $menuManipulator = new MenuManipulator();
        $menuManipulator->moveToPosition($adverts, 1);
        $menuManipulator->moveToPosition($priceBombProducts, 4);
    }
}
