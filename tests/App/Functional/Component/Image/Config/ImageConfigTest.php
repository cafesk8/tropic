<?php

declare(strict_types=1);

namespace Tests\App\Functional\Component\Image\Config;

use App\Model\Product\Product;
use Shopsys\FrameworkBundle\Component\Image\Config\ImageConfig;
use Shopsys\FrameworkBundle\Model\Product\Product as BaseProduct;
use Tests\App\Test\FunctionalTestCase;

class ImageConfigTest extends FunctionalTestCase
{
    public function testGetImageConfigForExtendedEntity()
    {
        $imageConfig = $this->getContainer()->get(ImageConfig::class);

        $baseProductImageConfig = $imageConfig->getImageEntityConfigByClass(BaseProduct::class);
        $projectProductImageConfig = $imageConfig->getImageEntityConfigByClass(Product::class);

        self::assertEquals($projectProductImageConfig, $baseProductImageConfig);
    }
}
