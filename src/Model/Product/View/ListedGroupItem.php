<?php

declare(strict_types=1);

namespace App\Model\Product\View;

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
     * @param int $id
     * @param string $name
     * @param int $amount
     */
    public function __construct(int $id, string $name, int $amount)
    {
        $this->id = $id;
        $this->name = $name;
        $this->amount = $amount;
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
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'amount' => $this->getAmount(),
        ];
    }
}
