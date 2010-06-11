<?php

require dirname(__FILE__).'/../../bootstrap/doctrine.php';

$t = new lime_test(4);

$t->info('1 - Test ->getRelatedObject()');
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

$t->info('2 - Test the ->render() method');
  $appConfig = ProjectConfiguration::getApplicationConfiguration('frontend', 'test', true);
  sfContext::createInstance($appConfig);

  $type = new sfInlineObjectType('photo');

  $t->info('  2.1 - Rendering sfInlineObjectType without a partial option throws an exception');
  try
  {
    $type->render('name', array());
    $t->fail('The exception was not thrown');
  }
  catch (sfException $e)
  {
    $t->pass('The exception was thrown: '.$e->getMessage());
  }

  $type->setOption('partial', 'test/templateRender');
  $type->setOption('test_option', 'option_val');
  $rendered = $type->render('test_name', array('arg1' => 'val1'));

  $expected = '<p class="option">option_val</p>
<p class="name">test_name</p>
<p class="argument">val1</p>';
  $t->is($rendered, $expected, 'Rendering via the partial method works correctly');