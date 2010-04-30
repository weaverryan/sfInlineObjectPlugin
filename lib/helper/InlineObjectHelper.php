<?php

/**
 * Contains useful functions for filtering the inline objects in the view
 * 
 * @package     sfInlineObjectPlugin
 * @subpackage  helper
 * @author      Ryan Weaver <ryan@thatsquality.com>
 */

/**
 * Filters the given content based on the given inputType
 * 
 * @example
 * parse_inline_object('My content [photo:flower]');
 * 
 * @param string $content   The raw content that will be parsed
 */
function parse_inline_object($content, Doctrine_Record $record = null)
{
  $parser = sfApplicationConfiguration::getActive()
    ->getPluginConfiguration('sfInlineObjectPlugin')
    ->getParser();
  
  if ($record !== null)
  {
    $parser->setDoctrineRecord($record);
  }
  
  return $parser->parse($content);
}