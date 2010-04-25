<?php

// Testing class that refers to a doctrine object
class InlineObjectProduct extends sfInlineObjectDoctrineType
{
  public function getModel()
  {
    return 'Product';
  }
  
  public function getKeyColumn()
  {
    return 'slug';
  }
  
  public function render()
  {
    $product = $this->getRelatedObject();
    
    if (!$product)
    {
      return '';
    }

    if ($field = $this->getOption('display'))
    {
      return $product->get($field);
    }
    else
    {
      return (string) $product;
    }
  }
}