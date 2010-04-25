<?php

/**
 * Wrapper around InlineObjectParser to allow for
 *  * caching
 *  * app.yml configuration
 * 
 * @package     sfInlineObjectPlugin
 * @subpackage  parser
 * @author      Ryan Weaver <ryan@thatsquality.com>
 */
class sfInlineObjectParser extends InlineObjectParser
{
  protected
    $_cacheDriver;

  /**
   * @var Doctrine_Record An optional record that the inline objects relate to
   */
  protected
    $_doctrineRecord;

  /**
   * Overridden to read the types from config
   */
  public function __construct($types = array())
  {
    // Merge the given types onto the types from the config
    $types = array_merge(
      sfConfig::get('app_inline_object_types', array()),
      $types
    );
    
    parent::__construct($types);
  }

  /**
   * Overridden to allow for extra setup to be done to support efficient
   * querying for inline objects that refer to Doctrine objects
   * 
   * @see InlineObjectParser
   */
  public function parse($text, $key = null)
  {
    // Parse the string to retrieve tokenized text and an array of InlineObjects
    $parsed = $this->parseTypes($text, $key);
    
    $text = $parsed[0];
    $objects = $parsed[1];

    /*
     * Iterate through all of the inline objects and collect a list of the
     * Doctrine inline objects and their keys
     */
    $doctrineTypes = array();
    foreach ($objects as $object)
    {
      if ($object instanceof sfInlineObjectDoctrineType)
      {
        $className = get_class($object);
        if (!isset($doctrineTypes[$className]))
        {
          $doctrineTypes[$className] = array(
            'model'     => $object->getModel(),
            'keyColumn' => $object->getKeyColumn(),
            'keys'      => array(),
          );
        }
        
        $doctrineTypes[$className]['keys'] = $object->getName();
      }
    }
    
    $relationsConfig = sfConfig::get('app_inline_object_relations');
    $resources = array();

    /*
     * Iterate through all of the Doctrine types and instantiate the resource
     * that will be used to retrieve those doctrine objects
     */
    foreach ($doctrineTypes as $typeClass => $doctrineType)
    {
      $relationConfig = isset($relationsConfig[$doctrineType['model']]) ? $relationsConfig[$doctrineType['model']] : array();

      if ($this->_doctrineRecord && isset($relationConfig[get_class($this->_doctrineRecord)]))
      {
        $resources[$typeClass] = sfInlineObjectDoctrineRelatedResource::getInstance(
          $this->_doctrineRecord,
          $relationConfig[get_class($this->_doctrineRecord)],
          $doctrineType['keyColumn'],
        );
      }
      else
      {
        $resources[$typeClass] = new sfInlineObjectDoctrineResource(
          $doctrineType['model'],
          $doctrineType['keyColumn'],
        );
      }
      
      $resources[$typeClass]->prepareObjects($doctrineType['keys']);
    }
    
    /*
     * Iterate through all of the objects and assign resources where necessary
     */
    $renderedObjects = array();
    foreach ($objects as $object)
    {
      if ($object instanceof sfInlineObjectDoctrineType)
      {
        $object->setDoctrineResource($resources[get_class($object)]);
      }
      $renderedObjects[] = $object->render();
    }

    return $this->_combineTextAndRenderedObjects($text, $renderedObjects);
  }

  /**
   * Allows the optional sfSympalCacheManager dependency to be specified
   * 
   * @param sfSympalCacheManager $cacheManager The cache manager to use for caching
   */
  public function setCacheDriver(sfCache $cacheDriver)
  {
    $this->_cacheDriver = $cacheDriver;
  }

  /**
   * Returns the cache driver
   */
  public function getCacheDriver()
  {
    return $this->_cacheDriver;
  }

  /**
   * @see InlineObjectParser
   */
  public function getCache($key)
  {
    if ($this->_cacheDriver)
    {
      return $this->_cacheDriver->get($key);
    }
  }

  /**
   * @see InlineObjectParser
   */
  public function setCache($key, $data)
  {
    if ($this->_cacheDriver)
    {
      return $this->_cacheDriver->set($key, $data);
    }
  }

  /**
   * Sets the Doctrine_Record that relates to inline objects.
   * 
   * If this is set, and inline objects that refer to foreign Doctrine objects
   * will attempt to retrieve those objects through a true relationship
   * on the given Doctrine_Record
   * 
   * @param Doctrine_Record $record The record that sources the raw text
   */
  public function setDoctrineRecord(Doctrine_Record $record)
  {
    $this->_doctrineRecord = $record;
  }

  /**
   * Initialize the parser
   */
  protected function _initialize()
  {
    // Setup the cache, if enabled
    $cacheConfig = sfConfig::get('app_inline_object_cache');
    if ($cacheConfig['enabled'])
    {
      $class = $cacheConfig['class'];
      $args = isset($cacheConfig['options']) ? $cacheConfig['options'] : array();
      
      $cache = new $class($args);
      $this->setCacheDriver($cache);
    }
  }
}