<?php

/**
 * Wrapper for the InlineObjectType class
 * 
 * @package     sfInlineObjectPlugin
 * @subpackage  type
 * @author      Ryan Weaver <ryan@thatsquality.com>
 */

abstract class sfInlineObjectDoctrineType extends sfInlineObjectType
{
  protected $_doctrineResource;

  /**
   * Should return the model name that this inline object refers to
   * 
   * @return string
   */
  abstract public function getModel();

  /**
   * Should return the key column on the model used to query for the referenced objects
   * 
   * @return string
   */
  abstract public function getKeyColumn();

  /**
   * Used to set a Doctrine resource instance on this object.
   * 
   * If set, that resource will be used to return the related Doctrine object,
   * which is almost always more efficient than querying directly here
   */
  public function setDoctrineResource(sfInlineObjectDoctrineResource $resource)
  {
    $this->_doctrineResource = $resource;
  }
}