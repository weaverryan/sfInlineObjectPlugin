<?php

/**
 * Listens to the component.method_not_found event and acts like a subclass
 * to the actions class
 * 
 * @package     sfInlineObjectPlugin
 * @subpackage  action
 * @author      Ryan Weaver <ryan@thatsquality.com>
 */
class sfInlineObjectAction
{
  /**
   * Listens to the component.method_not_found event to effectively
   * extend the actions class
   */
  public function listenComponentMethodNotFound(sfEvent $event)
  {
    $method = $event['method'];
    $arguments = $event['arguments'];

    if (method_exists($this, $method))
    {
      $result = call_user_func_array(array($this, $method), $arguments);

      $event->setReturnValue($result);

      return true;
    }
    else
    {
      return false;
    }
  }

  /**
   * Returns the current inline object parser
   * 
   * @return sfInlineObjectParser
   */
  public function getInlineObjectParser(sfActions $action)
  {
    return $action->getContext()
      ->getConfiguration()
      ->getPluginConfiguration('sfInlineObjectPlugin')
      ->getParser();
  }
}