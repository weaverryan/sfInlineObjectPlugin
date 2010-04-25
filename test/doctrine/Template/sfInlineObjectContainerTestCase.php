<?php

/**
 * Doctrine_Template_Sortable_TestCase
 *
 * @package     Doctrine
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.2
 * @version     $Revision$
 */

/**
 * Unit test for the sfInlineObjectContainer Doctrine Template
 * 
 * @package     sfInlineObjectPlugin
 * @subpackage  test
 * @author      Ryan Weaver <ryan@thatsquality.com>
 */
class sfInlineObjectContainerTestCase extends Doctrine_UnitTestCase
{
  public function setUp()
  {
      parent::setUp();
  }

  public function prepareTables()
  {
      $this->tables[] = "InlineTestBlog";
      parent::prepareTables();
  }

  public function prepareData()
  { }

  public function testMyTest()
  {
      $this->assertEqual(1, 1);
  }
}

// Stub Doctrine_Record
class InlineTestBlog extends Doctrine_Record
{
  public function setTableDefinition()
  {
    $this->setTableName('inline_test_blog');
    $this->hasColumn('name', 'string', 50);
    $this->hasColumn('body', 'clob', null);
  }

  public function setUp()
  {
    parent::setUp();
    
    $inlineObjectTemplate = new sfInlineObjectContainer(array(
      'Products',
    ));
    $this->actAs($inlineObjectTemplate);
  }
}