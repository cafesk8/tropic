<?php

declare(strict_types=1);

namespace App\Model\Order\PromoCode\Listing;

use Shopsys\FrameworkBundle\Form\Admin\QuickSearch\QuickSearchFormData;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade;

class PromoCodeListAdminFacade
{
    /**
     * @var \App\Model\Order\PromoCode\Listing\PromoCodeListAdminRepository
     */
    protected $promoCodeListAdminRepository;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade
     */
    protected $pricingGroupSettingFacade;

    /**
     * @param \App\Model\Order\PromoCode\Listing\PromoCodeListAdminRepository $promoCodeListAdminRepository
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade $pricingGroupSettingFacade
     */
    public function __construct(
        PromoCodeListAdminRepository $promoCodeListAdminRepository,
        PricingGroupSettingFacade $pricingGroupSettingFacade
    ) {
        $this->promoCodeListAdminRepository = $promoCodeListAdminRepository;
        $this->pricingGroupSettingFacade = $pricingGroupSettingFacade;
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getPromoCodeListQueryBuilder()
    {
        return $this->promoCodeListAdminRepository->getPromoCodeListQueryBuilder();
    }

    /**
     * @param \Shopsys\FrameworkBundle\Form\Admin\QuickSearch\QuickSearchFormData $quickSearchData
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getPromoCodeListQueryBuilderByQuickSearchData(
        QuickSearchFormData $quickSearchData
    ) {
        return $this->promoCodeListAdminRepository->getPromoCodeListQueryBuilderByQuickSearchData(
            $quickSearchData
        );
    }
}
