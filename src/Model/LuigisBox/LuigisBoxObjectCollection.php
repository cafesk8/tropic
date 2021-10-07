<?php

declare(strict_types=1);

namespace App\Model\LuigisBox;

class LuigisBoxObjectCollection
{
    /**
     * @var \App\Model\LuigisBox\LuigisBoxObject[]
     */
    public array $objects = [];

    /**
     * @param \App\Model\LuigisBox\LuigisBoxObject $element
     */
    public function add(LuigisBoxObject $element): void
    {
        $this->objects[] = $element;
    }

    public function clear(): void
    {
        $this->objects = [];
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->objects);
    }

    /**
     * @param int $key
     */
    public function remove(int $key)
    {
        unset($this->objects[$key]);
    }

    /**
     * @return \App\Model\LuigisBox\LuigisBoxObject[]
     */
    public function toArray(): array
    {
        return $this->objects;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->objects);
    }

    /**
     * Temporary function to migrate from URL identification to catnum/ID identification
     */
    public function convertToOldIdentification(): void
    {
        foreach ($this->objects as $object) {
            $object->url = $object->fields->web_url;

            foreach ($object->nested as $nestedObject) {
                $nestedObject->url = $nestedObject->fields->web_url;
                $this->objects[] = $nestedObject;
            }
        }
    }
}
