<?php

declare(strict_types=1);

namespace App\DataFixtures\Demo;

use App\Model\Product\Flag\Flag;
use App\Model\Product\Flag\ProductFlagDataFactory;
use App\Model\Product\Flag\ProductFlagFacade;
use App\Model\Product\Product;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;

class ProductFlagDataFixture extends AbstractReferenceFixture implements DependentFixtureInterface
{
    /**
     * @var \App\Model\Product\Flag\ProductFlagDataFactory
     */
    private $productFlagDataFactory;

    /**
     * @var \App\Model\Product\Flag\ProductFlagFacade
     */
    private $productFlagFacade;

    /**
     * @param \App\Model\Product\Flag\ProductFlagDataFactory $productFlagDataFactory
     * @param \App\Model\Product\Flag\ProductFlagFacade $productFlagFacade
     */
    public function __construct(ProductFlagDataFactory $productFlagDataFactory, ProductFlagFacade $productFlagFacade)
    {
        $this->productFlagDataFactory = $productFlagDataFactory;
        $this->productFlagFacade = $productFlagFacade;
    }

    /**
     * @inheritDoc
     */
    public function getDependencies()
    {
        return [
            FlagDataFixture::class,
            ProductDataFixture::class,
        ];
    }

    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager)
    {
        $this->createProductFlag(1, FlagDataFixture::FLAG_TOP_PRODUCT);
        $this->createProductFlag(1, FlagDataFixture::FLAG_ACTION_PRODUCT);
        $this->createProductFlag(2, FlagDataFixture::FLAG_TOP_PRODUCT);
        $this->createProductFlag(5, FlagDataFixture::FLAG_NEW_PRODUCT);
        $this->createProductFlag(5, FlagDataFixture::FLAG_ACTION_PRODUCT);
        $this->createProductFlag(5, FlagDataFixture::FLAG_TOP_PRODUCT);
        $this->createProductFlag(6, FlagDataFixture::FLAG_NEW_PRODUCT);
        $this->createProductFlag(9, FlagDataFixture::FLAG_NEW_PRODUCT);
        $this->createProductFlag(9, FlagDataFixture::FLAG_TOP_PRODUCT);
        $this->createProductFlag(10, FlagDataFixture::FLAG_NEW_PRODUCT);
        $this->createProductFlag(11, FlagDataFixture::FLAG_SALE_PRODUCT);
        $this->createProductFlag(13, FlagDataFixture::FLAG_NEW_PRODUCT);
        $this->createProductFlag(14, FlagDataFixture::FLAG_NEW_PRODUCT);
        $this->createProductFlag(15, FlagDataFixture::FLAG_NEW_PRODUCT);
        $this->createProductFlag(16, FlagDataFixture::FLAG_ACTION_PRODUCT);
        $this->createProductFlag(17, FlagDataFixture::FLAG_NEW_PRODUCT);
        $this->createProductFlag(19, FlagDataFixture::FLAG_NEW_PRODUCT);
        $this->createProductFlag(21, FlagDataFixture::FLAG_NEW_PRODUCT);
        $this->createProductFlag(22, FlagDataFixture::FLAG_NEW_PRODUCT);
        $this->createProductFlag(22, FlagDataFixture::FLAG_TOP_PRODUCT);
        $this->createProductFlag(23, FlagDataFixture::FLAG_NEW_PRODUCT);
        $this->createProductFlag(25, FlagDataFixture::FLAG_NEW_PRODUCT);
        $this->createProductFlag(25, FlagDataFixture::FLAG_SALE_PRODUCT);
        $this->createProductFlag(26, FlagDataFixture::FLAG_NEW_PRODUCT);
        $this->createProductFlag(27, FlagDataFixture::FLAG_NEW_PRODUCT);
        $this->createProductFlag(28, FlagDataFixture::FLAG_NEW_PRODUCT);
        $this->createProductFlag(29, FlagDataFixture::FLAG_NEW_PRODUCT);
        $this->createProductFlag(30, FlagDataFixture::FLAG_NEW_PRODUCT);
        $this->createProductFlag(31, FlagDataFixture::FLAG_NEW_PRODUCT);
        $this->createProductFlag(33, FlagDataFixture::FLAG_NEW_PRODUCT);
        $this->createProductFlag(33, FlagDataFixture::FLAG_ACTION_PRODUCT);
        $this->createProductFlag(33, FlagDataFixture::FLAG_TOP_PRODUCT);
        $this->createProductFlag(35, FlagDataFixture::FLAG_NEW_PRODUCT);
        $this->createProductFlag(39, FlagDataFixture::FLAG_ACTION_PRODUCT);
        $this->createProductFlag(40, FlagDataFixture::FLAG_NEW_PRODUCT);
        $this->createProductFlag(40, FlagDataFixture::FLAG_ACTION_PRODUCT);
        $this->createProductFlag(42, FlagDataFixture::FLAG_NEW_PRODUCT);
        $this->createProductFlag(43, FlagDataFixture::FLAG_NEW_PRODUCT);
        $this->createProductFlag(44, FlagDataFixture::FLAG_NEW_PRODUCT);
        $this->createProductFlag(44, FlagDataFixture::FLAG_SALE_PRODUCT);
        $this->createProductFlag(45, FlagDataFixture::FLAG_ACTION_PRODUCT);
        $this->createProductFlag(45, FlagDataFixture::FLAG_TOP_PRODUCT);
        $this->createProductFlag(46, FlagDataFixture::FLAG_NEW_PRODUCT);
        $this->createProductFlag(47, FlagDataFixture::FLAG_NEW_PRODUCT);
        $this->createProductFlag(49, FlagDataFixture::FLAG_NEW_PRODUCT);
        $this->createProductFlag(50, FlagDataFixture::FLAG_NEW_PRODUCT);
        $this->createProductFlag(50, FlagDataFixture::FLAG_ACTION_PRODUCT);
        $this->createProductFlag(51, FlagDataFixture::FLAG_TOP_PRODUCT);
        $this->createProductFlag(52, FlagDataFixture::FLAG_NEW_PRODUCT);
        $this->createProductFlag(53, FlagDataFixture::FLAG_TOP_PRODUCT);
        $this->createProductFlag(54, FlagDataFixture::FLAG_TOP_PRODUCT);
        $this->createProductFlag(65, FlagDataFixture::FLAG_SALE_PRODUCT);
        $this->createProductFlag(69, FlagDataFixture::FLAG_TOP_PRODUCT);
        $this->createProductFlag(70, FlagDataFixture::FLAG_ACTION_PRODUCT);
        $this->createProductFlag(72, FlagDataFixture::FLAG_TOP_PRODUCT);
        $this->createProductFlag(74, FlagDataFixture::FLAG_TOP_PRODUCT);
        $this->createProductFlag(75, FlagDataFixture::FLAG_TOP_PRODUCT);
        $this->createProductFlag(82, FlagDataFixture::FLAG_TOP_PRODUCT);
        $this->createProductFlag(82, FlagDataFixture::FLAG_SALE_PRODUCT);
        $this->createProductFlag(144, FlagDataFixture::FLAG_NEW_PRODUCT);
        $this->createProductFlag(144, FlagDataFixture::FLAG_TOP_PRODUCT);
    }

    /**
     * @param int $productId
     * @param string $flagReference
     */
    private function createProductFlag(int $productId, string $flagReference): void
    {
        $productFlagData = $this->productFlagDataFactory->create($this->getProduct($productId), $this->getFlag($flagReference));
        $this->productFlagFacade->create($productFlagData);
    }

    /**
     * @param string $reference
     * @return \App\Model\Product\Flag\Flag
     */
    private function getFlag(string $reference): Flag
    {
        return $this->getReference($reference);
    }

    /**
     * @param int $id
     * @return \App\Model\Product\Product
     */
    private function getProduct(int $id): Product
    {
        return $this->getReference(ProductDataFixture::PRODUCT_PREFIX . (string)$id);
    }
}
