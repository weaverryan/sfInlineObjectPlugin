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

  protected $_relatedObject;

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
   * Returns the related Doctrine object.
   * 
   * If a doctrine resource has been set (which attempts to aggregate many
   * inline doctrine objects together to minimize queries), then it will
   * be used to retreive the object.
   * 
   * If no doctrine resource is set, this will query or the object.
   * 
   * @return Doctrine_Record
   */
  public function getRelatedObject()
  {
    if ($this->_relatedObject === null)
    {
      if ($this->doctrineResource)
      {
        $this->_relatedObject = $this->doctrineResource->getObject($this->getName());
      }
      else
      {
        $this->_relatedObject = Doctrine_Core::getTable($this->getModel())
          ->createQuery('a')
          ->where('a.'.$this->getKeyColumn().' = ?', $this->getName())
          ->fetchOne();
      }
    }
    
    return $this->_relatedObject;
  }

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