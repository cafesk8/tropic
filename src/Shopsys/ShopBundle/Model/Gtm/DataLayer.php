<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Gtm;

class DataLayer
{
    /**
     * @var string
     */
    private $locale;

    /**
     * @var array
     */
    private $data;

    /**
     * @var array
     */
    private $pushes;

    /**
     * @param string $locale
     */
    public function __construct(string $locale)
    {
        $this->locale = $locale;
        $this->data = [];
        $this->pushes = [];
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function getPushes(): array
    {
        return $this->pushes;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get(string $key)
    {
        return $this->data[$key];
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function set(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * @param string $eventName
     * @param array $eventData
     */
    public function addEvent(string $eventName, array $eventData = []): void
    {
        $event = array_merge(
            ['event' => $eventName],
            $eventData
        );

        if (array_key_exists('event', $this->data)) {
            $this->push($event);
            return;
        }

        $this->data = array_merge(
            $this->data,
            $event
        );
    }

    /**
     * @param array $data
     */
    public function push(array $data): void
    {
        $this->pushes[] = $data;
    }
}
