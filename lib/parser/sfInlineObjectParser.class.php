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
    $inlineObjects = $parsed[1];

    /*
     * Iterate through all of the inline objects and collect a list of the
     * Doctrine inline objects and their keys
     */
    $doctrineTypes = array();
    foreach ($inlineObjects as $inlineObject)
    {
      $typeObject = $this->getType($inlineObject['type']);
      if ($typeObject->hasRelatedDoctrineObject())
      {
        $className = get_class($object);

        // if this is the first of this type, setup the array for the type
        if (!isset($doctrineTypes[$className]))
        {
          $doctrineTypes[$className] = array(
            'model'     => $object->getModel(),
            'keyColumn' => $object->getKeyColumn(),
            'keys'      => array(),
          );
        }

        // add the current name/key to the array of keys
        $doctrineTypes[$className]['keys'][] = $inlineObject['name'];
      }
    }
    
    $resources = array();

    /*
     * Iterate through all of the Doctrine types and instantiate the resource
     * that will be used to retrieve those doctrine objects
     */
    foreach ($doctrineTypes as $typeClass => $doctrineType)
    {
      /**
       * if we were given a base object to relate inline objects to, and if
       * the given model uses the sfInlineObjectContainerTemplate, then
       * use the more powerful related resource object to pull the objects
       * through that relationship.
       */
      if ($this->_doctrineRecord && $relation = $this->getRelation(get_class($this->_doctrineRecord), $doctrineType['model']))
      {
        $resources[$typeClass] = sfInlineObjectDoctrineRelatedResource::getInstance(
          $this->_doctrineRecord,
          $relation,
          $doctrineType['keyColumn']
        );
      }
      else
      {
        // use the normal doctrine resource which attempts to minimize queries
        $resources[$typeClass] = new sfInlineObjectDoctrineResource(
          $doctrineType['model'],
          $doctrineType['keyColumn']
        );
      }

      // tell the resource to query out and prepare for the given related objects
      $resources[$typeClass]->prepareObjects($doctrineType['keys']);
    }
    
    /*
     * Iterate through the original array and assign records where necessary
     */
    $renderedObjects = array();
    foreach ($inlineObjects as $key => $inlineObject)
    {
      $typeObject = $this->getType($inlineObject['type']);
      if ($typeObject->hasRelatedDoctrineObject())
      {
        $typeObject->setDoctrineResource($resources[get_class($typeObject)]);
      }

      $renderedObjects[$key] = $typeObject->render(
        $inlineObject['name'],
        $inlineObject['arguments']
      );
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
      return unserialize($this->_cacheDriver->get($key));
    }
  }

  /**
   * @see InlineObjectParser
   */
  public function setCache($key, $data)
  {
    if ($this->_cacheDriver)
    {
      return $this->_cacheDriver->set($key, serialize($data));
    }
  }

  /**
   * Sets the Doctrine_Record that relates to inline objects.
   * 
   * If this is set, and inline objects that refer to foreign Doctrine objects
   * will attempt to retrieve those objects through a true relationship
   * on the given Doctrine_Record
   * 
   * @param Doctrine_Record or false $record The record that sources the raw text
   */
  public function setDoctrineRecord($record)
  {
    $this->_doctrineRecord = $record;
  }

  /**
   * Returns the relation name (if one exists) that will allow us to retrieve
   * objects of the targetModel from the sourceModel.
   * 
   * So, if a Blog model embeds many Product objects and there is a
   * many-to-many relationship called Blog->Products, then
   * 
   * echo $this->getRelation('Blog', 'Product'); // returns 'Products'
   */
  public function getRelation($sourceModel, $targetModel)
  {
    $tbl = Doctrine_Core::getTable($sourceModel);
    $template = $tbl->getTemplate('sfInlineObjectContainerTemplate');
    
    if (!$template)
    {
      return false;
    }

    $relations = $template->getOption('relations');
    foreach ($relations as $relation)
    {
      if ($tbl->getRelation($relation)->getClass() == $targetModel)
      {
        return $relation;
      }
    }
    
    return false;
  }

  /**
   * This is not a singleton accessor, but rather a place to house the logic
   * for this class to bootstrap itself based on the application configuration
   * 
   * A better way to retrieve this class would be the sfInlineObjectPluginConfiguration
   * 
   * @return sfInlineObjectParser
   */
  public static function createInstance()
  {
    $class = sfConfig::get('app_inline_object_parser_class', 'sfInlineObjectParser');
    $parser = new $class();

    // add the types to the parser
    $types = sfConfig::get('app_inline_object_types', array());
    foreach ($types as $key => $typeConfig)
    {
      if (!is_array($typeConfig))
      {
        // help out with the old syntax
        throw new sfException(
          'The inline_object_types config must now specify an array of
          options instead of just a scalar value (which was the class):
          all:
            inline_object:
              types:
                my_type:
                  class:    myInlineObjectTyepe'
        );
      }

      $class = isset($typeConfig['class']) ? $typeConfig['class'] : 'sfInlineObjectType';
      unset($typeConfig['class']);

      $type = new $class($key, $typeConfig);
      $parser->addType($type);
    }

    // configure the cache
    $cacheConfig = sfConfig::get('app_inline_object_cache');
    if ($cacheConfig['enabled'])
    {
      $class = $cacheConfig['class'];
      $args = isset($cacheConfig['options']) ? $cacheConfig['options'] : array();
      
      $cache = new $class($args);
      $parser->setCacheDriver($cache);
    }

    return $parser;
  }
}