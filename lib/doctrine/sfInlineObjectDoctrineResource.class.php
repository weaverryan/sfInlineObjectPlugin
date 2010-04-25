<?php

/**
 * Class that assist in querying for a group of objects in as efficient
 * manner as possible
 * 
 * @package     sfInlineObjectPlugin
 * @subpackage  doctrine
 * @author      Ryan Weaver <ryan@thatsquality.com>
 */
class sfInlineObjectDoctrineResource
{
  protected
    $_model,
    $_keyColumn;

  protected
    $_objects;

  /**
   * Class constructor
   */
  public function __construct($model, $keyColumn)
  {
    $this->_model = $model;
    $this->_keyColumn = $keyColumn;
  }

  /**
   * Retrieves an array of objects based off of their keys.
   * 
   * The key should correspond to the keyColumn given when constructing this resource
   * 
   * @param array() $keys The keys of the objects that should be prepared
   */
  public function prepareObjects($keys)
  {
    $objects = $this->_getObjects($keys);
    $this->_objects = $this->_buildObjectArray($objects);
  }

  /**
   * Returns a foreign object identified by the given key
   * 
   * This must be called after prepareObjects(), else newly related objects
   * may not be returned correctly
   * 
   * @return Doctrine_Record or null
   */
  public function getObject($key)
  {
    if ($this->_objects === null)
    {
      throw new sfException('->prepareObjects() should be called before getObject()');
    }

    return isset($this->_objects[$key]) ? $this->_objects[$key] : null;
  }

  /**
   * This queries and returns a collection of records based on the given keys
   * 
   * @param array $keys The array of keys to use when querying for the objects
   */
  protected function _getObjects($keys)
  {
    return $this->_getQueryForObjects($keys)->execute();
  }

  /**
   * Returns the query that should be used if we need to query out
   * and get a collection of the foreign objects
   */
  protected function _getQueryForObjects($keys)
  {
    $q = Doctrine_Core::getTable($this->_model)
      ->createQuery('a')
      ->whereIn('a.'.$this->_keyColumn, array_unique($keys))
      ->orderBy('a.'.$this->_keyColumn.' ASC');

    return $q;
  }

  /**
   * Takes a doctrine collection and reorganizes it into an array where
   * the key of the array is value defined by the $_field field
   * 
   * @return array()
   */
  protected function _buildObjectArray(Doctrine_Collection $collection)
  {
    $objects = new Doctrine_Collection($collection->getTable());
    foreach ($collection as $object)
    {
      $objects[$object->get($this->_keyColumn)] = $object;
    }

    return $objects;
  }

  /**
   * Clears all of the instances
   */
  public static function clearInstances()
  {
    self::$_instances = array();
  }
}