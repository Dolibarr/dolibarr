<?php
/* Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
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
 */

/**
 *      \file       htdocs/core/class/events.class.php
 *      \ingroup    core
 *		\brief      File of class to manage security events.
 *		\author		Laurent Destailleur
 */

// Put here all includes required by your class file
//require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';


/**
 *	Events class
 */
class Events // extends CommonObject
{
	public $element='events';				//!< Id that identify managed objects
	public $table_element='events';		//!< Name of table without prefix where object is stored

	var $id;
	var $db;

	var $error;

	var $tms;
	var $type;
	var $entity;
	var $dateevent;
	var $description;

	// List of all events supported by triggers
	var $eventstolog=array(
		array('id'=>'USER_LOGIN',             'test'=>1),
		array('id'=>'USER_LOGIN_FAILED',      'test'=>1),
	    array('id'=>'USER_LOGOUT',            'test'=>1),
		array('id'=>'USER_CREATE',            'test'=>1),
		array('id'=>'USER_MODIFY',            'test'=>1),
		array('id'=>'USER_NEW_PASSWORD',      'test'=>1),
		array('id'=>'USER_ENABLEDISABLE',     'test'=>1),
		array('id'=>'USER_DELETE',            'test'=>1),
	/*    array('id'=>'USER_SETINGROUP',        'test'=>1), deprecated. Replace with USER_MODIFY
	    array('id'=>'USER_REMOVEFROMGROUP',   'test'=>1), deprecated. Replace with USER_MODIFY */
		array('id'=>'GROUP_CREATE',           'test'=>1),
		array('id'=>'GROUP_MODIFY',           'test'=>1),
		array('id'=>'GROUP_DELETE',           'test'=>1),
	/*	array('id'=>'ACTION_CREATE',          'test'=>$conf->societe->enabled),
		array('id'=>'COMPANY_CREATE',         'test'=>$conf->societe->enabled),
		array('id'=>'CONTRACT_VALIDATE',      'test'=>$conf->contrat->enabled),
		array('id'=>'PROPAL_VALIDATE',        'test'=>$conf->propal->enabled),
		array('id'=>'PROPAL_CLOSE_SIGNED',    'test'=>$conf->propal->enabled),
		array('id'=>'PROPAL_CLOSE_REFUSED',   'test'=>$conf->propal->enabled),
		array('id'=>'PROPAL_SENTBYMAIL',      'test'=>$conf->propal->enabled),
		array('id'=>'ORDER_VALIDATE',         'test'=>$conf->commande->enabled),
		array('id'=>'ORDER_SENTBYMAIL',       'test'=>$conf->commande->enabled),
		array('id'=>'BILL_VALIDATE',          'test'=>$conf->facture->enabled),
		array('id'=>'BILL_PAYED',             'test'=>$conf->facture->enabled),
		array('id'=>'BILL_CANCEL',            'test'=>$conf->facture->enabled),
		array('id'=>'BILL_SENTBYMAIL',        'test'=>$conf->facture->enabled),
		array('id'=>'PAYMENT_CUSTOMER_CREATE','test'=>$conf->facture->enabled),
		array('id'=>'PAYMENT_SUPPLIER_CREATE','test'=>$conf->fournisseur->enabled),
		array('id'=>'MEMBER_CREATE',          'test'=>$conf->adherent->enabled),
		array('id'=>'MEMBER_VALIDATE',        'test'=>$conf->adherent->enabled),
		array('id'=>'MEMBER_SUBSCRIPTION',    'test'=>$conf->adherent->enabled),
		array('id'=>'MEMBER_MODIFY',          'test'=>$conf->adherent->enabled),
		array('id'=>'MEMBER_RESILIATE',       'test'=>$conf->adherent->enabled),
		array('id'=>'MEMBER_DELETE',          'test'=>$conf->adherent->enabled),
	*/
	);


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
		return 1;
	}


	/**
	 *   Create in database
	 *
	 *   @param      User	$user       User that create
	 *   @return     int     		    <0 if KO, >0 if OK
	 */
	function create($user)
	{
		global $conf, $langs;

		// Clean parameters
		$this->description=trim($this->description);

		// Check parameters
		if (empty($this->description)) { $this->error='ErrorBadValueForParameterCreateEventDesc'; return -1; }

		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."events(";
		$sql.= "type,";
		$sql.= "entity,";
		$sql.= "ip,";
		$sql.= "user_agent,";
		$sql.= "dateevent,";
		$sql.= "fk_user,";
		$sql.= "description";
		$sql.= ") VALUES (";
		$sql.= " '".$this->type."',";
		$sql.= " ".$conf->entity.",";
		$sql.= " '".$_SERVER['REMOTE_ADDR']."',";
		$sql.= " ".($_SERVER['HTTP_USER_AGENT']?"'".dol_trunc($_SERVER['HTTP_USER_AGENT'],250)."'":'NULL').",";
		$sql.= " '".$this->db->idate($this->dateevent)."',";
		$sql.= " ".($user->id?"'".$user->id."'":'NULL').",";
		$sql.= " '".$this->db->escape(dol_trunc($this->description,250))."'";
		$sql.= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."events");
			return $this->id;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			return -1;
		}
	}


	/**
	 * Update database
	 *
	 * @param	User    $user        	User that modify
	 * @param   int		$notrigger	    0=no, 1=yes (no update trigger)
	 * @return  int         			<0 if KO, >0 if OK
	 */
	function update($user=null, $notrigger=0)
	{
		global $conf, $langs;

		// Clean parameters
		$this->id=trim($this->id);
		$this->type=trim($this->type);
		$this->description=trim($this->description);

		// Check parameters
		// Put here code to add control on parameters values

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."events SET";
		$sql.= " type='".$this->type."',";
		$sql.= " dateevent=".$this->db->idate($this->dateevent).",";
		$sql.= " description='".$this->db->escape($this->description)."'";
		$sql.= " WHERE rowid=".$this->id;

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql)
		{
			$this->error="Error ".$this->db->lasterror();
			return -1;
		}
		return 1;
	}


	/**
	 *  Load object in memory from database
	 *
	 *  @param	int		$id         Id object
	 *  @param  User	$user       User that load
	 *  @return int         		<0 if KO, >0 if OK
	 */
	function fetch($id, $user=null)
	{
		global $langs;

		$sql = "SELECT";
		$sql.= " t.rowid,";
		$sql.= " t.tms,";
		$sql.= " t.type,";
		$sql.= " t.entity,";
		$sql.= " t.dateevent,";
		$sql.= " t.description,";
		$sql.= " t.ip,";
		$sql.= " t.user_agent";
		$sql.= " FROM ".MAIN_DB_PREFIX."events as t";
		$sql.= " WHERE t.rowid = ".$id;

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id    = $obj->rowid;
				$this->tms = $this->db->jdate($obj->tms);
				$this->type = $obj->type;
				$this->entity = $obj->entity;
				$this->dateevent = $this->db->jdate($obj->dateevent);
				$this->description = $obj->description;
				$this->ip = $obj->ip;
				$this->user_agent = $obj->user_agent;
			}
			$this->db->free($resql);

			return 1;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			return -1;
		}
	}


	/**
	 *  Delete object in database
	 *
	 *	@param	User	$user       User that delete
	 *	@return	int					<0 if KO, >0 if OK
	 */
	function delete($user)
	{
		global $conf, $langs;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."events";
		$sql.= " WHERE rowid=".$this->id;

		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql)
		{
			$this->error="Error ".$this->db->lasterror();
			return -1;
		}

		return 1;
	}


	/**
     *  Initialise an instance with random values.
     *  Used to build previews or test instances.
     *	id must be 0 if object instance is a specimen.
     *
     *  @return	void
	 */
	function initAsSpecimen()
	{
		$this->id=0;

		$this->tms=time();
		$this->type='';
		$this->dateevent=time();
		$this->description='This is a specimen event';
	}

}
