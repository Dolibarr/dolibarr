<?php
/* Copyright (C) 2010-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
    function __construct()
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
    * @return void
    */
    public function testGetBrowserVersion()
    {
        $_SERVER['HTTP_USER_AGENT']='Mozilla/4.0 (compatible; MSIE 5.0; Windows 98; DigExt; KITV4 Wanadoo; KITV5 Wanadoo)';    // MSIE 5.0
        $tmp=getBrowserInfo();
        $this->assertEquals('ie',$tmp['browsername']);
        $this->assertEquals('5.0',$tmp['browserversion']);
        $_SERVER['HTTP_USER_AGENT']='Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.5a) Gecko/20030728 Mozilla Firefox/0.9.1';    // Firefox 0.9.1
        $tmp=getBrowserInfo();
        $this->assertEquals('firefox',$tmp['browsername']);
        $this->assertEquals('0.9.1',$tmp['browserversion']);
        $_SERVER['HTTP_USER_AGENT']='Mozilla/3.0 (Windows 98; U) Opera 6.03  [en]';
        $tmp=getBrowserInfo();
        $this->assertEquals('opera',$tmp['browsername']);
        $this->assertEquals('6.03',$tmp['browserversion']);
        $_SERVER['HTTP_USER_AGENT']='Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/535.21 (KHTML, like Gecko) Chrome/19.0.1042.0 Safari/535.21';
        $tmp=getBrowserInfo();
        $this->assertEquals('chrome',$tmp['browsername']);
        $this->assertEquals('19.0.1042.0',$tmp['browserversion']);
        $_SERVER['HTTP_USER_AGENT']='chrome (Mozilla/5.0 (X11; Linux i686) AppleWebKit/535.11 (KHTML, like Gecko) Chrome/17.0.963.56 Safari/535.11)';
        $tmp=getBrowserInfo();
        $this->assertEquals('chrome',$tmp['browsername']);
        $this->assertEquals('17.0.963.56',$tmp['browserversion']);
        $_SERVER['HTTP_USER_AGENT']='Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_8; de-at) AppleWebKit/533.21.1 (KHTML, like Gecko) Version/5.0.5 Safari/533.21.1';
        $tmp=getBrowserInfo();
        $this->assertEquals('safari',$tmp['browsername']);
        $this->assertEquals('533.21.1',$tmp['browserversion']);
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
     * testDolNbOfLinesBis
     *
     * @return boolean
     */
    public function testDolNbOfLinesBis()
    {
        // This is not a html string so nb of lines depends on \n
        $input="A string\nwith a é, &, < and > and bold tag.\nThird line";
        $after=dol_nboflines_bis($input,0);
        $this->assertEquals($after,3);

        // This is a html string so nb of lines depends on <br>
        $input="A string\nwith a é, &, < and > and <b>bold</b> tag.\nThird line";
        $after=dol_nboflines_bis($input,0);
        $this->assertEquals($after,1);

        // This is a html string so nb of lines depends on <br>
        $input="A string<br>with a é, &, < and > and <b>bold</b> tag.<br>Third line";
        $after=dol_nboflines_bis($input,0);
        $this->assertEquals($after,3);

        return true;
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

        $result=dol_mktime(2,0,0,1,1,1970,0);                // 1970-01-01 02:00:00 = 7200 in local area Europe/Paris = 3600 GMT
        print __METHOD__." result=".$result."\n";
        $tz=getServerTimeZoneInt('winter');                  // +1 in Europe/Paris at this time (this time is winter)
        $this->assertEquals(7200-($tz*3600),$result);        // Should be 7200 if we are at greenwich winter
    }


    /**
     * testDolEscapeJs
     *
     * @return	void
     */
    public function testDolEscapeJs()
    {
        $input="x&<b>#</b>,\"'";    // " will be converted into '
        $result=dol_escape_js($input);
        $this->assertEquals("x&<b>#<\/b>,\'\'",$result);
    }


    /**
    * testDolEscapeHtmlTag
    *
    * @return	void
    */
    public function testDolEscapeHtmlTag()
    {
        $input='x&<b>#</b>,"';    // & and " are converted into html entities, <b> are removed
        $result=dol_escape_htmltag($input);
        $this->assertEquals('x&amp;#,&quot;',$result);

        $input='x&<b>#</b>,"';    // & and " are converted into html entities, <b> are not removed
        $result=dol_escape_htmltag($input,1);
        $this->assertEquals('x&amp;&lt;b&gt;#&lt;/b&gt;,&quot;',$result);
    }


    /**
     * testDolFormatAddress
     *
     * @return	void
     */
    public function testDolFormatAddress()
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$object=new Societe($db);
		$object->initAsSpecimen();

		$object->country_code='FR';
    	$address=dol_format_address($object);
    	$this->assertEquals("21 jump street\n99999 MyTown",$address);

		$object->country_code='GB';
    	$address=dol_format_address($object);
    	$this->assertEquals("21 jump street\nMyTown, MyState\n99999",$address);

		$object->country_code='US';
    	$address=dol_format_address($object);
    	$this->assertEquals("21 jump street\nMyTown, MyState, 99999",$address);

		$object->country_code='AU';
    	$address=dol_format_address($object);
    	$this->assertEquals("21 jump street\nMyTown, MyState, 99999",$address);
    }

    /**
     * testImgPicto
     *
     * @return	void
     */
    public function testImgPicto()
    {
        $s=img_picto('alt','user');
        print __METHOD__." s=".$s."\n";
        $this->assertContains('theme',$s,'testImgPicto1');

    	$s=img_picto('alt','img.png','style="float: right"',0);
        print __METHOD__." s=".$s."\n";
        $this->assertContains('theme',$s,'testImgPicto2');
        $this->assertContains('style="float: right"',$s,'testImgPicto2');

        $s=img_picto('alt','/fullpath/img.png','',1);
        print __METHOD__." s=".$s."\n";
        $this->assertEquals($s,'<img src="/fullpath/img.png" border="0" alt="alt" title="alt">','testImgPicto3');

        $s=img_picto('alt','/fullpath/img.png','',true);
        print __METHOD__." s=".$s."\n";
        $this->assertEquals($s,'<img src="/fullpath/img.png" border="0" alt="alt" title="alt">','testImgPicto3');
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
        print __METHOD__." getServerTimeZoneInt=".(getServerTimeZoneInt('now')*3600)."\n";
        $this->assertEquals(getServerTimeZoneInt('now')*3600,($nowtzserver-$now));
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

    /**
     * testGetDefaultTva
     *
     * @return	void
     */
    public function testGetDefaultTva()
    {
        global $conf,$user,$langs,$db;
        $this->savconf=$conf;
        $this->savuser=$user;
        $this->savlangs=$langs;
        $this->savdb=$db;

        $companyfrnovat=new Societe($db);
        $companyfrnovat->country_code='FR';
        $companyfrnovat->tva_assuj=0;

        $companyfr=new Societe($db);
        $companyfr->country_code='FR';
        $companyfr->tva_assuj=1;

        $companymc=new Societe($db);
        $companymc->country_code='MC';
        $companymc->tva_assuj=1;

        $companyit=new Societe($db);
        $companyit->country_code='IT';
        $companyit->tva_assuj=1;
        $companyit->tva_intra='IT99999';

        $notcompanyit=new Societe($db);
        $notcompanyit->country_code='IT';
        $notcompanyit->tva_assuj=1;
        $notcompanyit->tva_intra='';
        $notcompanyit->typent_code='TE_PRIVATE';

        $companyus=new Societe($db);
        $companyus->country_code='US';
        $companyus->tva_assuj=1;
        $companyus->tva_intra='';

        // Test RULE 1-2
        $vat=get_default_tva($companyfrnovat,$companymc,0);
        $this->assertEquals(0,$vat);

        // Test RULE 3 (FR-FR)
        $vat=get_default_tva($companyfr,$companyfr,0);
        $this->assertEquals(19.6,$vat);

        // Test RULE 3 (FR-MC)
        $vat=get_default_tva($companyfr,$companymc,0);
        $this->assertEquals(19.6,$vat);

        // Test RULE 4 (FR-IT)
        $vat=get_default_tva($companyfr,$companyit,0);
        $this->assertEquals(0,$vat);

        // Test RULE 5 (FR-IT)
        $vat=get_default_tva($companyfr,$notcompanyit,0);
        $this->assertEquals(19.6,$vat);

        // Test RULE 6 (FR-IT)
        // Not tested

        // Test RULE 7 (FR-US)
        $vat=get_default_tva($companyfr,$companyus,0);
        $this->assertEquals(0,$vat);
    }

    /**
     * testGetDefaultTva
     *
     * @return	void
     */
    public function testGetDefaultLocalTax()
    {
    	global $conf,$user,$langs,$db;
    	$this->savconf=$conf;
    	$this->savuser=$user;
    	$this->savlangs=$langs;
    	$this->savdb=$db;

    	$companyfrnovat=new Societe($db);
    	$companyfrnovat->country_code='FR';
    	$companyfrnovat->tva_assuj=0;
    	$companyfrnovat->localtax1_assuj=0;
    	$companyfrnovat->localtax2_assuj=0;

    	$companyes=new Societe($db);
    	$companyes->country_code='ES';
    	$companyes->tva_assuj=1;
    	$companyes->localtax1_assuj=1;
    	$companyes->localtax2_assuj=1;

    	$companymc=new Societe($db);
    	$companymc->country_code='MC';
    	$companymc->tva_assuj=1;
    	$companymc->localtax1_assuj=0;
    	$companymc->localtax2_assuj=0;

    	$companyit=new Societe($db);
    	$companyit->country_code='IT';
    	$companyit->tva_assuj=1;
    	$companyit->tva_intra='IT99999';
    	$companyit->localtax1_assuj=0;
    	$companyit->localtax2_assuj=0;

    	$notcompanyit=new Societe($db);
    	$notcompanyit->country_code='IT';
    	$notcompanyit->tva_assuj=1;
    	$notcompanyit->tva_intra='';
    	$notcompanyit->typent_code='TE_PRIVATE';
    	$notcompanyit->localtax1_assuj=0;
    	$notcompanyit->localtax2_assuj=0;

    	$companyus=new Societe($db);
    	$companyus->country_code='US';
    	$companyus->tva_assuj=1;
    	$companyus->tva_intra='';
    	$companyus->localtax1_assuj=0;
    	$companyus->localtax2_assuj=0;

    	// Test RULE FR-MC
    	$vat1=get_default_localtax($companyfrnovat,$companymc,1,0);
    	$vat2=get_default_localtax($companyfrnovat,$companymc,2,0);
    	$this->assertEquals(0,$vat1);
    	$this->assertEquals(0,$vat2);

    	// Test RULE ES-ES
    	$vat1=get_default_localtax($companyes,$companyes,1,0);
    	$vat2=get_default_localtax($companyes,$companyes,2,0);
    	$this->assertEquals(5.2,$vat1);
    	$this->assertEquals(-21,$vat2);

    	// Test RULE ES-IT
    	$vat1=get_default_localtax($companyes,$companyit,1,0);
    	$vat2=get_default_localtax($companyes,$companyit,2,0);
    	$this->assertEquals(0,$vat1);
    	$this->assertEquals(0,$vat2);

    	// Test RULE ES-IT
    	$vat1=get_default_localtax($companyes,$notcompanyit,1,0);
    	$vat2=get_default_localtax($companyes,$notcompanyit,2,0);
    	$this->assertEquals(0,$vat1);
    	$this->assertEquals(0,$vat2);

    	// Test RULE FR-IT
    	// Not tested

    	// Test RULE ES-US
    	$vat1=get_default_localtax($companyes,$companyus,1,0);
    	$vat2=get_default_localtax($companyes,$companyus,2,0);
    	$this->assertEquals(0,$vat1);
    	$this->assertEquals(0,$vat2);
    }
}
?>