<?php

/**
 * Listener class for sfInlineObjectTemplate
 * 
 * @package     sfInlineObjectPlugin
 * @subpackage  behavior
 * @author      Ryan Weaver <ryan@thatsquality.com>
 */

class sfInlineObjectContainerListener extends Doctrine_Record_Listener
{
  protected $_options = array();
  
  public function __construct(array $options)
  {
    $this->_options = $options;
  }

  /**
   * Clear the related inline objects on insert
   */
  public function postInsert(Doctrine_Event $event)
  {
    $this->_deleteInlineObjectReferences($event);
  }

  /**
   * Clear the related inline objects on update
   */
  public function postUpdate(Doctrine_Event $event)
  {
    $this->_deleteInlineObjectReferences($event);
  }

  /**
   * Removes all references to related objects that are as a result of
   * inline object references
   * 
   * This guarantees that if a reference to a related object is removed
   * from an inline text field, that relation won't continue to exist forever.
   * In that sense, this is effectively a "cache clear" for these relations.
   */
  protected function _deleteInlineObjectReferences(Doctrine_Event $event)
  {
    foreach ($this->_options['relations'] as $relation)
    {
      // Unlink ALL related objects on the given relation
      $event->getInvoker()->unlink($relation, array(), true);
    }
  }
}