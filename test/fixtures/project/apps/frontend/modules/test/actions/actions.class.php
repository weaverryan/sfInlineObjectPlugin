<?php

// test actions class
class testActions extends sfActions
{
  public function preExecute()
  {
    $this->phrase = 'And then, something happened. I let go. Lost in oblivion. [photo:Dark] and silent and complete. I found freedom. Losing all hope was freedom.';
  }
  
  public function executeFromAction()
  {
    $parser = $this->getInlineObjectParser();
    $this->renderText($parser->parse($this->phrase));
    
    return sfView::NONE;
  }

  public function executeFromView()
  {
    $this->setLayout(false);
  }
}