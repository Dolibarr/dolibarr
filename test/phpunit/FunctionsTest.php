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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
*/

/**
 *      \file       test/phpunit/FunctionsTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/date.lib.php';

if (! defined('NOREQUIREUSER'))  define('NOREQUIREUSER','1');
if (! defined('NOREQUIREDB'))    define('NOREQUIREDB','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');
if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');
if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1');
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1'); // If there is no menu to show
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1'); // If we don't need to load the html.form.class.php
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined("NOLOGIN"))        define("NOLOGIN",'1');       // If this page is public (can be called outside logged session)


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class FunctionsTest extends PHPUnit_Framework_TestCase
{
    protected $savconf;
    protected $savuser;
    protected $savlangs;
    protected $savdb;

    /**
     * Constructor
     * We save global variables into local variables
     *
     * @return CoreTest
     */
    function FunctionsTest()
    {
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
        //$db->begin();	// This is to have all actions inside a transaction even if test launched without suite.

        print __METHOD__."\n";
    }
    public static function tearDownAfterClass()
    {
        global $conf,$user,$langs,$db;
        //$db->rollback();

        print __METHOD__."\n";
    }

	/**
	 * Init phpunit tests
	 *
	 * @return	void
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
	 * @return	void
	 */
    protected function tearDown()
    {
        print __METHOD__."\n";
    }


    /**
     * testDolHtmlCleanLastBr
     *
     * @return boolean
     */
    public function testDolHtmlCleanLastBr()
    {
        $input="A string<br>";
        $after=dol_htmlcleanlastbr($input);
        $this->assertEquals("A string",$after);
        $input="A string first<br>\nA string second<br>";
        $after=dol_htmlcleanlastbr($input);
        $this->assertEquals("A string first<br>\nA string second",$after);
        $input="A string\n<br type=\"_moz\" />\n";
        $after=dol_htmlcleanlastbr($input);
        $this->assertEquals("A string",$after);
        $input="A string\n<br><br />\n\n";
        $after=dol_htmlcleanlastbr($input);
        $this->assertEquals("A string",$after);

        return true;
    }

    /**
     * testDolHtmlEntitiesBr
     *
     * @return boolean
     */
    public function testDolHtmlEntitiesBr()
    {
        $input="A string\nwith a é, &, < and >.";   // Text not already HTML
        $after=dol_htmlentitiesbr($input,0);    // Add <br> before \n
        $this->assertEquals("A string<br>\nwith a &eacute;, &amp;, &lt; and &gt;.",$after);

        $input="A string\nwith a é, &, < and >.";   // Text not already HTML
        $after=dol_htmlentitiesbr($input,1);    // Replace \n with <br>
        $this->assertEquals("A string<br>with a &eacute;, &amp;, &lt; and &gt;.",$after);

        $input="A string<br>\nwith a é, &, < and >.";   // Text already HTML, so &,<,> should not be converted
        $after=dol_htmlentitiesbr($input);
        $this->assertEquals("A string<br>\nwith a &eacute;, &, < and >.",$after);

        $input="<li>\nA string with a é, &, < and >.</li>\nAnother string";   // Text already HTML, so &,<,> should not be converted
        $after=dol_htmlentitiesbr($input);
        $this->assertEquals("<li>\nA string with a &eacute;, &, < and >.</li>\nAnother string",$after);

        return true;
    }

    /**
     * testDolTextIsHtml
     *
     * @return void
     */
    public function testDolTextIsHtml()
    {
        // True
        $input='<html>xxx</html>';
        $after=dol_textishtml($input);
        $this->assertTrue($after);
        $input='<body>xxx</body>';
        $after=dol_textishtml($input);
        $this->assertTrue($after);
        $input='xxx <b>yyy</b> zzz';
        $after=dol_textishtml($input);
        $this->assertTrue($after);
        $input='xxx<br>';
        $after=dol_textishtml($input);
        $this->assertTrue($after);
        $input='text with <div>some div</div>';
        $after=dol_textishtml($input);
        $this->assertTrue($after);
        $input='text with HTML &nbsp; entities';
        $after=dol_textishtml($input);
        $this->assertTrue($after);

        // False
        $input='xxx < br>';
        $after=dol_textishtml($input);
        $this->assertFalse($after);
    }

    /**
     * testDolTextIsHtml
     *
     * @return void
     */
    public function testDolUtf8Check()
    {
        // True
        $result=utf8_check('azerty');
        $this->assertTrue($result);

        $file=dirname(__FILE__).'/textutf8.txt';
        $filecontent=file_get_contents($file);
        $result=utf8_check($filecontent);
        $this->assertTrue($result);

        $file=dirname(__FILE__).'/textiso.txt';
        $filecontent=file_get_contents($file);
        $result=utf8_check($filecontent);
        $this->assertFalse($result);
    }

    /**
     * testDolTrunc
     *
     * @return boolean
     */
    public function testDolTrunc()
    {
        // Default trunc (will add ... if truncation truncation or keep last char if only one char)
        $input="éeéeéeàa";
        $after=dol_trunc($input,3);
        $this->assertEquals("éeé...",$after);
        $after=dol_trunc($input,2);
        $this->assertEquals("ée...",$after);
        $input="éeé";
        $after=dol_trunc($input,3);
        $this->assertEquals("éeé",$after);
        $after=dol_trunc($input,2);
        $this->assertEquals("éeé",$after);
        $after=dol_trunc($input,1);
        $this->assertEquals("é...",$after);
        // Trunc with no ...
        $input="éeéeéeàa";
        $after=dol_trunc($input,3,'right','UTF-8',1);
        $this->assertEquals("éeé",$after);
        $after=dol_trunc($input,2,'right','UTF-8',1);
        $this->assertEquals("ée",$after);
        $input="éeé";
        $after=dol_trunc($input,3,'right','UTF-8',1);
        $this->assertEquals("éeé",$after);
        $after=dol_trunc($input,2,'right','UTF-8',1);
        $this->assertEquals("ée",$after);
        $after=dol_trunc($input,1,'right','UTF-8',1);
        $this->assertEquals("é",$after);
        $input="éeéeéeàa";
        $after=dol_trunc($input,4,'middle');
        $this->assertEquals("ée...àa",$after);

        return true;
    }

    /**
     * testDolMkTime
     *
     * @return	void
     */
    public function testDolMkTime()
    {
        $result=dol_mktime(25,0,0,1,1,1970,1,1);    // Error (25 hours)
        print __METHOD__." result=".$result."\n";
        $this->assertEquals('',$result);
        $result=dol_mktime(2,61,0,1,1,1970,1,1);    // Error (61 minutes)
        print __METHOD__." result=".$result."\n";
        $this->assertEquals('',$result);
        $result=dol_mktime(2,1,61,1,1,1970,1,1);    // Error (61 seconds)
        print __METHOD__." result=".$result."\n";
        $this->assertEquals('',$result);
        $result=dol_mktime(2,1,1,1,32,1970,1,1);    // Error (day 32)
        print __METHOD__." result=".$result."\n";
        $this->assertEquals('',$result);
        $result=dol_mktime(2,1,1,13,1,1970,1,1);    // Error (month 13)
        print __METHOD__." result=".$result."\n";
        $this->assertEquals('',$result);

        $result=dol_mktime(2,1,1,1,1,1970,1);    // 1970-01-01 02:01:01 in GMT area -> 7261
        print __METHOD__." result=".$result."\n";
        $this->assertEquals(7261,$result);

        $result=dol_mktime(2,0,0,1,1,1970,0);                // 1970-01-01 02:00:00 in local area Europe/Paris -> 3600 GMT
        print __METHOD__." result=".$result."\n";
        $tz=getServerTimeZoneInt('1970-01-01 02:00:00');    // +1 in Europe/Paris at this time (we are winter)
        $this->assertEquals(7200-($tz*3600),$result);        // Should be 7200 if we are at greenwich
    }

    /**
     * testDolNow
     *
     * @return	void
     */
    public function testDolNow()
    {
        $now=dol_now('gmt');
        $nowtzserver=dol_now('tzserver');
        print __METHOD__."getServerTimeZoneInt=".(getServerTimeZoneInt()*3600)."\n";
        $this->assertEquals(getServerTimeZoneInt()*3600,($nowtzserver-$now));
    }

    /**
     * testVerifCond
     *
     * @return	void
     */
    public function testVerifCond()
    {
        $verifcond=verifCond('1==1');
        $this->assertTrue($verifcond);

        $verifcond=verifCond('1==2');
        $this->assertFalse($verifcond);

        $verifcond=verifCond('$conf->facture->enabled');
        $this->assertTrue($verifcond);

        $verifcond=verifCond('$conf->moduledummy->enabled');
        $this->assertFalse($verifcond);

        $verifcond=verifCond('');
        $this->assertTrue($verifcond);
    }

}
?>