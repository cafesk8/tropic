<?php

declare(strict_types=1);

namespace Tests\App\Functional\Model\Product;

use App\DataFixtures\Demo\BrandDataFixture;
use App\DataFixtures\Demo\CategoryDataFixture;
use App\DataFixtures\Demo\FlagDataFixture;
use App\Model\Product\Filter\ProductFilterData;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Category\Category;
use Shopsys\FrameworkBundle\Model\Pricing\PriceConverter;
use Shopsys\FrameworkBundle\Model\Product\Filter\ParameterFilterData;
use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterConfigFactory;
use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterCountData;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterRepository;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValue;
use Shopsys\FrameworkBundle\Model\Product\ProductOnCurrentDomainFacadeInterface;
use Tests\App\Test\ParameterTransactionFunctionalTestCase;

abstract class ProductOnCurrentDomainFacadeCountDataTest extends ParameterTransactionFunctionalTestCase
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterConfigFactory
     */
    protected $productFilterConfigFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    protected $domain;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\ProductOnCurrentDomainFacadeInterface
     */
    protected $productOnCurrentDomainFacade;

    protected function setUp()
    {
        parent::setUp();
        $this->productFilterConfigFactory = $this->getContainer()->get(ProductFilterConfigFactory::class);
        $this->domain = $this->getContainer()->get(Domain::class);
        $this->productOnCurrentDomainFacade = $this->getProductOnCurrentDomainFacade();
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Product\ProductOnCurrentDomainFacadeInterface
     */
    abstract public function getProductOnCurrentDomainFacade(): ProductOnCurrentDomainFacadeInterface;

    /**
     * @param \App\Model\Category\Category $category
     * @param \App\Model\Product\Filter\ProductFilterData $filterData
     * @param \Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterCountData $expectedCountData
     * @dataProvider categoryTestCasesProvider
     */
    public function testCategory(Category $category, ProductFilterData $filterData, ProductFilterCountData $expectedCountData): void
    {
        $filterConfig = $this->productFilterConfigFactory->createForCategory($this->domain->getId(), $this->domain->getLocale(), $category);
        $countData = $this->productOnCurrentDomainFacade->getProductFilterCountDataInCategory($category->getId(), $filterConfig, $filterData);

        $this->assertEquals($expectedCountData, $this->removeEmptyParameters($countData));
    }

    /**
     * @return array[]
     */
    public function categoryTestCasesProvider(): array
    {
        return [
            'no-filter' => $this->categoryNoFilterTestCase(),
            'one-flag' => $this->categoryOneFlagTestCase(),
            'two-brands' => $this->categoryTwoBrandsTestCase(),
            'all-flags-all-brands' => $this->categoryAllFlagsAllBrandsTestCase(),
            'price' => $this->categoryPriceTestCase(),
            'stock' => $this->categoryStockTestCase(),
            'flag-brand-parameters' => $this->categoryFlagBrandAndParametersTestCase(),
            'parameters' => $this->categoryParametersTestCase(),
        ];
    }

    /**
     * @param string $searchText
     * @param \App\Model\Product\Filter\ProductFilterData $filterData
     * @param \Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterCountData $expectedCountData
     * @dataProvider searchTestCasesProvider
     */
    public function testSearch(string $searchText, ProductFilterData $filterData, ProductFilterCountData $expectedCountData): void
    {
        $this->skipTestIfFirstDomainIsNotInEnglish();

        $filterConfig = $this->productFilterConfigFactory->createForSearch($this->domain->getId(), $this->domain->getLocale(), $searchText);
        $countData = $this->productOnCurrentDomainFacade->getProductFilterCountDataForSearch($searchText, $filterConfig, $filterData);

        $this->assertEquals($expectedCountData, $this->removeEmptyParameters($countData));
    }

    /**
     * @return array[]
     */
    public function searchTestCasesProvider(): array
    {
        return [
            'no-filter' => $this->searchNoFilterTestCase(),
            'one-flag' => $this->searchOneFlagTestCase(),
            'one-brand' => $this->searchOneBrandTestCase(),
            'price' => $this->searchPriceTestCase(),
            'stock' => $this->searchStockTestCase(),
            'price-stock-flag-brands' => $this->searchPriceStockFlagBrandsTestCase(),
        ];
    }

    /**
     * @return array
     */
    private function categoryNoFilterTestCase(): array
    {
        $category = $this->getReference(CategoryDataFixture::CATEGORY_PRINTERS);
        $filterData = new ProductFilterData();
        $countData = new ProductFilterCountData();

        $countData->countInStock = 10;
        $countData->countByBrandId = [
            2 => 6,
            14 => 2,
            25 => 2,
        ];
        $countData->countByFlagId = [
            1 => 5,
            2 => 2,
        ];
        $countData->countByParameterIdAndValueId = [
            32 => [
                $this->getParameterValueIdForFirstDomain('Yes') => 10,
            ],
            11 => [
                $this->getParameterValueIdForFirstDomain('449x304x152 mm') => 8,
                $this->getParameterValueIdForFirstDomain('426x306x145 mm') => 2,
            ],
            30 => [
                $this->getParameterValueIdForFirstDomain('Yes') => 5,
                $this->getParameterValueIdForFirstDomain('No') => 5,
            ],
            29 => [
                $this->getParameterValueIdForFirstDomain('A3') => 7,
                $this->getParameterValueIdForFirstDomain('A4') => 3,
            ],
            31 => [
                $this->getParameterValueIdForFirstDomain('4800x1200') => 3,
                $this->getParameterValueIdForFirstDomain('2400x600') => 7,
            ],
            28 => [
                $this->getParameterValueIdForFirstDomain('inkjet') => 10,
            ],
            4 => [
                $this->getParameterValueIdForFirstDomain('Yes') => 10,
            ],
            10 => [
                $this->getParameterValueIdForFirstDomain('5.4 kg') => 1,
                $this->getParameterValueIdForFirstDomain('3.5 kg') => 9,
            ],
            33 => [
                $this->getParameterValueIdForFirstDomain('Yes') => 8,
                $this->getParameterValueIdForFirstDomain('No') => 2,
            ],
        ];

        return [
            $category,
            $filterData,
            $countData,
        ];
    }

    /**
     * @return array
     */
    private function categoryOneFlagTestCase(): array
    {
        $category = $this->getReference(CategoryDataFixture::CATEGORY_PRINTERS);
        $filterData = new ProductFilterData();
        $filterData->flags[] = $this->getReference(FlagDataFixture::FLAG_TOP_PRODUCT);

        $countData = new ProductFilterCountData();

        $countData->countInStock = 2;
        $countData->countByBrandId = [
            2 => 2,
        ];
        $countData->countByFlagId = [
            1 => 3,
        ];
        $countData->countByParameterIdAndValueId = [
            32 => [
                $this->getParameterValueIdForFirstDomain('Yes') => 2,
            ],
            11 => [
                $this->getParameterValueIdForFirstDomain('449x304x152 mm') => 2,
            ],
            30 => [
                $this->getParameterValueIdForFirstDomain('Yes') => 1,
                $this->getParameterValueIdForFirstDomain('No') => 1,
            ],
            29 => [
                $this->getParameterValueIdForFirstDomain('A3') => 1,
                $this->getParameterValueIdForFirstDomain('A4') => 1,
            ],
            31 => [
                $this->getParameterValueIdForFirstDomain('4800x1200') => 1,
                $this->getParameterValueIdForFirstDomain('2400x600') => 1,
            ],
            28 => [
                $this->getParameterValueIdForFirstDomain('inkjet') => 2,
            ],
            4 => [
                $this->getParameterValueIdForFirstDomain('Yes') => 2,
            ],
            10 => [
                $this->getParameterValueIdForFirstDomain('5.4 kg') => 1,
                $this->getParameterValueIdForFirstDomain('3.5 kg') => 1,
            ],
            33 => [
                $this->getParameterValueIdForFirstDomain('Yes') => 2,
            ],
        ];

        return [
            $category,
            $filterData,
            $countData,
        ];
    }

    /**
     * @return array
     */
    private function categoryTwoBrandsTestCase(): array
    {
        $category = $this->getReference(CategoryDataFixture::CATEGORY_PRINTERS);
        $filterData = new ProductFilterData();
        $filterData->brands[] = $this->getReference(BrandDataFixture::BRAND_CANON);
        $countData = new ProductFilterCountData();
        $countData->countInStock = 6;
        $countData->countByFlagId = [
            1 => 3,
            2 => 2,
        ];
        $countData->countByBrandId = [
            14 => 2,
            25 => 2,
        ];
        $countData->countByParameterIdAndValueId = [
            32 => [
                $this->getParameterValueIdForFirstDomain('Yes') => 6,
            ],
            11 => [
                $this->getParameterValueIdForFirstDomain('449x304x152 mm') => 6,
            ],
            30 => [
                $this->getParameterValueIdForFirstDomain('Yes') => 3,
                $this->getParameterValueIdForFirstDomain('No') => 3,
            ],
            29 => [
                $this->getParameterValueIdForFirstDomain('A3') => 3,
                $this->getParameterValueIdForFirstDomain('A4') => 3,
            ],
            31 => [
                $this->getParameterValueIdForFirstDomain('4800x1200') => 2,
                $this->getParameterValueIdForFirstDomain('2400x600') => 4,
            ],
            28 => [
                $this->getParameterValueIdForFirstDomain('inkjet') => 6,
            ],
            4 => [
                $this->getParameterValueIdForFirstDomain('Yes') => 6,
            ],
            10 => [
                $this->getParameterValueIdForFirstDomain('5.4 kg') => 1,
                $this->getParameterValueIdForFirstDomain('3.5 kg') => 5,
            ],
            33 => [
                $this->getParameterValueIdForFirstDomain('Yes') => 6,
            ],
        ];

        return [
            $category,
            $filterData,
            $countData,
        ];
    }

    /**
     * @return array
     */
    private function categoryAllFlagsAllBrandsTestCase(): array
    {
        $category = $this->getReference(CategoryDataFixture::CATEGORY_PRINTERS);
        $filterData = new ProductFilterData();
        $filterData->brands[] = $this->getReference(BrandDataFixture::BRAND_CANON);
        $filterData->brands[] = $this->getReference(BrandDataFixture::BRAND_HP);
        $filterData->flags[] = $this->getReference(FlagDataFixture::FLAG_TOP_PRODUCT);
        $filterData->flags[] = $this->getReference(FlagDataFixture::FLAG_NEW_PRODUCT);

        $countData = new ProductFilterCountData();
        $countData->countInStock = 4;
        $countData->countByBrandId = [
            25 => 1,
        ];
        $countData->countByParameterIdAndValueId = [
            32 => [
                $this->getParameterValueIdForFirstDomain('Yes') => 4,
            ],
            11 => [
                $this->getParameterValueIdForFirstDomain('449x304x152 mm') => 4,
            ],
            30 => [
                $this->getParameterValueIdForFirstDomain('Yes') => 2,
                $this->getParameterValueIdForFirstDomain('No') => 2,
            ],
            29 => [
                $this->getParameterValueIdForFirstDomain('A3') => 3,
                $this->getParameterValueIdForFirstDomain('A4') => 1,
            ],
            31 => [
                $this->getParameterValueIdForFirstDomain('4800x1200') => 2,
                $this->getParameterValueIdForFirstDomain('2400x600') => 2,
            ],
            28 => [
                $this->getParameterValueIdForFirstDomain('inkjet') => 4,
            ],
            4 => [
                $this->getParameterValueIdForFirstDomain('Yes') => 4,
            ],
            10 => [
                $this->getParameterValueIdForFirstDomain('5.4 kg') => 1,
                $this->getParameterValueIdForFirstDomain('3.5 kg') => 3,
            ],
            33 => [
                $this->getParameterValueIdForFirstDomain('Yes') => 4,
            ],
        ];

        return [
            $category,
            $filterData,
            $countData,
        ];
    }

    /**
     * @return array
     */
    private function categoryPriceTestCase(): array
    {
        /** @var \Shopsys\FrameworkBundle\Model\Pricing\PriceConverter $priceConverter */
        $priceConverter = $this->getContainer()->get(PriceConverter::class);
        $category = $this->getReference(CategoryDataFixture::CATEGORY_PRINTERS);
        $filterData = new ProductFilterData();
        $filterData->minimalPrice = $priceConverter->convertPriceWithVatToPriceInDomainDefaultCurrency(Money::create(1000), Domain::FIRST_DOMAIN_ID);
        $filterData->maximalPrice = $priceConverter->convertPriceWithVatToPriceInDomainDefaultCurrency(Money::create(80000), Domain::FIRST_DOMAIN_ID);

        $countData = new ProductFilterCountData();
        $countData->countInStock = 8;
        $countData->countByBrandId = [
            2 => 4,
            14 => 2,
            25 => 2,
        ];
        $countData->countByFlagId = [
            1 => 4,
            2 => 2,
        ];
        $countData->countByParameterIdAndValueId = [
            32 => [
                $this->getParameterValueIdForFirstDomain('Yes') => 8,
            ],
            11 => [
                $this->getParameterValueIdForFirstDomain('449x304x152 mm') => 6,
                $this->getParameterValueIdForFirstDomain('426x306x145 mm') => 2,
            ],
            30 => [
                $this->getParameterValueIdForFirstDomain('Yes') => 4,
                $this->getParameterValueIdForFirstDomain('No') => 4,
            ],
            29 => [
                $this->getParameterValueIdForFirstDomain('A3') => 6,
                $this->getParameterValueIdForFirstDomain('A4') => 2,
            ],
            31 => [
                $this->getParameterValueIdForFirstDomain('4800x1200') => 2,
                $this->getParameterValueIdForFirstDomain('2400x600') => 6,
            ],
            28 => [
                $this->getParameterValueIdForFirstDomain('inkjet') => 8,
            ],
            4 => [
                $this->getParameterValueIdForFirstDomain('Yes') => 8,
            ],
            10 => [
                $this->getParameterValueIdForFirstDomain('5.4 kg') => 1,
                $this->getParameterValueIdForFirstDomain('3.5 kg') => 7,
            ],
            33 => [
                $this->getParameterValueIdForFirstDomain('Yes') => 6,
                $this->getParameterValueIdForFirstDomain('No') => 2,
            ],
        ];

        return [
            $category,
            $filterData,
            $countData,
        ];
    }

    /**
     * @return array
     */
    private function categoryStockTestCase(): array
    {
        $category = $this->getReference(CategoryDataFixture::CATEGORY_PHONES);
        $filterData = new ProductFilterData();
        $filterData->inStock = true;

        $countData = new ProductFilterCountData();
        $countData->countInStock = 2;
        $countData->countByBrandId = [
            3 => 1,
            20 => 1,
        ];
        $countData->countByFlagId = [
            1 => 2,
        ];
        $countData->countByParameterIdAndValueId = [
            17 => [
                $this->getParameterValueIdForFirstDomain('Yes') => 1,
            ],
            11 => [
                $this->getParameterValueIdForFirstDomain('123.8x58.6 mm') => 1,
            ],
            19 => [
                $this->getParameterValueIdForFirstDomain('No') => 1,
            ],
            12 => [
                $this->getParameterValueIdForFirstDomain('No') => 1,
            ],
            18 => [
                $this->getParameterValueIdForFirstDomain('No') => 1,
            ],
            14 => [
                $this->getParameterValueIdForFirstDomain('16mil.') => 1,
            ],
            16 => [
                $this->getParameterValueIdForFirstDomain('2') => 1,
            ],
            15 => [
                $this->getParameterValueIdForFirstDomain('1.7GHz') => 1,
            ],
            13 => [
                $this->getParameterValueIdForFirstDomain('1024 MB') => 1,
            ],
            10 => [
                $this->getParameterValueIdForFirstDomain('112 g') => 1,
            ],
        ];

        return [
            $category,
            $filterData,
            $countData,
        ];
    }

    /**
     * @return array
     */
    private function categoryFlagBrandAndParametersTestCase(): array
    {
        /** @var \Shopsys\FrameworkBundle\Component\Domain\Domain $domain */
        $domain = $this->getContainer()->get(Domain::class);
        $firstDomainLocale = $domain->getDomainConfigById(Domain::FIRST_DOMAIN_ID)->getLocale();
        $category = $this->getReference(CategoryDataFixture::CATEGORY_PRINTERS);
        $filterData = new ProductFilterData();
        $filterData->brands[] = $this->getReference(BrandDataFixture::BRAND_CANON);
        $filterData->flags[] = $this->getReference(FlagDataFixture::FLAG_NEW_PRODUCT);
        $filterData->parameters[] = $this->createParameterFilterData(
            [$firstDomainLocale => t('Dimensions', [], 'dataFixtures', $firstDomainLocale)],
            [[$firstDomainLocale => t('449x304x152 mm', [], 'dataFixtures', $firstDomainLocale)]]
        );
        $filterData->parameters[] = $this->createParameterFilterData(
            [$firstDomainLocale => t('Print resolution', [], 'dataFixtures', $firstDomainLocale)],
            [[$firstDomainLocale => t('2400x600', [], 'dataFixtures', $firstDomainLocale)], [$firstDomainLocale => t('4800x1200', [], 'dataFixtures', $firstDomainLocale)]]
        );
        $filterData->parameters[] = $this->createParameterFilterData(
            [$firstDomainLocale => t('Weight', [], 'dataFixtures', $firstDomainLocale)],
            [[$firstDomainLocale => t('3.5 kg', [], 'dataFixtures', $firstDomainLocale)]]
        );

        $countData = new ProductFilterCountData();
        $countData->countInStock = 2;
        $countData->countByBrandId = [
            14 => 1,
        ];
        $countData->countByFlagId = [];
        $countData->countByParameterIdAndValueId = [
            32 => [
                $this->getParameterValueIdForFirstDomain('Yes') => 2,
            ],
            11 => [
                $this->getParameterValueIdForFirstDomain('449x304x152 mm') => 2,
            ],
            30 => [
                $this->getParameterValueIdForFirstDomain('Yes') => 1,
                $this->getParameterValueIdForFirstDomain('No') => 1,
            ],
            29 => [
                $this->getParameterValueIdForFirstDomain('A3') => 1,
                $this->getParameterValueIdForFirstDomain('A4') => 1,
            ],
            31 => [
                $this->getParameterValueIdForFirstDomain('4800x1200') => 1,
                $this->getParameterValueIdForFirstDomain('2400x600') => 1,
            ],
            28 => [
                $this->getParameterValueIdForFirstDomain('inkjet') => 2,
            ],
            4 => [
                $this->getParameterValueIdForFirstDomain('Yes') => 2,
            ],
            10 => [
                $this->getParameterValueIdForFirstDomain('5.4 kg') => 1,
                $this->getParameterValueIdForFirstDomain('3.5 kg') => 2,
            ],
            33 => [
                $this->getParameterValueIdForFirstDomain('Yes') => 2,
            ],
        ];

        return [
            $category,
            $filterData,
            $countData,
        ];
    }

    /**
     * @return array
     */
    private function categoryParametersTestCase(): array
    {
        /** @var \Shopsys\FrameworkBundle\Component\Domain\Domain $domain */
        $domain = $this->getContainer()->get(Domain::class);
        $firstDomainLocale = $domain->getDomainConfigById(Domain::FIRST_DOMAIN_ID)->getLocale();
        $category = $this->getReference(CategoryDataFixture::CATEGORY_PRINTERS);
        $filterData = new ProductFilterData();
        $filterData->parameters[] = $this->createParameterFilterData(
            [$firstDomainLocale => t('Dimensions', [], 'dataFixtures', $firstDomainLocale)],
            [[$firstDomainLocale => t('449x304x152 mm', [], 'dataFixtures', $firstDomainLocale)]]
        );
        $filterData->parameters[] = $this->createParameterFilterData(
            [$firstDomainLocale => t('Print resolution', [], 'dataFixtures', $firstDomainLocale)],
            [[$firstDomainLocale => t('2400x600', [], 'dataFixtures', $firstDomainLocale)], [$firstDomainLocale => t('4800x1200', [], 'dataFixtures', $firstDomainLocale)]]
        );
        $filterData->parameters[] = $this->createParameterFilterData(
            [$firstDomainLocale => t('Weight', [], 'dataFixtures', $firstDomainLocale)],
            [[$firstDomainLocale => t('3.5 kg', [], 'dataFixtures', $firstDomainLocale)]]
        );

        $countData = new ProductFilterCountData();
        $countData->countInStock = 7;
        $countData->countByBrandId = [
            14 => 2,
            2 => 5,
        ];
        $countData->countByFlagId = [
            1 => 3,
            2 => 1,
        ];
        $countData->countByParameterIdAndValueId = [
            32 => [
                $this->getParameterValueIdForFirstDomain('Yes') => 7,
            ],
            11 => [
                $this->getParameterValueIdForFirstDomain('449x304x152 mm') => 7,
                $this->getParameterValueIdForFirstDomain('426x306x145 mm') => 2,
            ],
            30 => [
                $this->getParameterValueIdForFirstDomain('Yes') => 3,
                $this->getParameterValueIdForFirstDomain('No') => 4,
            ],
            29 => [
                $this->getParameterValueIdForFirstDomain('A3') => 4,
                $this->getParameterValueIdForFirstDomain('A4') => 3,
            ],
            31 => [
                $this->getParameterValueIdForFirstDomain('4800x1200') => 1,
                $this->getParameterValueIdForFirstDomain('2400x600') => 6,
            ],
            28 => [
                $this->getParameterValueIdForFirstDomain('inkjet') => 7,
            ],
            4 => [
                $this->getParameterValueIdForFirstDomain('Yes') => 7,
            ],
            10 => [
                $this->getParameterValueIdForFirstDomain('5.4 kg') => 1,
                $this->getParameterValueIdForFirstDomain('3.5 kg') => 7,
            ],
            33 => [
                $this->getParameterValueIdForFirstDomain('Yes') => 7,
            ],
        ];

        return [
            $category,
            $filterData,
            $countData,
        ];
    }

    /**
     * @param array $namesByLocale
     * @param array $valuesTextsByLocales
     * @return \Shopsys\FrameworkBundle\Model\Product\Filter\ParameterFilterData
     */
    private function createParameterFilterData(array $namesByLocale, array $valuesTextsByLocales)
    {
        /** @var \App\Model\Product\Parameter\ParameterRepository $parameterRepository */
        $parameterRepository = $this->getContainer()->get(ParameterRepository::class);

        $parameter = $parameterRepository->findParameterByNames($namesByLocale);
        $parameterValues = $this->getParameterValuesByLocalesAndTexts($valuesTextsByLocales);

        $parameterFilterData = new ParameterFilterData();
        $parameterFilterData->parameter = $parameter;
        $parameterFilterData->values = $parameterValues;

        return $parameterFilterData;
    }

    /**
     * @param array[] $valuesTextsByLocales
     * @return \App\Model\Product\Parameter\ParameterValue[]
     */
    private function getParameterValuesByLocalesAndTexts(array $valuesTextsByLocales)
    {
        /** @var \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em */
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $parameterValues = [];

        foreach ($valuesTextsByLocales as $valueTextsByLocales) {
            foreach ($valueTextsByLocales as $locale => $text) {
                /** @var \App\Model\Product\Parameter\ParameterValue $parameterValue */
                $parameterValue = $em->getRepository(ParameterValue::class)->findOneBy([
                    'text' => $text,
                    'locale' => $locale,
                ]);
                $parameterValues[] = $parameterValue;
            }
        }

        return $parameterValues;
    }

    /**
     * @return array
     */
    private function searchNoFilterTestCase(): array
    {
        $filterData = new ProductFilterData();
        $countData = new ProductFilterCountData();
        $countData->countInStock = 38;
        $countData->countByBrandId = [
            8 => 1,
            11 => 1,
            19 => 2,
            10 => 1,
            2 => 10,
            4 => 1,
            16 => 1,
            15 => 1,
            6 => 1,
            14 => 2,
            12 => 2,
            3 => 2,
            9 => 1,
        ];
        $countData->countByFlagId = [
            1 => 15,
            2 => 5,
            3 => 3,
        ];

        return [
            'print',
            $filterData,
            $countData,
        ];
    }

    /**
     * @return array
     */
    private function searchOneFlagTestCase(): array
    {
        $filterData = new ProductFilterData();
        $filterData->flags[] = $this->getReference(FlagDataFixture::FLAG_NEW_PRODUCT);
        $countData = new ProductFilterCountData();
        $countData->countInStock = 11;
        $countData->countByBrandId = [
            2 => 3,
            3 => 1,
            10 => 1,
            11 => 1,
            12 => 1,
            14 => 1,
            15 => 1,
            16 => 1,
            19 => 2,
        ];
        $countData->countByFlagId = [
            2 => 2,
            3 => 2,
        ];

        return [
            'print',
            $filterData,
            $countData,
        ];
    }

    /**
     * @return array
     */
    private function searchOneBrandTestCase(): array
    {
        $filterData = new ProductFilterData();
        $filterData->brands[] = $this->getReference(BrandDataFixture::BRAND_CANON);
        $countData = new ProductFilterCountData();

        $countData->countInStock = 10;
        $countData->countByBrandId = [
            3 => 2,
            4 => 1,
            6 => 1,
            8 => 1,
            10 => 1,
            11 => 1,
            12 => 2,
            14 => 2,
            15 => 1,
            16 => 1,
            19 => 2,
            9 => 1,
        ];
        $countData->countByFlagId = [
            1 => 3,
            2 => 2,
        ];

        return [
            'print',
            $filterData,
            $countData,
        ];
    }

    /**
     * @return array
     */
    private function searchPriceTestCase(): array
    {
        /** @var \Shopsys\FrameworkBundle\Model\Pricing\PriceConverter $priceConverter */
        $priceConverter = $this->getContainer()->get(PriceConverter::class);

        $filterData = new ProductFilterData();
        $filterData->minimalPrice = $priceConverter->convertPriceWithVatToPriceInDomainDefaultCurrency(Money::create(5000), Domain::FIRST_DOMAIN_ID);
        $filterData->maximalPrice = $priceConverter->convertPriceWithVatToPriceInDomainDefaultCurrency(Money::create(50000), Domain::FIRST_DOMAIN_ID);
        $countData = new ProductFilterCountData();
        $countData->countInStock = 9;
        $countData->countByBrandId = [
            2 => 4,
            3 => 1,
            4 => 1,
            11 => 1,
            15 => 1,
        ];
        $countData->countByFlagId = [
            1 => 2,
            2 => 2,
        ];

        return [
            'print',
            $filterData,
            $countData,
        ];
    }

    /**
     * @return array
     */
    private function searchStockTestCase(): array
    {
        $filterData = new ProductFilterData();
        $filterData->inStock = true;
        $countData = new ProductFilterCountData();
        $countData->countInStock = 38;
        $countData->countByBrandId = [
            2 => 10,
            3 => 2,
            4 => 1,
            6 => 1,
            8 => 1,
            10 => 1,
            11 => 1,
            12 => 2,
            14 => 2,
            16 => 1,
        ];
        $countData->countByFlagId = [
            1 => 11,
            2 => 4,
            3 => 2,
        ];

        return [
            'print',
            $filterData,
            $countData,
        ];
    }

    /**
     * @return array
     */
    private function searchPriceStockFlagBrandsTestCase(): array
    {
        /** @var \Shopsys\FrameworkBundle\Model\Pricing\PriceConverter $priceConverter */
        $priceConverter = $this->getContainer()->get(PriceConverter::class);

        $filterData = new ProductFilterData();
        $filterData->inStock = true;
        $filterData->flags[] = $this->getReference(FlagDataFixture::FLAG_NEW_PRODUCT);
        $filterData->brands[] = $this->getReference(BrandDataFixture::BRAND_DELONGHI);
        $filterData->brands[] = $this->getReference(BrandDataFixture::BRAND_DEFENDER);
        $filterData->brands[] = $this->getReference(BrandDataFixture::BRAND_GENIUS);
        $filterData->brands[] = $this->getReference(BrandDataFixture::BRAND_HP);
        $filterData->maximalPrice = $priceConverter->convertPriceWithVatToPriceInDomainDefaultCurrency(Money::create(20000), Domain::FIRST_DOMAIN_ID);

        $countData = new ProductFilterCountData();
        $countData->countInStock = 3;
        $countData->countByBrandId = [
            2 => 3,
            3 => 1,
        ];

        return [
            'print',
            $filterData,
            $countData,
        ];
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterCountData $countData
     * @return \Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterCountData
     */
    private function removeEmptyParameters(ProductFilterCountData $countData): ProductFilterCountData
    {
        $result = clone $countData;
        foreach ($countData->countByParameterIdAndValueId as $parameterId => $values) {
            if (empty($values)) {
                unset($result->countByParameterIdAndValueId[$parameterId]);
            }
        }

        return $result;
    }
}