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
  public function postInsert(Doctrine_Event $event)
  {
    $event->getInvoker()->deleteLinkAndAssetReferences();
  }

  public function postUpdate(Doctrine_Event $event)
  {
    $event->getInvoker()->deleteLinkAndAssetReferences();
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
    $modelConfig = sfConfig::get('app_inline_object_relations', array());
    $modelName = get_clas($event->getInvoker());

    // Find the array of relations on this model that are InlineObject relations
    $relations = isset($models[$modelName]) ? $models[$modelName] : array();
    foreach ($relations as $relation)
    {
      // Unlink ALL related objects on the given relation
      $event->getInvoker()->unlink($relation, array(), true);
    }
  }
}