<?php

require dirname(__FILE__).'/../../bootstrap/doctrine.php';

$t = new lime_test(2);
Doctrine_Core::getTable('Product')->createQuery('p')->delete()->execute();

// Instantiate what will essentially be our "stub".
$inlineProduct = new InlineObjectProduct('test-product');
$t->is($inlineProduct->getRelatedObject(), false, '->getRelatedObject() returns null if the object is not found');

$product = new Product();
$product->title = 'Test Product';
$product->slug = 'test-product';
$product->save();

$inlineProduct = new InlineObjectProduct('test-product');
$t->is($inlineProduct->getRelatedObject()->id, $product->id, '->getRelatedObject() returns the correct related object');