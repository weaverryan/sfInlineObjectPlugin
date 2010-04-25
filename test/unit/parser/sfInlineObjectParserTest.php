<?php

require dirname(__FILE__).'/../../bootstrap/unit.php';

$t = new lime_test(4);

$t->info('1 - Test some configuration setup');

$parser = new sfInlineObjectParser();
$t->is($parser->getCacheDriver(), null, 'The cache driver is not set automatically without config');

$cacheConfig = array(
  'enabled' => true,
  'class'   => 'sfFileCache',
  'options'  => array('cache_dir' => '/tmp'),
);
sfConfig::set('app_inline_object_cache', $cacheConfig);

$parser = new sfInlineObjectParser();
$t->is(get_class($parser->getCacheDriver()), 'sfFileCache', 'The cache driver is set automatically with config');

$cacheConfig['enabled'] = false;
sfConfig::set('app_inline_object_cache', $cacheConfig);

$parser = new sfInlineObjectParser();
$t->is($parser->getCacheDriver(), null, 'The cache driver is null if caching is not enabled');



$t->info('2 - Parse strings using simple inline objects');
require_once dirname(__FILE__).'/../../fixtures/project/lib/inline_object/InlineObjectPhoto.class.php';

$parser = new sfInlineObjectParser();
$parser->addType('photo', 'InlineObjectPhoto');
$result = $parser->parse('A [photo:flower width=100] flower');
$t->is($result, 'A <img src="/images/flower.jpg" width="100" /> flower', 'A simple InlineObject translates correctly');