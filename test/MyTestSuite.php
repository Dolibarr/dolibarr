<?php
global $conf,$user,$langs,$db;
require_once 'PHPUnit/Framework.php';

require_once dirname(__FILE__).'/../htdocs/master.inc.php';
require_once dirname(__FILE__).'/FactureTest.php';
require_once dirname(__FILE__).'/PropalTest.php';
require_once dirname(__FILE__).'/CommandeTest.php';

print "Load permissions for admin user with login 'admin'\n";
$user->fetch('admin');
$user->getrights();


/**
 * Class for the All test suite
 */
class MyTestSuite
{
	public static function suite()
    {
		$suite = new PHPUnit_Framework_TestSuite('PHPUnit Framework');

        $suite->addTestSuite('FactureTest');
        $suite->addTestSuite('PropalTest');
        $suite->addTestSuite('CommandeTest');

        return $suite;
    }
}

?>