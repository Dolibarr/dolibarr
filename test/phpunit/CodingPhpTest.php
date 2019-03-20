<?php
/* Copyright (C) 2013 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       test/phpunit/SqlTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/security.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/security2.lib.php';

if (! defined('NOREQUIREUSER'))  define('NOREQUIREUSER', '1');
if (! defined('NOREQUIREDB'))    define('NOREQUIREDB', '1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC', '1');
if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN', '1');
if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK', '1');
if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1');
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1'); // If there is no menu to show
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1'); // If we don't need to load the html.form.class.php
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');
if (! defined("NOLOGIN"))        define("NOLOGIN", '1');       // If this page is public (can be called outside logged session)

if (empty($user->id))
{
    print "Load permissions for admin user nb 1\n";
    $user->fetch(1);
    $user->getrights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS=1;


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class CodingPhpTest extends PHPUnit_Framework_TestCase
{
    protected $savconf;
    protected $savuser;
    protected $savlangs;
    protected $savdb;

    /**
     * Constructor
     * We save global variables into local variables
     *
     * @return SecurityTest
     */
    public function __construct()
    {
        parent::__construct();

        //$this->sharedFixture
        global $conf,$user,$langs,$db;
        $this->savconf=$conf;
        $this->savuser=$user;
        $this->savlangs=$langs;
        $this->savdb=$db;

        print __METHOD__." db->type=".$db->type." user->id=".$user->id;
        //print " - db ".$db->db;
        print "\n";
    }

    // Static methods
    public static function setUpBeforeClass()
    {
        global $conf,$user,$langs,$db;
        $db->begin(); // This is to have all actions inside a transaction even if test launched without suite.

        print __METHOD__."\n";
    }

    // tear down after class
    public static function tearDownAfterClass()
    {
        global $conf,$user,$langs,$db;
        $db->rollback();

        print __METHOD__."\n";
    }

    /**
     * Init phpunit tests
     *
     * @return  void
     */
    protected function setUp()
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        print __METHOD__."\n";
    }

    /**
     * End phpunit tests
     *
     * @return  void
     */
    protected function tearDown()
    {
        print __METHOD__."\n";
    }

    /**
     * testSql
     *
     * @return string
     */
    public function testPHP()
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
        $filesarray = dol_dir_list(DOL_DOCUMENT_ROOT, 'files', 1, '\.php', null, 'fullname');

        foreach($filesarray as $key => $file)
        {
            if (preg_match('/\/htdocs\/includes\//', $file['fullname'])) continue;
            if (preg_match('/\/htdocs\/custom\//', $file['fullname'])) continue;
            if (preg_match('/\/htdocs\/dolimed/', $file['fullname'])) continue;
            if (preg_match('/\/htdocs\/nltechno/', $file['fullname'])) continue;
            if (preg_match('/\/htdocs\/teclib/', $file['fullname'])) continue;

            print 'Check php file '.$file['fullname']."\n";
            $filecontent=file_get_contents($file['fullname']);


            $ok=true;
            $matches=array();
            // Check string   ='".$this->xxx   with xxx that is not 'escape'. It means we forget a db->escape when forging sql request.
            preg_match_all('/(..)\s*\.\s*\$this->db->idate\(/', $filecontent, $matches, PREG_SET_ORDER);
            foreach($matches as $key => $val)
            {
                if ($val[1] != '\'"' && $val[1] != '\'\'')
                {
                    $ok=false;
                    break;
                }
                //if ($reg[0] != 'db') $ok=false;
            }
            //print __METHOD__." Result for checking we don't have non escaped string in sql requests for file ".$file."\n";
            $this->assertTrue($ok, 'Found a $this->db->idate to forge a sql request without quotes around this date field '.$file['fullname'].' :: '.$val[0]);
            //exit;


            $ok=true;
            $matches=array();
            // Check string   ='".$this->xxx   with xxx that is not 'escape'. It means we forget a db->escape when forging sql request.
            preg_match_all('/(=|sql.+)\s*\'"\s*\.\s*\$this->(....)/', $filecontent, $matches, PREG_SET_ORDER);
            foreach($matches as $key => $val)
            {
                if ($val[2] != 'db->' && $val[2] != 'esca')
                {
                    $ok=false;
                    break;
                }
                //if ($reg[0] != 'db') $ok=false;
            }
            //print __METHOD__." Result for checking we don't have non escaped string in sql requests for file ".$file."\n";
            $this->assertTrue($ok, 'Found non escaped string in building of a sql request '.$file['fullname'].' ('.$val[0].'). Bad.');
            //exit;


            // Test that output of $_SERVER\[\'QUERY_STRING\'\] is escaped.
            $ok=true;
            $matches=array();
            // Check string   ='".$this->xxx   with xxx that is not 'escape'. It means we forget a db->escape when forging sql request.
            preg_match_all('/(..............)\$_SERVER\[\'QUERY_STRING\'\]/', $filecontent, $matches, PREG_SET_ORDER);
            foreach($matches as $key => $val)
            {
                if ($val[1] != 'scape_htmltag(' && $val[1] != 'ing_nohtmltag(' && $val[1] != 'dol_escape_js(')
                {
                    $ok=false;
                    break;
                }
            }
            $this->assertTrue($ok, 'Found a $_SERVER[\'QUERY_STRING\'] without dol_escape_htmltag neither dol_string_nohtmltag around it, in file '.$file['fullname'].' ('.$val[1].'$_SERVER[\'QUERY_STRING\']). Bad.');


            // Test that first param of print_liste_field_titre is a translation key and not the translated value
            $ok=true;
            $matches=array();
            // Check string   ='".$this->xxx   with xxx that is not 'escape'. It means we forget a db->escape when forging sql request.
            preg_match_all('/print_liste_field_titre\(\$langs/', $filecontent, $matches, PREG_SET_ORDER);
            foreach($matches as $key => $val)
            {
                   $ok=false;
                   break;
            }
            $this->assertTrue($ok, 'Found a use of print_liste_field_titre with first parameter that is a translated value instead of just the translation key in file '.$file['fullname'].'. Bad.');


            // Test we don't have <br />
            $ok=true;
            $matches=array();
            // Check string   ='".$this->xxx   with xxx that is not 'escape'. It means we forget a db->escape when forging sql request.
            preg_match_all('/<br \/>/', $filecontent, $matches, PREG_SET_ORDER);
            foreach($matches as $key => $val)
            {
                if ($file['name'] != 'functions.lib.php')
                {
                    $ok=false;
                    break;
                }
            }
            $this->assertTrue($ok, 'Found a tag <br /> that is for xml in file '.$file['fullname'].' You may use <br> instead.');
        }

        return;
    }
}
