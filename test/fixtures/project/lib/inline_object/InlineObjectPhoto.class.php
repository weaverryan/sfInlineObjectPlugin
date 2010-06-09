<?php

class InlineObjectPhoto extends sfInlineObjectType
{
  public function render($name, $arguments)
  {
    $attrs = InlineObjectToolkit::arrayToAttributes($arguments);

    return sprintf('<img src="/images/%s.jpg"%s />', $name, $attrs);
  }
}