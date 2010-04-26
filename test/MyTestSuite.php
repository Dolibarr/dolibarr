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
global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__).'/../htdocs/master.inc.php';
require_once dirname(__FILE__).'/FactureTest.php';
require_once dirname(__FILE__).'/PropalTest.php';
require_once dirname(__FILE__).'/CommandeTest.php';

if (empty($user->id))
{
	print "Load permissions for admin user with login 'admin'\n";
	$user->fetch('admin');
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

        $suite->addTestSuite('FactureTest');
        $suite->addTestSuite('PropalTest');
        $suite->addTestSuite('CommandeTest');

        return $suite;
    }
}

?>