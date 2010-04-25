<?php

/**
 * Allows a Doctrine_Record to embed foreign-related objects in text fields
 * while keeping real relations and avoiding extra queries
 * 
 * @package     sfInlineObjectPlugin
 * @subpackage  behavior
 * @author      Ryan Weaver <ryan@thatsquality.com>
 */

class sfInlineObjectContainerTemplate extends Doctrine_Template
{

  /**
   * Set the table definition for sfInlineObjectTemplate
   */
  public function setTableDefinition()
  {
    $this->addListener(new sfInlineObjectContainerListener());
  }
}