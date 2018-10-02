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
 *      \file       test/phpunit/FactureRecTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/compta/facture/class/facture.class.php';
require_once dirname(__FILE__).'/../../htdocs/compta/facture/class/facture-rec.class.php';

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
class FactureRecTest extends PHPUnit_Framework_TestCase
{
	protected $savconf;
	protected $savuser;
	protected $savlangs;
	protected $savdb;

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @return FactureTest
	 */
	function __construct()
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
		$db->begin();	// This is to have all actions inside a transaction even if test launched without suite.

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
     * testFactureCreate
     *
     * @return int
     */
    public function testFactureRecCreate()
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$localobjectinv=new Facture($this->savdb);
		$localobjectinv->initAsSpecimen();
		$localobjectinv->create($user);

		$localobject=new FactureRec($this->savdb);
    	$localobject->initAsSpecimen();
    	$result=$localobject->create($user, $localobjectinv->id);

    	$this->assertLessThan($result, 0);
    	print __METHOD__." result=".$result."\n";
    	return $result;
    }






    /**
     * Edit an object to test updates
     *
     * @param 	mixed	$localobject		Object Facture
     * @return	void
     */
    public function changeProperties(&$localobject)
    {
        $localobject->note_private='New note';
        //$localobject->note='New note after update';
    }

    /**
     * Compare all public properties values of 2 objects
     *
     * @param 	Object		$oA						Object operand 1
     * @param 	Object		$oB						Object operand 2
     * @param	boolean		$ignoretype				False will not report diff if type of value differs
     * @param	array		$fieldstoignorearray	Array of fields to ignore in diff
	 * @return	array								Array with differences
     */
    public function objCompare($oA,$oB,$ignoretype=true,$fieldstoignorearray=array('id'))
    {
        $retAr=array();

        if (get_class($oA) !== get_class($oB))
        {
            $retAr[]="Supplied objects are not of same class.";
        }
        else
        {
            $oVarsA=get_object_vars($oA);
            $oVarsB=get_object_vars($oB);
            $aKeys=array_keys($oVarsA);
            foreach($aKeys as $sKey)
            {
                if (in_array($sKey,$fieldstoignorearray)) continue;
                if (! $ignoretype && $oVarsA[$sKey] !== $oVarsB[$sKey])
                {
                    $retAr[]=$sKey.' : '.(is_object($oVarsA[$sKey])?get_class($oVarsA[$sKey]):$oVarsA[$sKey]).' <> '.(is_object($oVarsB[$sKey])?get_class($oVarsB[$sKey]):$oVarsB[$sKey]);
                }
                if ($ignoretype && $oVarsA[$sKey] != $oVarsB[$sKey])
                {
                    $retAr[]=$sKey.' : '.(is_object($oVarsA[$sKey])?get_class($oVarsA[$sKey]):$oVarsA[$sKey]).' <> '.(is_object($oVarsB[$sKey])?get_class($oVarsB[$sKey]):$oVarsB[$sKey]);
                }
            }
        }
        return $retAr;
    }
}
