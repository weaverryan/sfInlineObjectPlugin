<?php
include(dirname(__FILE__).'/unit.php');
new sfDatabaseManager(ProjectConfiguration::getApplicationConfiguration('frontend', 'test', true));