<?php

declare(strict_types=1);

namespace Tests\App\Functional\Model\Product;

use App\DataFixtures\Demo\AvailabilityDataFixture;
use App\Model\Pricing\Vat\VatFacade;
use App\Model\Product\Product;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Product\ProductData;
use Shopsys\FrameworkBundle\Model\Product\ProductDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Product\ProductFactoryInterface;
use Tests\App\Test\TransactionFunctionalTestCase;

class ProductDomainTest extends TransactionFunctionalTestCase
{
    public const FIRST_DOMAIN_ID = 1;
    public const SECOND_DOMAIN_ID = 2;
    public const DEMONSTRATIVE_DESCRIPTION = 'Demonstrative description';
    public const DEMONSTRATIVE_SEO_TITLE = 'Demonstrative seo title';
    public const DEMONSTRATIVE_SEO_META_DESCRIPTION = 'Demonstrative seo description';
    public const DEMONSTRATIVE_SEO_H1 = 'Demonstrative seo H1';
    public const DEMONSTRATIVE_SHORT_DESCRIPTION = 'Demonstrative short description';

    /**
     * @var \App\Model\Product\ProductDataFactory
     */
    private $productDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\ProductFactory
     */
    private $productFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator
     */
    private $em;

    public function setUp()
    {
        parent::setUp();
        $this->productDataFactory = $this->getContainer()->get(ProductDataFactoryInterface::class);
        $this->productFactory = $this->getContainer()->get(ProductFactoryInterface::class);
        $this->em = $this->getEntityManager();
    }

    /**
     * @group multidomain
     */
    public function testCreateProductDomainWithData()
    {
        $productData = $this->productDataFactory->create();

        $productData->seoTitles[self::FIRST_DOMAIN_ID] = self::DEMONSTRATIVE_SEO_TITLE;
        $productData->seoMetaDescriptions[self::SECOND_DOMAIN_ID] = self::DEMONSTRATIVE_SEO_META_DESCRIPTION;
        $productData->seoH1s[self::FIRST_DOMAIN_ID] = self::DEMONSTRATIVE_SEO_H1;
        $productData->descriptions[self::SECOND_DOMAIN_ID] = self::DEMONSTRATIVE_DESCRIPTION;
        $productData->shortDescriptions[self::FIRST_DOMAIN_ID] = self::DEMONSTRATIVE_SHORT_DESCRIPTION;
        $productData->availability = $this->getReference(AvailabilityDataFixture::AVAILABILITY_IN_STOCK);
        $productData->outOfStockAvailability = $this->getReference(AvailabilityDataFixture::AVAILABILITY_OUT_OF_STOCK);
        $this->setVats($productData);

        $product = $this->productFactory->create($productData);

        $refreshedProduct = $this->getRefreshedProductFromDatabase($product);

        $this->assertSame(self::DEMONSTRATIVE_SEO_TITLE, $refreshedProduct->getSeoTitle(self::FIRST_DOMAIN_ID));
        $this->assertNull($refreshedProduct->getSeoTitle(self::SECOND_DOMAIN_ID));
        $this->assertSame(self::DEMONSTRATIVE_SEO_META_DESCRIPTION, $refreshedProduct->getSeoMetaDescription(self::SECOND_DOMAIN_ID));
        $this->assertNull($refreshedProduct->getSeoMetaDescription(self::FIRST_DOMAIN_ID));
        $this->assertSame(self::DEMONSTRATIVE_SEO_H1, $refreshedProduct->getSeoH1(self::FIRST_DOMAIN_ID));
        $this->assertNull($refreshedProduct->getSeoH1(self::SECOND_DOMAIN_ID));
        $this->assertSame(self::DEMONSTRATIVE_DESCRIPTION, $refreshedProduct->getDescription(self::SECOND_DOMAIN_ID));
        $this->assertNull($refreshedProduct->getDescription(self::FIRST_DOMAIN_ID));
        $this->assertSame(self::DEMONSTRATIVE_SHORT_DESCRIPTION, $refreshedProduct->getShortDescription(self::FIRST_DOMAIN_ID));
        $this->assertNull($refreshedProduct->getShortDescription(self::SECOND_DOMAIN_ID));
    }

    /**
     * @group singledomain
     */
    public function testCreateProductDomainWithDataForSingleDomain()
    {
        $productData = $this->productDataFactory->create();

        $productData->seoTitles[self::FIRST_DOMAIN_ID] = self::DEMONSTRATIVE_SEO_TITLE;
        $productData->seoMetaDescriptions[self::FIRST_DOMAIN_ID] = self::DEMONSTRATIVE_SEO_META_DESCRIPTION;
        $productData->seoH1s[self::FIRST_DOMAIN_ID] = self::DEMONSTRATIVE_SEO_H1;
        $productData->descriptions[self::FIRST_DOMAIN_ID] = self::DEMONSTRATIVE_DESCRIPTION;
        $productData->shortDescriptions[self::FIRST_DOMAIN_ID] = self::DEMONSTRATIVE_SHORT_DESCRIPTION;
        $productData->availability = $this->getReference(AvailabilityDataFixture::AVAILABILITY_IN_STOCK);
        $productData->outOfStockAvailability = $this->getReference(AvailabilityDataFixture::AVAILABILITY_OUT_OF_STOCK);
        $this->setVats($productData);

        $product = $this->productFactory->create($productData);

        $refreshedProduct = $this->getRefreshedProductFromDatabase($product);

        $this->assertSame(self::DEMONSTRATIVE_SEO_TITLE, $refreshedProduct->getSeoTitle(self::FIRST_DOMAIN_ID));
        $this->assertSame(self::DEMONSTRATIVE_SEO_META_DESCRIPTION, $refreshedProduct->getSeoMetaDescription(self::FIRST_DOMAIN_ID));
        $this->assertSame(self::DEMONSTRATIVE_SEO_H1, $refreshedProduct->getSeoH1(self::FIRST_DOMAIN_ID));
        $this->assertSame(self::DEMONSTRATIVE_DESCRIPTION, $refreshedProduct->getDescription(self::FIRST_DOMAIN_ID));
        $this->assertSame(self::DEMONSTRATIVE_SHORT_DESCRIPTION, $refreshedProduct->getShortDescription(self::FIRST_DOMAIN_ID));
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return \App\Model\Product\Product
     */
    private function getRefreshedProductFromDatabase(Product $product)
    {
        $this->em->persist($product);
        $this->em->flush();

        $productId = $product->getId();

        $this->em->clear();

        return $this->em->getRepository(Product::class)->find($productId);
    }

    /**
     * @param \App\Model\Product\ProductData $productData
     */
    private function setVats(ProductData $productData): void
    {
        /** @var \Shopsys\FrameworkBundle\Component\Domain\Domain $domain */
        $domain = $this->getContainer()->get(Domain::class);
        /** @var \App\Model\Pricing\Vat\VatFacade $vatFacade */
        $vatFacade = $this->getContainer()->get(VatFacade::class);
        $productVatsIndexedByDomainId = [];
        foreach ($domain->getAllIds() as $domainId) {
            $productVatsIndexedByDomainId[$domainId] = $vatFacade->getDefaultVatForDomain($domainId);
        }
        $productData->vatsIndexedByDomainId = $productVatsIndexedByDomainId;
    }
}
