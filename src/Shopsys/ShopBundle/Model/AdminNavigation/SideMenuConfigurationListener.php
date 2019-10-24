<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\AdminNavigation;

use Knp\Menu\ItemInterface;
use Shopsys\FrameworkBundle\Model\AdminNavigation\ConfigureMenuEvent;
use Shopsys\ShopBundle\Model\Administrator\Role;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SideMenuConfigurationListener
{
    /**
     * @var \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @param \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\AdminNavigation\ConfigureMenuEvent $event
     */
    public function onRootConfigure(ConfigureMenuEvent $event): void
    {
        $menu = $event->getMenu();

        $this->removeItemFromMenuIfPrivilegeIsNotGranted($menu, 'orders', Role::VIEW_ORDERS);
        $this->removeItemFromMenuIfPrivilegeIsNotGranted($menu, 'customers', Role::VIEW_CUSTOMERS);
        $this->removeItemFromMenuIfPrivilegeIsNotGranted($menu, 'products', Role::VIEW_PRODUCTS);
        $this->removeItemFromMenuIfPrivilegeIsNotGranted($menu, 'pricing', Role::VIEW_PRICING);
        $this->removeItemFromMenuIfPrivilegeIsNotGranted($menu, 'marketing', Role::VIEW_MARKETING);
        $this->removeItemFromMenuIfPrivilegeIsNotGranted($menu, 'administrators', Role::VIEW_ADMINISTRATORS);
        $this->removeItemFromMenuIfPrivilegeIsNotGranted($menu, 'settings', Role::VIEW_SETTINGS);
    }

    /**
     * @param \Knp\Menu\ItemInterface $menu
     * @param string $itemName
     * @param string $privilege
     */
    private function removeItemFromMenuIfPrivilegeIsNotGranted(ItemInterface $menu, string $itemName, string $privilege): void
    {
        if ($this->authorizationChecker->isGranted($privilege) === false) {
            $menu->removeChild($itemName);
        }
    }
}
