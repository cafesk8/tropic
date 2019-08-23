<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Breadcrumb;

use Shopsys\FrameworkBundle\Model\Breadcrumb\SimpleBreadcrumbGenerator as BaseSimpleBreadcrumbGenerator;

class SimpleBreadcrumbGenerator extends BaseSimpleBreadcrumbGenerator
{
    /**
     * @return string[]
     */
    protected function getRouteNameMap(): array
    {
        if ($this->routeNameMap === null) {
            parent::getRouteNameMap();
            $this->routeNameMap['front_store_index'] = t('Prodejny');
            $this->routeNameMap['front_contact_index'] = t('Kontakt');
            $this->routeNameMap['front_about_us_info'] = t('O nás');
        }

        return $this->routeNameMap;
    }
}
