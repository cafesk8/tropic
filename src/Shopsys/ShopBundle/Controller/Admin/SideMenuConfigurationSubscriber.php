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
            ConfigureMenuEvent::SIDE_MENU_SETTINGS => 'configureSettingsMenu',
            ConfigureMenuEvent::SIDE_MENU_MARKETING => 'configureMarketingMenu',
        ];
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
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\AdminNavigation\ConfigureMenuEvent $event
     */
    public function configureMarketingMenu(ConfigureMenuEvent $event): void
    {
        $marketingMenu = $event->getMenu();

        $marketingMenu->addChild('stores', [
            'route' => 'admin_inforow_detail',
            'label' => t('Informační řádek'),
        ]);
    }
}
