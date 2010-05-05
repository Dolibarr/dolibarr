<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *      \file       test/MyTestSuite.php
 *		\ingroup    test
 *      \brief      This file is a test suite to run all unit tests
 *      \version    $Id$
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */
print "PHP Version: ".phpversion()."\n";
print "Memory: ". ini_get('memory_limit')."\n";

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__).'/../htdocs/master.inc.php';

if (empty($user->id))
{
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->getrights();
}


/**
 * Class for the All test suite
 */
class MyTestSuite
{
	public static function suite()
    {
		$suite = new PHPUnit_Framework_TestSuite('PHPUnit Framework');

		require_once dirname(__FILE__).'/CommonObjectTest.php';
        $suite->addTestSuite('CommonObjectTest');

        require_once dirname(__FILE__).'/AdherentTest.php';
        $suite->addTestSuite('AdherentTest');
		require_once dirname(__FILE__).'/CommandeTest.php';
        $suite->addTestSuite('CommandeTest');
		require_once dirname(__FILE__).'/ContratTest.php';
        $suite->addTestSuite('ContratTest');
        require_once dirname(__FILE__).'/FactureTest.php';
        $suite->addTestSuite('FactureTest');
		require_once dirname(__FILE__).'/PropalTest.php';
        $suite->addTestSuite('PropalTest');
		require_once dirname(__FILE__).'/UserTest.php';
        $suite->addTestSuite('UserTest');
		require_once dirname(__FILE__).'/UserGroupTest.php';
        $suite->addTestSuite('UserGroupTest');

        return $suite;
    }
}

?>