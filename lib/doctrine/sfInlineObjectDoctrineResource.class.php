<?php

/**
 * Internal utility class that helps retrieve foreign Doctrine relations
 * 
 * Given a Doctrine_Record instance and a hasMany relation name, one can
 * ask this object for individual records from that collection by some
 * identifier. If a record of a particular identifier doesn't exist on
 * the collection, it will be added.
 * 
 * Effectively, this allows for individual records from a hasMany() relationship
 * to be retrieved without additional queries (since it goes through the
 * real relationship).
 * 
 * @package     sfInlineObjectPlugin
 * @subpackage  doctrine
 * @author      Ryan Weaver <ryan@thatsquality.com>
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */

class sfInlineObjectDoctrineResource
{
  protected
    $_record,
    $_relation,
    $_field;

  protected
    $_objects;
  
  protected static
    $_instances = array();

  /**
   * Class constructor
   */
  public function __construct(Doctrine_Record $record, $relation, $field)
  {
    $this->_record = $record;
    $this->_relation = $relation;
    $this->_field = $field;
  }

  /**
   * Retrieves an array of objects based off of their names.
   * 
   * The name should correspond to the field given when constructing this resource
   * 
   * @param string $name The name/id of the slug to retrieve
   */
  public function prepareObjects($names)
  {
    $objects = $this->_getObjects($names);
    $this->_objects = $this->_buildObjectArray($objects);
  }

  /**
   * Returns a foreign object identified by the given name
   * 
   * This must be called after prepareObjects(), else newly related objects
   * may not be returned correctly
   * 
   * @return Doctrine_Record or null
   */
  public function getObject($name)
  {
    if ($this->_objects === null)
    {
      throw new sfException('->prepareObjects() should be called before getObject()');
    }

    return isset($this->_objects[$name]) ? $this->_objects[$name] : null;
  }

  /**
   * Retrieves an instance based on the modelName and relationName
   * 
   * @param Doctrine_Record $record   The record from which to retrieve the foreign objects
   * @param string          $relation The name of the relation to use for retrieving the objects
   * @param string          $field    The name of the field that's used as the "identifier" or "name"
   * 
   * @return sfInlineObjectResource
   */
  public function getInstance(Doctrine_Record $record, $relation, $field)
  {
    $instanceName = sprintf('%s_%s_%s', get_class($record), $record->id, $relation);
    
    if (!isset(self::$_instances[$instanceName]))
    {
      $instance = new self($record, $relation, $field);
      
      self::$_instances[$instanceName] = $instance;
    }
    
    return self::$_instances[$instanceName];
  }

  /**
   * Given an array of names, this creates any new links from the $_record
   * variable to the foreign relation and then returns all objects in the
   * foreign relation.
   * 
   * This ensures that all foreign objects we're looking for are set on
   * the relation
   */
  protected function _getObjects($names)
  {
    $currentObjects = $this->record->get($this->_relation);
    $currentNames = $this->_getNamesFromCollection($currentObjects);
    asort($names);

    if (array_diff($names, $currentNames) || array_diff($currentNames, $names))
    {
      $objects = $this->_getQueryForObjects($names)->execute();

      $ids = array();
      foreach ($objects as $object)
      {
        $ids[] = $object->id;
      }
      
      $this->_record->link($this->_relation, $ids);
      //$this->_record->disableSearchIndexUpdateForSave();
      $this->_record->save();
    }

    return $this->_record->get($this->_relation);
  }

  /**
   * Helper to retrieve an array of names/ids from a doctrine collection
   * 
   * @param Doctrine_Collection $collection
   * @return array
   */
  protected function _getNamesFromCollection(Doctrine_Collection $collection)
  {
    $names = array();
    foreach ($collection as $object)
    {
      $names[] = $object->get($this->_field);
    }

    return $names;
  }

  /**
   * Returns the query that should be used if we need to query out
   * and get a collection of the foreign objects
   */
  protected function _getQueryForObjects($names)
  {
    $q = Doctrine_Core::getTable($this->_getForeignModelName())
      ->createQuery('a')
      ->whereIn('a.'.$this->_field, array_unique($names))
      ->orderBy('a.'.$this->_field.' ASC');

    return $q;
  }

  /**
   * Returns the model name of the foreign objects that we're retrieving
   */
  protected function _getForeignModelName()
  {
    return $this->_record->getTable()->getRelation($this->_relation)->getClass();
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
      $objects[$object->get($this->_field)] = $object;
    }

    return $objects;
  }
}