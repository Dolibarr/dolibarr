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
 *      \file       test/FactureTestSuite.php
 *		\ingroup    test
 *      \brief      This file is an example for a PHPUnit test
 *      \version    $Id$
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */
require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__).'/../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../htdocs/compta/facture/facture.class.php';
require_once dirname(__FILE__).'/FactureTest.php';


class FactureTestSuite extends PHPUnit_Framework_TestSuite
{
	protected $savconf;
	protected $savuser;
	protected $savlangs;
	protected $savdb;

  	protected function setUp()
    {
		//$this->sharedFixture
		global $conf,$user,$langs,$db;
		print __METHOD__." db->type=".$db->type." user->id=".$user->id;
		//print " - db ".$db->db;
		print "\n";
		$this->savconf=$conf;
		$this->savuser=$user;
		$this->savlangs=$langs;
		$this->savdb=$db;

		$db->begin();
		//print "TO=".$db->transaction_opened;
    }
  	protected function tearDown()
    {
    	global $conf,$langs,$db,$user;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		print __METHOD__."\n";
    	//print $db->getVersion();	// Uncomment this to know if db handler is still working
		//print "TO=".$db->transaction_opened;
    	$db->rollback();
    }


 	public static function suite()
    {
        return new FactureTestSuite('FactureTest');
    }

}
?>