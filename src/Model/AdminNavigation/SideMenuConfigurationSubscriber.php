<?php

declare(strict_types=1);

namespace App\Model\AdminNavigation;

use Shopsys\FrameworkBundle\Model\AdminNavigation\ConfigureMenuEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SideMenuConfigurationSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            ConfigureMenuEvent::SIDE_MENU_SETTINGS => 'configureSettingsMenu',
        ];
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\AdminNavigation\ConfigureMenuEvent $event
     */
    public function configureSettingsMenu(ConfigureMenuEvent $event): void
    {
        $settingMenu = $event->getMenu();

        $superadminSettingMenu = $settingMenu->getChild('superadmin');

        if ($superadminSettingMenu !== null) {
            $superadminSettingMenu->addChild('migrations', ['route' => 'admin_superadmin_migrations', 'label' => t('Migrace - importy')]);
        }
    }
}
