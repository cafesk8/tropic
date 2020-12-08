<?php

declare(strict_types=1);

namespace App\Model\Order\PromoCode\Listing;

use App\Model\Order\Order;
use App\Model\Order\PromoCode\PromoCode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Component\String\DatabaseSearching;
use Shopsys\FrameworkBundle\Form\Admin\QuickSearch\QuickSearchFormData;
use Shopsys\FrameworkBundle\Model\Localization\Localization;

class PromoCodeListAdminRepository
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $em;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Localization\Localization
     */
    protected $localization;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade
     */
    private $adminDomainTabsFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Model\Localization\Localization $localization
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade
     */
    public function __construct(EntityManagerInterface $em, Localization $localization, AdminDomainTabsFacade $adminDomainTabsFacade)
    {
        $this->em = $em;
        $this->localization = $localization;
        $this->adminDomainTabsFacade = $adminDomainTabsFacade;
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getPromoCodeListQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
            ->select('pc')
            ->from(PromoCode::class, 'pc')
            ->where('pc.domainId = :selectedDomainId')
            ->setParameter('selectedDomainId', $this->adminDomainTabsFacade->getSelectedDomainId());

        return $queryBuilder;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Form\Admin\QuickSearch\QuickSearchFormData $quickSearchData
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getPromoCodeListQueryBuilderByQuickSearchData(
        QuickSearchFormData $quickSearchData
    ) {
        $queryBuilder = $this->em->createQueryBuilder()
            ->select('pc')
            ->from(PromoCode::class, 'pc')
            ->where('pc.domainId = :selectedDomainId')
            ->setParameter('selectedDomainId', $this->adminDomainTabsFacade->getSelectedDomainId());

        if ($quickSearchData->text !== null && $quickSearchData->text !== '') {
            $queryBuilder
                ->leftJoin(Order::class, 'o', 'WITH', 'o.promoCodesCodes = pc.code')
                ->leftJoin('o.customerUser', 'cu')
                ->andWhere('
                    (
                        NORMALIZE(pc.code) LIKE NORMALIZE(:text)
                        OR
                        NORMALIZE(pc.certificateSku) LIKE NORMALIZE(:text)
                        OR
                        NORMALIZE(cu.email) LIKE NORMALIZE(:text)
                    )');
            $querySearchText = DatabaseSearching::getFullTextLikeSearchString($quickSearchData->text);
            $queryBuilder->setParameter('text', $querySearchText);
        }

        return $queryBuilder;
    }
}
