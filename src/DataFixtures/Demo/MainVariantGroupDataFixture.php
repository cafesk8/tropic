<?php

declare(strict_types=1);

namespace App\DataFixtures\Demo;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterFacade;
use App\Model\Product\MainVariantGroup\MainVariantGroupFacade;

class MainVariantGroupDataFixture extends AbstractReferenceFixture implements DependentFixtureInterface
{
    /**
     * @var \App\Model\Product\MainVariantGroup\MainVariantGroupFacade
     */
    private $mainVariantGroupFacade;

    /**
     * @var \App\Model\Product\Parameter\ParameterFacade
     */
    private $parameterFacade;

    /**
     * @param \App\Model\Product\MainVariantGroup\MainVariantGroupFacade $mainVariantGroupFacade
     * @param \App\Model\Product\Parameter\ParameterFacade $parameterFacade
     */
    public function __construct(MainVariantGroupFacade $mainVariantGroupFacade, ParameterFacade $parameterFacade)
    {
        $this->mainVariantGroupFacade = $mainVariantGroupFacade;
        $this->parameterFacade = $parameterFacade;
    }

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $parameter = $this->parameterFacade->findParameterByNames(\App\Model\Product\Parameter\ParameterFacade::PARAMETER_SIZE);

        $this->mainVariantGroupFacade->createMainVariantGroup($parameter, [
            $this->getReference(ProductDataFixture::PRODUCT_PREFIX . '148'),
            $this->getReference(ProductDataFixture::PRODUCT_PREFIX . '149'),
            $this->getReference(ProductDataFixture::PRODUCT_PREFIX . '150'),
        ]);
    }

    /**
     * This method must return an array of fixtures classes
     * on which the implementing class depends on
     *
     * @return array
     */
    public function getDependencies()
    {
        return [
            ProductDataFixture::class,
        ];
    }
}
