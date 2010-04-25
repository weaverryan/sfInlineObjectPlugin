<?php

class InlineObjectPhoto extends sfInlineObjectType
{
  public function render()
  {
    return image_tag($this->getName().'.jpg', $this->getOptions());
  }
}