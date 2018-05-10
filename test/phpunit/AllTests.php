<?php
/* Copyright (C) 2010-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2011-2012  Regis Houssin       <regis.houssin@capnetworks.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *      \file       test/phpunit/AllTest.php
 *      \ingroup    test
 *      \brief      This file is a test suite to run all unit tests
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */
print "PHP Version: ".phpversion()."\n";
print "Memory: ". ini_get('memory_limit')."\n";

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql'); // This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
print 'DOL_MAIN_URL_ROOT='.DOL_MAIN_URL_ROOT."\n";  // constant will be used by other tests


if ($langs->defaultlang != 'en_US')
{
    print "Error: Default language for company to run tests must be set to en_US or auto. Current is ".$langs->defaultlang."\n";
    exit(1);
}
if (empty($conf->adherent->enabled))
{
	print "Error: Module member must be enabled to have significant results.\n";
	exit(1);
}
if (! empty($conf->ldap->enabled))
{
    print "Error: LDAP module should not be enabled.\n";
    exit(1);
}
if (! empty($conf->google->enabled))
{
    print "Warning: Google module should not be enabled.\n";
}
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
    /**
     * Function suite to make all PHPUnit tests
     *
     * @return	void
     */
    public static function suite()
    {

        $suite = new PHPUnit_Framework_TestSuite('PHPUnit Framework');

        //require_once dirname(__FILE__).'/CoreTest.php';
        //$suite->addTestSuite('CoreTest');
        require_once dirname(__FILE__).'/AdminLibTest.php';
        $suite->addTestSuite('AdminLibTest');
        require_once dirname(__FILE__).'/CompanyLibTest.php';
        $suite->addTestSuite('CompanyLibTest');
        require_once dirname(__FILE__).'/DateLibTest.php';
        $suite->addTestSuite('DateLibTest');
        require_once dirname(__FILE__).'/UtilsTest.php';
        $suite->addTestSuite('UtilsTest');
        //require_once dirname(__FILE__).'/DateLibTzFranceTest.php';
        //$suite->addTestSuite('DateLibTzFranceTest');
        require_once dirname(__FILE__).'/MarginsLibTest.php';
        $suite->addTestSuite('MarginsLibTest');
        require_once dirname(__FILE__).'/FilesLibTest.php';
        $suite->addTestSuite('FilesLibTest');
        require_once dirname(__FILE__).'/GetUrlLibTest.php';
        $suite->addTestSuite('GetUrlLibTest');
        require_once dirname(__FILE__).'/JsonLibTest.php';
        $suite->addTestSuite('JsonLibTest');
        require_once dirname(__FILE__).'/ImagesLibTest.php';
        $suite->addTestSuite('ImagesLibTest');
        require_once dirname(__FILE__).'/FunctionsLibTest.php';
        $suite->addTestSuite('FunctionsLibTest');
        require_once dirname(__FILE__).'/Functions2LibTest.php';
        $suite->addTestSuite('Functions2LibTest');
        require_once dirname(__FILE__).'/XCalLibTest.php';
        $suite->addTestSuite('XCalLibTest');

        // Rules into source files content
        require_once dirname(__FILE__).'/LangTest.php';
        $suite->addTestSuite('LangTest');
        require_once dirname(__FILE__).'/CodingSqlTest.php';
        $suite->addTestSuite('CodingSqlTest');
        require_once dirname(__FILE__).'/CodingPhpTest.php';
        $suite->addTestSuite('CodingPhpTest');

        require_once dirname(__FILE__).'/SecurityTest.php';
        $suite->addTestSuite('SecurityTest');

        require_once dirname(__FILE__).'/UserTest.php';
        $suite->addTestSuite('UserTest');
        require_once dirname(__FILE__).'/UserGroupTest.php';
        $suite->addTestSuite('UserGroupTest');

        require_once dirname(__FILE__).'/NumberingModulesTest.php';
        $suite->addTestSuite('NumberingModulesTest');
        require_once dirname(__FILE__).'/PgsqlTest.php';
        $suite->addTestSuite('PgsqlTest');
        require_once dirname(__FILE__).'/PdfDocTest.php';
        $suite->addTestSuite('PdfDocTest');
        require_once dirname(__FILE__).'/BuildDocTest.php';
        $suite->addTestSuite('BuildDocTest');
        require_once dirname(__FILE__).'/CMailFileTest.php';
        $suite->addTestSuite('CMailFileTest');

        require_once dirname(__FILE__).'/CommonObjectTest.php';
        $suite->addTestSuite('CommonObjectTest');

        require_once dirname(__FILE__).'/SocieteTest.php';
        $suite->addTestSuite('SocieteTest');
        require_once dirname(__FILE__).'/ContactTest.php';
        $suite->addTestSuite('ContactTest');
        require_once dirname(__FILE__).'/AdherentTest.php';
        $suite->addTestSuite('AdherentTest');

        require_once dirname(__FILE__).'/ProductTest.php';
        $suite->addTestSuite('ProductTest');

        require_once dirname(__FILE__).'/PricesTest.php';
        $suite->addTestSuite('PricesTest');
        require_once dirname(__FILE__).'/DiscountTest.php';
        $suite->addTestSuite('DiscountTest');

        require_once dirname(__FILE__).'/ContratTest.php';
        $suite->addTestSuite('ContratTest');

        require_once dirname(__FILE__).'/FichinterTest.php';
        $suite->addTestSuite('FichinterTest');
        require_once dirname(__FILE__).'/TicketsupTest.php';
        $suite->addTestSuite('TicketsupTest');

        require_once dirname(__FILE__).'/PropalTest.php';
        $suite->addTestSuite('PropalTest');

        require_once dirname(__FILE__).'/SupplierProposalTest.php';
        $suite->addTestSuite('SupplierProposalTest');

        require_once dirname(__FILE__).'/CommandeTest.php';
        $suite->addTestSuite('CommandeTest');

        require_once dirname(__FILE__).'/CommandeFournisseurTest.php';
        $suite->addTestSuite('CommandeFournisseurTest');

        require_once dirname(__FILE__).'/FactureTest.php';
        $suite->addTestSuite('FactureTest');
        require_once dirname(__FILE__).'/FactureRecTest.php';
        $suite->addTestSuite('FactureRecTest');
        require_once dirname(__FILE__).'/FactureTestRounding.php';
        $suite->addTestSuite('FactureTestRounding');
        require_once dirname(__FILE__).'/FactureFournisseurTest.php';
        $suite->addTestSuite('FactureFournisseurTest');

        require_once dirname(__FILE__).'/BankAccountTest.php';
        $suite->addTestSuite('BankAccountTest');
        require_once dirname(__FILE__).'/CompanyBankAccountTest.php';
        $suite->addTestSuite('CompanyBankAccountTest');
        require_once dirname(__FILE__).'/BonPrelevementTest.php';
        $suite->addTestSuite('BonPrelevementTest');

        require_once dirname(__FILE__).'/ChargeSocialesTest.php';
        $suite->addTestSuite('ChargeSocialesTest');
        require_once dirname(__FILE__).'/HolidayTest.php';
        $suite->addTestSuite('HolidayTest');
        require_once dirname(__FILE__).'/ExpenseReportTest.php';
        $suite->addTestSuite('ExpenseReportTest');

        require_once dirname(__FILE__).'/EntrepotTest.php';
        $suite->addTestSuite('EntrepotTest');
        require_once dirname(__FILE__).'/MouvementStockTest.php';
        $suite->addTestSuite('MouvementStockTest');

        require_once dirname(__FILE__).'/CategorieTest.php';
        $suite->addTestSuite('CategorieTest');

        require_once dirname(__FILE__).'/RestAPIUserTest.php';
        $suite->addTestSuite('RestAPIUserTest');

        // Test only with php7.2 or less
        //if ((float) phpversion() < 7.3)
        //{
        	require_once dirname(__FILE__).'/WebservicesProductsTest.php';
	        $suite->addTestSuite('WebservicesProductsTest');
	        require_once dirname(__FILE__).'/WebservicesInvoicesTest.php';
	        $suite->addTestSuite('WebservicesInvoicesTest');
	        require_once dirname(__FILE__).'/WebservicesOrdersTest.php';
	        $suite->addTestSuite('WebservicesOrdersTest');
	        require_once dirname(__FILE__).'/WebservicesOtherTest.php';
	        $suite->addTestSuite('WebservicesOtherTest');
	        require_once dirname(__FILE__).'/WebservicesThirdpartyTest.php';
	        $suite->addTestSuite('WebservicesThirdpartyTest');
	        require_once dirname(__FILE__).'/WebservicesUserTest.php';
	        $suite->addTestSuite('WebservicesUserTest');
        //}

        require_once dirname(__FILE__).'/ExportTest.php';
        $suite->addTestSuite('ExportTest');
        require_once dirname(__FILE__).'/ImportTest.php';
        $suite->addTestSuite('ImportTest');

        require_once dirname(__FILE__).'/ScriptsTest.php';
        $suite->addTestSuite('ScriptsTest');

        require_once dirname(__FILE__).'/FormAdminTest.php';
        $suite->addTestSuite('FormAdminTest');

        require_once dirname(__FILE__).'/ModulesTest.php';  // At end because it's the longer
        $suite->addTestSuite('ModulesTest');


        // GUI
        require_once dirname(__FILE__).'/FormAdminTest.php';
        $suite->addTestSuite('FormAdminTest');


        return $suite;
    }
}

