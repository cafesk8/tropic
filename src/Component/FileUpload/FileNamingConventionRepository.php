<?php

declare(strict_types=1);

namespace App\Component\FileUpload;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Shopsys\FrameworkBundle\Model\Product\Exception\ProductNotFoundException;
use Shopsys\FrameworkBundle\Model\Product\Product;

class FileNamingConventionRepository
{
    protected EntityManagerInterface $em;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     */
    public function __construct(
        EntityManagerInterface $em
    ) {
        $this->em = $em;
    }

    /**
     * @param int $id
     * @return \App\Model\Product\Product
     */
    public function getProductById(int $id)
    {
        $product = $this->findById($id);

        if ($product === null) {
            throw new ProductNotFoundException('Product with ID ' . $id . ' does not exist.');
        }

        return $product;
    }

    /**
     * @param int $id
     * @return \App\Model\Product\Product|null
     */
    public function findById(int $id): ?Product
    {
        return $this->getProductRepository()->find($id);
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getProductRepository(): EntityRepository
    {
        return $this->em->getRepository(Product::class);
    }
}
