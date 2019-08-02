<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\DataFixtures\Demo;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Component\DataFixture\PersistentReferenceFacade;
use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValueDataFactory;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValueDataFactory;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\ProductData;
use Shopsys\FrameworkBundle\Model\Product\ProductFacade;
use Shopsys\FrameworkBundle\Model\Product\ProductVariantFacade;
use Shopsys\ShopBundle\DataFixtures\ProductDataFixtureReferenceInjector;
use Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade;
use Shopsys\ShopBundle\Model\Product\ProductDataFactory;

class ProductDataFixture extends AbstractReferenceFixture implements DependentFixtureInterface
{
    public const PRODUCT_PREFIX = 'product_';

    /** @var \Shopsys\ShopBundle\DataFixtures\Demo\ProductDataFixtureLoader */
    protected $productDataFixtureLoader;

    /** @var \Shopsys\ShopBundle\DataFixtures\ProductDataFixtureReferenceInjector */
    protected $referenceInjector;

    /** @var \Shopsys\FrameworkBundle\Component\DataFixture\PersistentReferenceFacade */
    protected $persistentReferenceFacade;

    /** @var \Shopsys\ShopBundle\DataFixtures\Demo\ProductDataFixtureCsvReader */
    protected $productDataFixtureCsvReader;

    /** @var \Shopsys\FrameworkBundle\Model\Product\ProductFacade */
    protected $productFacade;

    /** @var \Shopsys\FrameworkBundle\Model\Product\ProductVariantFacade */
    protected $productVariantFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductDataFactory
     */
    private $productDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValueDataFactory
     */
    private $parameterValueDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValueDataFactory
     */
    private $productParameterValueDataFactory;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade
     */
    private $parameterFacade;

    /**
     * @param \Shopsys\ShopBundle\DataFixtures\Demo\ProductDataFixtureLoader $productDataFixtureLoader
     * @param \Shopsys\ShopBundle\DataFixtures\ProductDataFixtureReferenceInjector $referenceInjector
     * @param \Shopsys\FrameworkBundle\Component\DataFixture\PersistentReferenceFacade $persistentReferenceFacade
     * @param \Shopsys\ShopBundle\DataFixtures\Demo\ProductDataFixtureCsvReader $productDataFixtureCsvReader
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductFacade $productFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductVariantFacade $productVariantFacade
     * @param \Shopsys\ShopBundle\Model\Product\ProductDataFactory $productDataFactory
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValueDataFactory $parameterValueDataFactory
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValueDataFactory $productParameterValueDataFactory
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade $parameterFacade
     */
    public function __construct(
        ProductDataFixtureLoader $productDataFixtureLoader,
        ProductDataFixtureReferenceInjector $referenceInjector,
        PersistentReferenceFacade $persistentReferenceFacade,
        ProductDataFixtureCsvReader $productDataFixtureCsvReader,
        ProductFacade $productFacade,
        ProductVariantFacade $productVariantFacade,
        ProductDataFactory $productDataFactory,
        ParameterValueDataFactory $parameterValueDataFactory,
        ProductParameterValueDataFactory $productParameterValueDataFactory,
        ParameterFacade $parameterFacade
    ) {
        $this->productDataFixtureLoader = $productDataFixtureLoader;
        $this->referenceInjector = $referenceInjector;
        $this->persistentReferenceFacade = $persistentReferenceFacade;
        $this->productDataFixtureCsvReader = $productDataFixtureCsvReader;
        $this->productFacade = $productFacade;
        $this->productVariantFacade = $productVariantFacade;
        $this->productDataFactory = $productDataFactory;
        $this->parameterValueDataFactory = $parameterValueDataFactory;
        $this->productParameterValueDataFactory = $productParameterValueDataFactory;
        $this->parameterFacade = $parameterFacade;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $this->referenceInjector->loadReferences($this->productDataFixtureLoader, $this->persistentReferenceFacade);

        $csvRows = $this->productDataFixtureCsvReader->getProductDataFixtureCsvRows();
        $productNo = 1;
        $productsByCatnum = [];
        foreach ($csvRows as $row) {
            $productData = $this->productDataFixtureLoader->createProductDataFromRow($row);
            $this->addFakeStoreStocks($productData);
            $product = $this->createProduct(self::PRODUCT_PREFIX . $productNo, $productData);

            if ($product->getCatnum() !== null) {
                $productsByCatnum[$product->getCatnum()] = $product;
            }
            $productNo++;
        }

        $this->createVariants($productsByCatnum, $productNo);
    }

    /**
     * @param string $referenceName
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductData $productData
     * @return \Shopsys\FrameworkBundle\Model\Product\Product
     */
    protected function createProduct($referenceName, ProductData $productData)
    {
        $product = $this->productFacade->create($productData);

        $this->addReference($referenceName, $product);

        return $product;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product[] $productsByCatnum
     * @param int $productNo
     */
    protected function createVariants(array $productsByCatnum, $productNo)
    {
        $csvRows = $this->productDataFixtureCsvReader->getProductDataFixtureCsvRows();
        $variantCatnumsByMainVariantCatnum = $this->productDataFixtureLoader->getVariantCatnumsIndexedByMainVariantCatnum($csvRows);

        $parameter = $this->parameterFacade->findOrCreateParameterByNames([
            'cs' => 'Velikost',
            'sk' => 'Velikosť',
            'de' => 'Size',
        ]);

        $distinguishingParameterForVariants = $this->parameterFacade->findOrCreateParameterByNames([
            'cs' => 'Úhlopříčka',
        ]);

        foreach ($variantCatnumsByMainVariantCatnum as $mainVariantCatnum => $variantsCatnums) {
            /* @var $mainProduct \Shopsys\ShopBundle\Model\Product\Product */
            $mainProduct = $productsByCatnum[$mainVariantCatnum];
            $this->setParameterToMainVariant($parameter, $mainProduct);

            $variants = [];
            foreach ($variantsCatnums as $variantCatnum) {
                $variants[] = $productsByCatnum[$variantCatnum];
            }

            $mainProduct->setDistinguishingParameter($distinguishingParameterForVariants);
            $mainVariant = $this->productVariantFacade->createVariant($mainProduct, $variants);
            $this->addReference(self::PRODUCT_PREFIX . $productNo, $mainVariant);
            $productNo++;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return ProductDataFixtureReferenceInjector::getDependenciesForFirstDomain();
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\ProductData $productData
     */
    private function addFakeStoreStocks(ProductData $productData)
    {
        $fakeStoreData = [];

        for ($i = 1; $i <= 4; $i++) {
            $fakeStoreData[$i] = rand(0, 125);
        }

        $productData->stockQuantityByStoreId = $fakeStoreData;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter $parameter
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $mainVariant
     */
    private function setParameterToMainVariant(Parameter $parameter, Product $mainVariant)
    {
        $parameterValueNames = ['XL', 'L', 'M'];

        $productData = $this->productDataFactory->createFromProduct($mainVariant);

        $parameterValueData = $this->parameterValueDataFactory->create();
        $parameterValueData->text = $parameterValueNames[$mainVariant->getId() % 3];
        $parameterValueData->locale = 'cs';

        $productParameterValueData = $this->productParameterValueDataFactory->create();
        $productParameterValueData->parameter = $parameter;
        $productParameterValueData->parameterValueData = $parameterValueData;

        $productData->parameters[] = $productParameterValueData;
        $this->productFacade->edit($mainVariant->getId(), $productData);
    }
}
