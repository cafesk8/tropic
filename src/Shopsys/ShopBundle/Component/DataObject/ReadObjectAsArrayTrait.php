<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\DataObject;

use ArrayIterator;
use Shopsys\ShopBundle\Component\DataObject\Exception\NotImplementedException;
use Traversable;

/**
 * This trait is created for objects, which can be traversable like array
 */
trait ReadObjectAsArrayTrait
{
    /**
     * Whether a offset exists
     * @see https://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return bool true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return property_exists($this, $offset);
    }

    /**
     * Offset to retrieve
     * @link https://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed can return all value types
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return $this->{$offset};
    }

    /**
     * Offset to set
     * @link https://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * @param mixed $value <p>
     * </p>
     * The value to set.
     * </p>
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        throw new NotImplementedException('Method VentusData::offsetSet is not implemented yet');
    }

    /**
     * Offset to unset
     * @link https://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        throw new NotImplementedException('Method VentusData::offsetUnset is not implemented yet');
    }

    /**
     * Retrieve an external iterator
     * @link https://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return \Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator()
    {
        return new ArrayIterator($this);
    }
}
