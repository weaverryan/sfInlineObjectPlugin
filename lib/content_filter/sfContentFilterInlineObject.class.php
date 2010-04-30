<?php

/**
 * Requires sfContentFilterPlugin
 * 
 * This makes the inline object filtering available automatically if the
 * sfContentFilterPlugin is present
 * 
 * @package     sfInlineObjectPlugin
 * @subpackage  content_filter
 * @author      Ryan Weaver <ryan@thatsquality.com>
 */
class sfContentFilterInlineObject extends sfContentFilterAbstract
{

  /**
   * @see sfContentFilterAbstract
   */
  public function _doFilter($content)
  {
    return sfApplicationConfiguration::getActive()
      ->getPluginConfiguration('sfInlineObjectPlugin')
      ->getParser()
      ->parse($content);
  }
}