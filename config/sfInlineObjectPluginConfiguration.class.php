<?php

/**
 * Plugin configuration for sfSympalInlineObjectPlugin
 * 
 * @package     sfInlineObjectPlugin
 * @subpackage  config
 * @author      Ryan Weaver <ryan@thatsquality.com>
 */
class sfInlineObjectPluginConfiguration extends sfPluginConfiguration
{
  public function initialize()
  {
    // Register the InlineObject autoloader
    require_once dirname(__FILE__).'/../lib/vendor/InlineObjectParser/lib/InlineObjectAutoloader.php';
    InlineObjectAutoloader::register();
  }

  /**
   * Returns the parser to be used to parse the inline objects
   * 
   * This allows us to effectively only have one parser instance without
   * implementing the singleton pattern
   * 
   * @param string $class The name of the class to use for the parser
   */
  public function getParser()
  {
    if ($this->_parser === null)
    {
      $this->_parser = sfInlineObjectParser::getInstance();
    }
    
    return $this->_parser;
  }
}