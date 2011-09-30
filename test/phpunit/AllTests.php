<?php
/* Copyright (C) 2010 Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2011 Regis Houssin			<regis@dolibarr.fr>
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
 *      \file       test/phpunit/AllTest.php
 *		\ingroup    test
 *      \brief      This file is a test suite to run all unit tests
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */
print "PHP Version: ".phpversion()."\n";
print "Memory: ". ini_get('memory_limit')."\n";

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';

if (empty($user->id))
{
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->getrights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS=1;


/**
 * Class for the All test suite
 */
class AllTests
{
	public static function suite()
    {
		$suite = new PHPUnit_Framework_TestSuite('PHPUnit Framework');

        //require_once dirname(__FILE__).'/CoreTest.php';
        //$suite->addTestSuite('CoreTest');
		require_once dirname(__FILE__).'/DateLibTest.php';
		$suite->addTestSuite('DateLibTest');
        require_once dirname(__FILE__).'/FunctionsTest.php';
        $suite->addTestSuite('FunctionsTest');

        require_once dirname(__FILE__).'/SecurityTest.php';
        $suite->addTestSuite('SecurityTest');

        require_once dirname(__FILE__).'/BuildDocTest.php';
        $suite->addTestSuite('BuildDocTest');
        require_once dirname(__FILE__).'/CMailFileTest.php';
        $suite->addTestSuite('CMailFileTest');

        require_once dirname(__FILE__).'/CommonObjectTest.php';
        $suite->addTestSuite('CommonObjectTest');

        require_once dirname(__FILE__).'/SocieteTest.php';
        $suite->addTestSuite('SocieteTest');
        require_once dirname(__FILE__).'/AdherentTest.php';
        $suite->addTestSuite('AdherentTest');

        require_once dirname(__FILE__).'/DiscountTest.php';
        $suite->addTestSuite('DiscountTest');

        require_once dirname(__FILE__).'/ProductTest.php';
        $suite->addTestSuite('ProductTest');

        require_once dirname(__FILE__).'/CommandeTest.php';
        $suite->addTestSuite('CommandeTest');
		require_once dirname(__FILE__).'/CommandeFournisseurTest.php';
        $suite->addTestSuite('CommandeFournisseurTest');
        require_once dirname(__FILE__).'/ContratTest.php';
        $suite->addTestSuite('ContratTest');
        require_once dirname(__FILE__).'/FactureTest.php';
        $suite->addTestSuite('FactureTest');    // This one covers also triggers
        require_once dirname(__FILE__).'/FactureFournisseurTest.php';
        $suite->addTestSuite('FactureFournisseurTest');
        require_once dirname(__FILE__).'/PropalTest.php';
        $suite->addTestSuite('PropalTest');
		require_once dirname(__FILE__).'/UserTest.php';
        $suite->addTestSuite('UserTest');
		require_once dirname(__FILE__).'/UserGroupTest.php';
        $suite->addTestSuite('UserGroupTest');
		require_once dirname(__FILE__).'/CompanyBankAccountTest.php';
        $suite->addTestSuite('CompanyBankAccountTest');
        require_once dirname(__FILE__).'/ChargeSocialesTest.php';
        $suite->addTestSuite('ChargeSocialesTest');

        require_once dirname(__FILE__).'/CategorieTest.php';
        $suite->addTestSuite('CategorieTest');

        require_once dirname(__FILE__).'/WebservicesTest.php';
        $suite->addTestSuite('WebservicesTest');
        require_once dirname(__FILE__).'/ExportTest.php';
        $suite->addTestSuite('ExportTest');
        require_once dirname(__FILE__).'/ImportTest.php';
        $suite->addTestSuite('ImportTest');

        require_once dirname(__FILE__).'/ModulesTest.php';  // At end because it's the longer
        $suite->addTestSuite('ModulesTest');

        return $suite;
    }
}

?>
