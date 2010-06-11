<?php

require dirname(__FILE__).'/../../bootstrap/doctrine.php';

$t = new lime_test(2);

$t->info('1 - Test ->getRelatedObject()';)
  Doctrine_Core::getTable('Product')->createQuery('p')->delete()->execute();

  // Instantiate what will essentially be our "stub".
  $inlineProduct = new InlineObjectProduct('product', array(
    'model'      => 'Product',
    'key_column' => 'slug',
  ));
  $t->is($inlineProduct->getRelatedObject('test-product'), false, '->getRelatedObject(test-product) returns null if the object is not found');

  $product = new Product();
  $product->title = 'Test Product';
  $product->slug = 'test-product';
  $product->save();

  $t->is($inlineProduct->getRelatedObject('test-product')->id, $product->id, '->getRelatedObject() returns the correct related object');

