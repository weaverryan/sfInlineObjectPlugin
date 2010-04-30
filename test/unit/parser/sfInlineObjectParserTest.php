<?php

require dirname(__FILE__).'/../../bootstrap/doctrine.php';

$t = new lime_test(23);
Doctrine_Core::getTable('Product')->createQuery('p')->delete()->execute();
Doctrine_Core::getTable('Blog')->createQuery('p')->delete()->execute();
Doctrine_Core::getTable('BlogProduct')->createQuery('p')->delete()->execute();

$t->info('1 - Test some configuration setup');

$t->info('  1.1 - Test the cache driver setup');
$parser = new sfInlineObjectParser();
$t->is($parser->getCacheDriver(), null, 'The cache driver is null by default');

$cache = new sfNoCache();
$parser->setCacheDriver($cache);
$t->is(get_class($parser->getCacheDriver()), 'sfNoCache', '->setCacheDriver() sets the driver properly');


$t->info('2 - Test the static bootstrap method');
require dirname(__FILE__).'/../../bootstrap/functional.php';

$parser = sfInlineObjectParser::createInstance();
$t->is(get_class($parser), 'sfInlineObjectTestParser', 'The class is sfInlineObjectTestParser');
$t->is(count($parser->getTypes()), 2, '->getTypes() begins with two types');
$t->is(get_class($parser->getCacheDriver()), 'sfFileCache', 'The cache is set correctly');


$cacheConfig = sfConfig::get('app_inline_object_cache');
$cacheConfig['enabled'] = false;
sfConfig::set('app_inline_object_cache', $cacheConfig);

$parser = sfInlineObjectParser::createInstance();
$t->is($parser->getCacheDriver(), null, 'The cache driver is null if caching is not enabled');



$t->info('3 - Parse a string using simple inline objects');

$parser = sfInlineObjectParser::createInstance();
$result = $parser->parse('A [photo:flower width=100] flower');
$t->is($result, 'A <img src="/images/flower.jpg" width="100" /> flower', 'A simple InlineObject translates correctly');

$result = $parser->parse('A [photo:"flower power" width=100] flower');
$t->is($result, 'A <img src="/images/flower power.jpg" width="100" /> flower', 'A simple InlineObject translates correctly');



$t->info('4 - Parse a string using doctrine inline objects, but with no related record');

$parser = sfInlineObjectParser::createInstance();

$result = $parser->parse('The price of "My Product": [product:my-product display=price].');
$t->is($result, 'The price of "My Product": .', 'The doctrine object was not found, so nothing is output');

$product = new Product();
$product->title = 'My Product';
$product->slug = 'my-product';
$product->price = '15.99';
$product->save();

$result = $parser->parse('The price of "My Product": [product:my-product display=price].');
$t->is($result, 'The price of "My Product": 15.99.', 'The doctrine object was found and used to output.');


$t->info('5 - Parse a string using doctrine inline objects with a related record');
$blog = new Blog();
$blog->title = 'Testing blog';
$blog->body = 'The price of "My Product": [product:my-product display=price].';
$blog->save();

$t->is(count($blog->Products), 0, 'Sanity check, the blog has no related Products');

$parser = sfInlineObjectParser::createInstance();
$parser->setDoctrineRecord($blog);
$result = $parser->parse($blog->body);
$blog->refreshRelated('Products');

$t->is($result, 'The price of "My Product": 15.99.', 'The doctrine object was found and used to output.');
$t->is(count($blog->Products), 1, 'The Blog now has one related Product entry');
$t->is($blog->Products[0]->id, $product->id, 'The related Product is the Product we embedded.');

$blog->body = 'The price of "[product:my-product]": [product:my-product display=price].';
$blog->save();
$blog->refreshRelated('Products');
$t->is(count($blog->Products), 0, 'Upon saving the blog post, it loses its relations to Product');

$result = $parser->parse($blog->body);
$blog->refreshRelated('Products');

$t->is($result, 'The price of "My Product": 15.99.', 'Both entries from the one Product were output');
$t->is(count($blog->Products), 1, 'The Blog still only has one relation, even though it was referenced twice');

$product2 = new Product();
$product2->title = 'Foo Product';
$product2->slug = 'foo-product';
$product2->price = '30.59';
$product2->save();

$result = $parser->parse('Showing info about "[product:foo-product]"');
$blog->refreshRelated('Products');

$t->is($result, 'Showing info about "Foo Product"', 'The second product outputs correctly');
$t->is(count($blog->Products), 2, 'The blog is now related to two Products');

$blog->body = 'The price of "[product:my-product]": [product:my-product display=price] and "[product:foo-product]": [product:foo-product display=price].';
$blog->save();
$result = $parser->parse($blog->body);
$t->is($result, 'The price of "My Product": 15.99 and "Foo Product": 30.59.', 'Both products where translated');


$t->info('6 - Test caching');
sfToolkit::clearDirectory('/tmp/content_filter');
$cache = new sfFileCache(array(
  'cache_dir' => '/tmp/inline_object',
));
$parser->setCacheDriver($cache);

$result = $parser->parse('Showing info about "[product:foo-product]"', 'foo_cache_key');
$t->is($result, 'Showing info about "Foo Product"', 'Processing takes place as expected.');

$result = $parser->parse('Showing price of "[product:foo-product]"', 'foo_cache_key');
$t->is($result, 'Showing info about "Foo Product"', 'The cached version is returned.');

$product2->title = 'Bar Product';
$product2->save();

$result = $parser->parse('Showing price of "[product:foo-product]"', 'foo_cache_key');
$t->is($result, 'Showing info about "Bar Product"', 'The text is cached, but the object remains dynamic');
