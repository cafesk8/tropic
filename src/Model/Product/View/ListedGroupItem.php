<?php

declare(strict_types=1);

namespace App\Model\Product\View;

use Shopsys\ReadModelBundle\Image\ImageView;

class ListedGroupItem
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $amount;

    /**
     * @var \Shopsys\ReadModelBundle\Image\ImageView|null
     */
    private $image;

    /**
     * @param int $id
     * @param string $name
     * @param int $amount
     * @param \Shopsys\ReadModelBundle\Image\ImageView|null $imageView
     */
    public function __construct(int $id, string $name, int $amount, ?ImageView $imageView)
    {
        $this->id = $id;
        $this->name = $name;
        $this->amount = $amount;
        $this->image = $imageView;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @return \Shopsys\ReadModelBundle\Image\ImageView|null
     */
    public function getImage(): ?ImageView
    {
        return $this->image;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'amount' => $this->getAmount(),
            'image' => $this->getImage(),
        ];
    }
}
