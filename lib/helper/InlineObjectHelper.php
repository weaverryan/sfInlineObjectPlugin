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
 * @param Doctrine_Record $record The optional record to relate the inline objects to
 */
function parse_inline_object($content, $record = null)
{
  // Unescape the Doctrine_Record if necessary
  if ($record instanceof sfOutputEscaperObjectDecorator)
  {
    $record = $record->getRawValue();
  }
  
  $parser = get_inline_object_parser();
  
  if ($record !== null)
  {
    $parser->setDoctrineRecord($record);
  }
  
  return $parser->parse($content);
}

/**
 * Returns the sfInlineObjectParser object
 * 
 * @return sfInlineObjectParser
 */
function get_inline_object_parser()
{
  return sfApplicationConfiguration::getActive()
    ->getPluginConfiguration('sfInlineObjectPlugin')
    ->getParser();
}

