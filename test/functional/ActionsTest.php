<?php
require dirname(__FILE__).'/../bootstrap/functional.php';

$rendered = 'And then, something happened. I let go. Lost in oblivion. <img src="/images/Dark.jpg" /> and silent and complete. I found freedom. Losing all hope was freedom.';

$browser = new sfTestFunctional(new sfBrowser());

$browser->info('1 - Perform some sanity checks on rendering')
  
  ->info('  1.1 - Use the parser entirely in an action')
  ->get('/test/action')
  
  ->with('response')->begin()
    ->isStatusCode(200)
  ->end()
;
$browser->test()->is($browser->getResponse()->getContent(), $rendered, 'The output is what we expect');

$browser
  ->info('  1.2 - Use the parser entirely in a template')
  ->get('/test/view')
  
  ->with('response')->begin()
    ->isStatusCode(200)
  ->end()
;
$browser->test()->is($browser->getResponse()->getContent(), $rendered, 'The output is what we expect');