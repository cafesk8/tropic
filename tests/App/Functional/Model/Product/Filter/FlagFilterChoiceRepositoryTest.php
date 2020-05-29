<?php

declare(strict_types=1);

namespace Tests\App\Functional\Model\Product\Filter;

use App\DataFixtures\Demo\CategoryDataFixture;
use App\DataFixtures\Demo\PricingGroupDataFixture;
use App\Model\Product\Flag\Flag;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Product\Filter\FlagFilterChoiceRepository;
use Tests\App\Test\TransactionFunctionalTestCase;

class FlagFilterChoiceRepositoryTest extends TransactionFunctionalTestCase
{
    public function testFlagFilterChoicesFromCategoryWithOneFlag(): void
    {
        $flagFilterChoices = $this->getChoicesForCategoryReference(CategoryDataFixture::CATEGORY_GARDEN_TOOLS);

        $this->assertCount(1, $flagFilterChoices);
    }

    public function testFlagFilterChoicesFromCategoryWithFlags(): void
    {
        $flagFilterChoices = $this->getChoicesForCategoryReference(CategoryDataFixture::CATEGORY_ELECTRONICS);

        $this->assertCount(3, $flagFilterChoices);

        $ids = array_map(
            static function (Flag $flag) {
                return $flag->getId();
            },
            $flagFilterChoices
        );

        $this->assertContains(1, $ids);
        $this->assertContains(8, $ids);
        $this->assertContains(4, $ids);
    }

    public function testGetFlagFilterChoicesForSearchPhone(): void
    {
        $this->skipTestIfFirstDomainIsNotInEnglish();

        $flagFilterChoices = $this->getChoicesForSearchText('phone');

        $this->assertCount(3, $flagFilterChoices);

        $ids = array_map(
            static function (Flag $flag) {
                return $flag->getId();
            },
            $flagFilterChoices
        );

        $this->assertContains(1, $ids);
        $this->assertContains(2, $ids);
        $this->assertContains(3, $ids);
    }

    public function testGetFlagFilterChoicesForBook(): void
    {
        $this->skipTestIfFirstDomainIsNotInEnglish();

        $flagFilterChoices = $this->getChoicesForSearchText('book');

        $this->assertCount(2, $flagFilterChoices);

        $ids = array_map(
            static function (Flag $flag) {
                return $flag->getId();
            },
            $flagFilterChoices
        );

        $this->assertContains(1, $ids);
        $this->assertContains(2, $ids);
    }

    /**
     * @param string $categoryReferenceName
     * @return \Shopsys\FrameworkBundle\Model\Product\Flag\Flag[]
     */
    protected function getChoicesForCategoryReference(string $categoryReferenceName): array
    {
        $repository = $this->getFlagFilterChoiceRepository();

        /** @var \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup $pricingGroup */
        $pricingGroup = $this->getReferenceForDomain(PricingGroupDataFixture::PRICING_GROUP_BASIC_DOMAIN, Domain::FIRST_DOMAIN_ID);

        /** @var \App\Model\Category\Category $category */
        $category = $this->getReference($categoryReferenceName);

        /** @var \Shopsys\FrameworkBundle\Component\Domain\Domain $domain */
        $domain = $this->getContainer()->get(Domain::class);
        $domainConfig1 = $domain->getDomainConfigById(Domain::FIRST_DOMAIN_ID);

        return $repository->getFlagFilterChoicesInCategory($domainConfig1->getId(), $pricingGroup, $domainConfig1->getLocale(), $category);
    }

    /**
     * @param string $searchText
     * @return \Shopsys\FrameworkBundle\Model\Product\Flag\Flag[]
     */
    protected function getChoicesForSearchText(string $searchText): array
    {
        $repository = $this->getFlagFilterChoiceRepository();

        /** @var \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup $pricingGroup */
        $pricingGroup = $this->getReferenceForDomain(PricingGroupDataFixture::PRICING_GROUP_BASIC_DOMAIN, Domain::FIRST_DOMAIN_ID);

        /** @var \Shopsys\FrameworkBundle\Component\Domain\Domain $domain */
        $domain = $this->getContainer()->get(Domain::class);
        $domainConfig1 = $domain->getDomainConfigById(Domain::FIRST_DOMAIN_ID);

        return $repository->getFlagFilterChoicesForSearch($domainConfig1->getId(), $pricingGroup, $domainConfig1->getLocale(), $searchText);
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Product\Filter\FlagFilterChoiceRepository
     */
    public function getFlagFilterChoiceRepository(): FlagFilterChoiceRepository
    {
        return $this->getContainer()->get(FlagFilterChoiceRepository::class);
    }
}
