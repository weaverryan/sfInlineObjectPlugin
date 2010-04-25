<?php

class InlineObjectPhoto extends sfInlineObjectType
{
  public function render()
  {
    $attrs = InlineObjectToolkit::arrayToAttributes($this->getOptions());

    return sprintf('<img src="/images/%s.jpg"%s />', $this->getName(), $attrs);
  }
}