<?php

/**
 * Internal utility class that helps retrieve foreign Doctrine relations
 * 
 * Given a Doctrine_Record instance and a hasMany relation, one can
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

class sfInlineObjectDoctrineRelatedResource extends sfInlineObjectDoctrineResource
{
  protected
    $_record,
    $_relation;
  
  protected static
    $_instances = array();

  /**
   * Class constructor
   */
  public function __construct(Doctrine_Record $record, $relation, $keyColumn)
  {
    $this->_record = $record;
    $this->_relation = $relation;
    
    $model = $this->_record->getTable()->getRelation($this->_relation)->getClass();

    parent::__construct($model, $keyColumn);
  }

  /**
   * Retrieves an instance based on the record and relationName
   * 
   * @param Doctrine_Record $record   The record from which to retrieve the foreign objects
   * @param string          $relation The name of the relation to use for retrieving the objects
   * @param string          $field    The name of the field that's used as the "identifier" or "name"
   * 
   * @return sfInlineObjectResource
   */
  public static function getInstance(Doctrine_Record $record, $relation, $keyColumn)
  {
    $instanceName = sprintf('%s_%s_%s', get_class($record), $record->id, $relation);
    
    if (!isset(self::$_instances[$instanceName]))
    {
      $instance = new self($record, $relation, $keyColumn);
      
      self::$_instances[$instanceName] = $instance;
    }
    
    return self::$_instances[$instanceName];
  }
  
  /**
   * Returns the related doctrine object
   * 
   * @return Doctrine_Record
   */
  public function getRecord()
  {
    return $this->_record;
  }

  /**
   * Given an array of keys, this creates any new links from the $_record
   * variable to the foreign relation and then returns all objects in the
   * foreign relation.
   * 
   * This ensures that all foreign objects we're looking for are set on
   * the relation
   */
  protected function _getObjects($keys)
  {
    $currentObjects = $this->_record->get($this->_relation);
    $currentKeys = $this->_getKeysFromCollection($currentObjects);
    asort($keys);

    if (array_diff($keys, $currentKeys) || array_diff($currentKeys, $keys))
    {
      $objects = $this->_getQueryForObjects($keys)->execute();

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
  protected function _getKeysFromCollection(Doctrine_Collection $collection)
  {
    $keys = array();
    foreach ($collection as $object)
    {
      $keys[] = $object->get($this->_keyColumn);
    }

    return $keys;
  }
}