<?php

declare(strict_types=1);

namespace App\DataFixtures\Demo;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Product\TopProduct\TopProductFacade;

class TopProductDataFixture extends AbstractReferenceFixture implements DependentFixtureInterface
{
    /**
     * @var \App\Model\Product\TopProduct\TopProductFacade
     */
    protected $topProductFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    protected $domain;

    /**
     * @param \App\Model\Product\TopProduct\TopProductFacade $topProductFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(TopProductFacade $topProductFacade, Domain $domain)
    {
        $this->topProductFacade = $topProductFacade;
        $this->domain = $domain;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $defaultTopProductReferenceNames = [
            ProductDataFixture::PRODUCT_PREFIX . '1',
            ProductDataFixture::PRODUCT_PREFIX . '17',
            ProductDataFixture::PRODUCT_PREFIX . '9',
            ProductDataFixture::PRODUCT_PREFIX . '38',
        ];
        $distinctTopProductReferenceNames = [
            ProductDataFixture::PRODUCT_PREFIX . '14',
            ProductDataFixture::PRODUCT_PREFIX . '10',
            ProductDataFixture::PRODUCT_PREFIX . '7',
            ProductDataFixture::PRODUCT_PREFIX . '48',
        ];
        foreach ($this->domain->getAll() as $domainConfig) {
            $domainId = $domainConfig->getId();

            if ($domainId === Domain::SECOND_DOMAIN_ID) {
                $this->createTopProductsForDomain($distinctTopProductReferenceNames, $domainId);
            } else {
                $this->createTopProductsForDomain($defaultTopProductReferenceNames, $domainId);
            }
        }
    }

    /**
     * @param string[] $productReferenceNames
     * @param int $domainId
     */
    protected function createTopProductsForDomain(array $productReferenceNames, int $domainId): void
    {
        $products = [];
        foreach ($productReferenceNames as $productReferenceName) {
            $products[] = $this->getReference($productReferenceName);
        }

        $this->topProductFacade->saveTopProductsForDomain($domainId, $products);
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
