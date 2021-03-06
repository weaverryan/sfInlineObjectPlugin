<?php

/**
 * Wrapper for the InlineObjectType class
 * 
 * @package     sfInlineObjectPlugin
 * @subpackage  type
 * @author      Ryan Weaver <ryan@thatsquality.com>
 */

class sfInlineObjectType extends InlineObjectType
{
  /**
   * Used to retrieve objects in the event that this object is linked
   * to a Doctrine record
   *
   * @var sfInlineObjectDoctrineResource
   */
  protected $_doctrineResource;

  /**
   * Available options for this class are:
   *
   *  * model: The Doctrine model name to use as a datasource
   *  * key_column The column on the above model to use as the key column
   *  * partial A partial (module/partial) to use to render this object
   */


  /**
   * Attempts to render the object by using the partial option.
   *
   * @return string
   */
  public function render($name, $arguments)
  {
    if (!$this->getOption('partial'))
    {
      throw new sfException(sprintf(
        'The inline object "%s" cannot be rendered. Either specify a
        partial or override render() in a subclass.',
        $name
      ));
    }

    sfApplicationConfiguration::getActive()->loadHelpers('Partial');

    return get_partial($this->getOption('partial'), array(
      'inline_object' => $this,
      'name'          => $name,
      'arguments'     => $arguments,
    ));
  }

  /**
   * Returns the related Doctrine object.
   *
   * If a doctrine resource has been set (which attempts to aggregate many
   * inline doctrine objects together to minimize queries), then it will
   * be used to retrieve the object.
   *
   * If no doctrine resource is set, this will query or the object.
   *
   * @param string $name The name of the related object to retrieve
   * @return Doctrine_Record
   */
  public function getRelatedObject($name)
  {
    $model = $this->getOption('model');
    $keyColumn = $this->getOption('key_column');
    if (!$model)
    {
      throw new sfException(sprintf('getRelatedObject() on inline object of type "%s" cannot be called: it is missing the "model" option.', $this->getName()));
    }
    if (!$keyColumn)
    {
      throw new sfException(sprintf('getRelatedObject() on inline object of type "%s" cannot be called: it is missing the "key_column" option.', $this->getName()));
    }

    if ($this->_doctrineResource)
    {
      $obj = $this->_doctrineResource->getObject($name);
    }
    else
    {
      $obj = Doctrine_Core::getTable($model)
        ->createQuery('a')
        ->where('a.'.$keyColumn.' = ?', $name)
        ->fetchOne();
    }

    // throw an event if the object was not located
    if (!$obj)
    {
      sfProjectConfiguration::getActive()
        ->getEventDispatcher()
        ->notify(new sfEvent($this, 'inline_object.related_object_not_found', array(
          'type'        => $this->getName(),
          'model'       => $model,
          'key_column'  => $keyColumn,
          'name'        => $name,
        )
      ));
    }

    return $obj;
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

  /**
   * Returns whether or not this type is setup to have a related
   * Doctrine object.
   *
   * If a model and key column have been setup on this type, then it
   * is said to have a related doctrine object
   *
   * @return bool
   */
  public function hasRelatedDoctrineObject()
  {
    return $this->getOption('model') && $this->getOption('key_column');
  }
}