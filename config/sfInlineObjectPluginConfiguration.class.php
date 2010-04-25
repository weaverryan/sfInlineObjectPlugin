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
}