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