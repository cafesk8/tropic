<?php

declare(strict_types=1);

namespace App\DataFixtures\Demo;

use App\Model\Product\PriceBombProduct\PriceBombProductFacade;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Component\Domain\Domain;

class PriceBombProductDataFixture extends AbstractReferenceFixture implements DependentFixtureInterface
{
    /**
     * @var \App\Model\Product\PriceBombProduct\PriceBombProductFacade
     */
    protected $priceBombProductFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    protected $domain;

    /**
     * @param \App\Model\Product\PriceBombProduct\PriceBombProductFacade $priceBombProductFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(PriceBombProductFacade $priceBombProductFacade, Domain $domain)
    {
        $this->priceBombProductFacade = $priceBombProductFacade;
        $this->domain = $domain;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $defaultPriceBombProductReferenceNames = [
            ProductDataFixture::PRODUCT_PREFIX . '2',
            ProductDataFixture::PRODUCT_PREFIX . '12',
            ProductDataFixture::PRODUCT_PREFIX . '8',
        ];
        $distinctPriceBombProductReferenceNames = [
            ProductDataFixture::PRODUCT_PREFIX . '11',
            ProductDataFixture::PRODUCT_PREFIX . '16',
            ProductDataFixture::PRODUCT_PREFIX . '7',
        ];
        foreach ($this->domain->getAll() as $domainConfig) {
            $domainId = $domainConfig->getId();

            if ($domainId === Domain::SECOND_DOMAIN_ID) {
                $this->createPriceBombProductsForDomain($distinctPriceBombProductReferenceNames, $domainId);
            } else {
                $this->createPriceBombProductsForDomain($defaultPriceBombProductReferenceNames, $domainId);
            }
        }
    }

    /**
     * @param string[] $productReferenceNames
     * @param int $domainId
     */
    protected function createPriceBombProductsForDomain(array $productReferenceNames, int $domainId): void
    {
        $products = [];
        foreach ($productReferenceNames as $productReferenceName) {
            $products[] = $this->getReference($productReferenceName);
        }

        $this->priceBombProductFacade->savePriceBombProductsForDomain($domainId, $products);
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            ProductDataFixture::class,
        ];
    }
}
