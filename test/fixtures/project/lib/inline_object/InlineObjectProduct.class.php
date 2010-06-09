<?php

// Testing class that refers to a doctrine object
class InlineObjectProduct extends sfInlineObjectType
{
  public function render($name, $arguments)
  {
    $product = $this->getRelatedObject($name);
    
    if (!$product)
    {
      return '';
    }

    $field = isset($arguments['display']) ? $arguments['display'] : false;

    if ($field)
    {
      return $product->get($field);
    }
    else
    {
      return (string) $product;
    }
  }
}