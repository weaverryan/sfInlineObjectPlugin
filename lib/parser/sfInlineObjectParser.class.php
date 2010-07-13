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

    // iterate through each inline object andset its doctrine resource (if necessary)
    $resources = array();
    foreach ($inlineObjects as $key => $inlineObject)
    {
      $typeObject = $this->getType($inlineObject['type']);
      if ($typeObject->hasRelatedDoctrineObject())
      {
        $typeClass = get_class($typeObject);

        // make sure that we've got the resource in our resources array
        if (!isset($resources[$typeClass]))
        {
          $resources[$typeClass] = array(
            'resource'  => $this->_createDoctrineResource($typeObject->getOption('model'), $typeObject->getOption('key_column')),
            'keys'      => array(),
          );
        }

        // add this object's name to the list of keys for the resource
        $resources[$typeClass]['keys'][] = $inlineObject['name'];

        // set the resource on the object
        $typeObject->setDoctrineResource($resources[$typeClass]['resource']);
      }
    }

    // initialize each resource with the array of keys
    foreach ($resources as $resourceArr)
    {
      $resourceArr['resource']->prepareObjects($resourceArr['keys']);
    }

    // Create an array of the text from the rendered objects
    $renderedObjects = $this->_renderInlineObjectsFromArray($inlineObjects);

    return $this->_combineTextAndRenderedObjects($text, $renderedObjects);
  }

  /**
   * Creates a new sfInlineObjectDoctrineResource|sfInlineObjectDoctrineRelatedResource
   * instance for the given inline object type
   *
   * @return sfInlineObjectDoctrineResource
   */
  protected function _createDoctrineResource($relatedModel, $keyColumn)
  {
    /**
     * if we were given a base object to relate inline objects to, and if
     * the given model uses the sfInlineObjectContainerTemplate, then
     * use the more powerful related resource object to pull the objects
     * through that relationship.
     */
    if ($this->_doctrineRecord && $relation = $this->getRelation(get_class($this->_doctrineRecord), $relatedModel))
    {
      return sfInlineObjectDoctrineRelatedResource::getInstance(
        $this->_doctrineRecord,
        $relation,
        $keyColumn
      );
    }
    else
    {
      // use the normal doctrine resource which attempts to minimize queries
      return new sfInlineObjectDoctrineResource(
        $relatedModel,
        $keyColumn
      );
    }
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