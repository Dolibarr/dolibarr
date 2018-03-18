<?php
/* Advance Targeting Emailling for mass emailing module
 * Copyright (C) 2013  Florian Henry <florian.henry@open-concept.pro>
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * 	\file		comm/mailing/class/advtargetemailing.class.php
 * 	\ingroup	mailing
 * 	\brief		This file is an example CRUD class file (Create/Read/Update/Delete)
 */


/**
 * Class to manage advanced emailing target selector
 */
class AdvanceTargetingMailing extends CommonObject
{

	var $db; //!< To store db handler
	var $error; //!< To return error code (or message)
	var $errors = array(); //!< To return several error codes (or messages)
	var $element='advtargetemailing';			//!< Id that identify managed objects
	var $table_element='advtargetemailing';	//!< Name of table without prefix where object is stored

	var $id;

	var $name;
	var $entity;
	var $fk_mailing;
	var $filtervalue;
	var $fk_user_author;
	var $datec='';
	var $fk_user_mod;
	var $tms='';

	var $select_target_type = array();
	var $type_statuscommprospect=array();
	var $thirdparty_lines;
	var $contact_lines;


	/**
	 * Constructor
	 *
	 * 	@param	DoliDb		$db		Database handler
	 */
	function __construct($db)
	{
		global $langs;
		$langs->load('customers');

		$this->db = $db;

		$this->select_target_type = array(
				'2' => $langs->trans('Contacts'),
				'1' => $langs->trans('Contacts') . '+' . $langs->trans('ThirdParty'),
				'3' => $langs->trans('ThirdParty'),
				'4' => $langs->trans('ContactsWithThirdpartyFilter')
		);
		$this->type_statuscommprospect = array(
				- 1 => $langs->trans("StatusProspect-1"),
				0 => $langs->trans("StatusProspect0"),
				1 => $langs->trans("StatusProspect1"),
				2 => $langs->trans("StatusProspect2"),
				3 => $langs->trans("StatusProspect3")
		);
	}

	/**
	 *  Create object into database
	 *
	 *  @param	User	$user        User that creates
	 *  @param  int		$notrigger   0=launch triggers after, 1=disable triggers
	 *  @return int      		   	 <0 if KO, Id of created object if OK
	 */
	function create($user, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;

		// Clean parameters
		if (isset($this->fk_mailing)) $this->fk_mailing=trim($this->fk_mailing);
		if (isset($this->name)) $this->name=trim($this->name);
		if (isset($this->filtervalue)) $this->filtervalue=trim($this->filtervalue);
		if (isset($this->fk_user_author)) $this->fk_user_author=trim($this->fk_user_author);
		if (isset($this->fk_user_mod)) $this->fk_user_mod=trim($this->fk_user_mod);



		// Check parameters
		// Put here code to add control on parameters values

		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."advtargetemailing(";

		$sql.= "name,";
		$sql.= "entity,";
		$sql.= "fk_mailing,";
		$sql.= "filtervalue,";
		$sql.= "fk_user_author,";
		$sql.= "datec,";
		$sql.= "fk_user_mod";


		$sql.= ") VALUES (";

		$sql.= " ".(! isset($this->name)?'NULL':"'".$this->db->escape($this->name)."'").",";
		$sql.= " ".$conf->entity.",";
		$sql.= " ".(! isset($this->fk_mailing)?'NULL':"'".$this->db->escape($this->fk_mailing)."'").",";
		$sql.= " ".(! isset($this->filtervalue)?'NULL':"'".$this->db->escape($this->filtervalue)."'").",";
		$sql.= " ".$user->id.",";
		$sql.= " '".$this->db->idate(dol_now())."',";
		$sql.= " ".$user->id;


		$sql.= ")";

		$this->db->begin();

		dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."advtargetemailing");

			if (! $notrigger)
			{
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.

				//// Call triggers
				//include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				//$interface=new Interfaces($this->db);
				//$result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
				//if ($result < 0) { $error++; $this->errors=$interface->errors; }
				//// End call triggers
			}
		}

		// Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
				dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
				$this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return $this->id;
		}
	}

	/**
	 *  Load object in memory from the database
	 *
	 *  @param	int		$id    Id object
	 *  @return int          	<0 if KO, >0 if OK
	 */
	function fetch($id)
	{
		global $langs;
		$sql = "SELECT";
		$sql.= " t.rowid,";

		$sql.= " t.name,";
		$sql.= " t.entity,";
		$sql.= " t.fk_mailing,";
		$sql.= " t.filtervalue,";
		$sql.= " t.fk_user_author,";
		$sql.= " t.datec,";
		$sql.= " t.fk_user_mod,";
		$sql.= " t.tms";

		$sql.= " FROM ".MAIN_DB_PREFIX."advtargetemailing as t";
		$sql.= " WHERE t.rowid = ".$id;

		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id    = $obj->rowid;

				$this->name = $obj->name;
				$this->entity = $obj->entity;
				$this->fk_mailing = $obj->fk_mailing;
				$this->filtervalue = $obj->filtervalue;
				$this->fk_user_author = $obj->fk_user_author;
				$this->datec = $this->db->jdate($obj->datec);
				$this->fk_user_mod = $obj->fk_user_mod;
				$this->tms = $this->db->jdate($obj->tms);

			}
			$this->db->free($resql);

			return 1;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *  Load object in memory from the database
	 *
	 *  @param	int		$id    Id object
	 *  @return int          	<0 if KO, >0 if OK
	 */
	function fetch_by_mailing($id=0)
	{
		global $langs;
		$sql = "SELECT";
		$sql.= " t.rowid,";

		$sql.= " t.name,";
		$sql.= " t.entity,";
		$sql.= " t.fk_mailing,";
		$sql.= " t.filtervalue,";
		$sql.= " t.fk_user_author,";
		$sql.= " t.datec,";
		$sql.= " t.fk_user_mod,";
		$sql.= " t.tms";

		$sql.= " FROM ".MAIN_DB_PREFIX."advtargetemailing as t";
		if (!empty($id)) {
			$sql.= " WHERE t.fk_mailing = ".$id;
		}else {
			$sql.= " WHERE t.fk_mailing = ".$this->fk_mailing;
		}

		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id    = $obj->rowid;

				$this->name = $obj->name;
				$this->entity = $obj->entity;
				$this->fk_mailing = $obj->fk_mailing;
				$this->filtervalue = $obj->filtervalue;
				$this->fk_user_author = $obj->fk_user_author;
				$this->datec = $this->db->jdate($obj->datec);
				$this->fk_user_mod = $obj->fk_user_mod;
				$this->tms = $this->db->jdate($obj->tms);

			}
			$this->db->free($resql);

			return 1;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *  Update object into database
	 *
	 *  @param	User	$user        User that modifies
	 *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return int     		   	 <0 if KO, >0 if OK
	 */
	function update($user, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;

		// Clean parameters
		if (isset($this->fk_mailing)) $this->fk_mailing=trim($this->fk_mailing);
		if (isset($this->name)) $this->name=trim($this->name);
		if (isset($this->filtervalue)) $this->filtervalue=trim($this->filtervalue);
		if (isset($this->fk_user_author)) $this->fk_user_author=trim($this->fk_user_author);
		if (isset($this->fk_user_mod)) $this->fk_user_mod=trim($this->fk_user_mod);



		// Check parameters
		// Put here code to add a control on parameters values

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."advtargetemailing SET";

		$sql.= " name=".(isset($this->name)?"'".$this->db->escape($this->name)."'":"''").",";
		$sql.= " entity=".$conf->entity.",";
		$sql.= " fk_mailing=".(isset($this->fk_mailing)?$this->fk_mailing:"null").",";
		$sql.= " filtervalue=".(isset($this->filtervalue)?"'".$this->db->escape($this->filtervalue)."'":"null").",";
		$sql.= " fk_user_mod=".$user->id;

		$sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.

				//// Call triggers
				//include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				//$interface=new Interfaces($this->db);
				//$result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
				//if ($result < 0) { $error++; $this->errors=$interface->errors; }
				//// End call triggers
			}
		}

		// Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
				dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
				$this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
	}

	/**
	 *  Delete object in database
	 *
	 *	@param  User	$user        User that deletes
	 *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return	int					 <0 if KO, >0 if OK
	 */
	function delete($user, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;

		$this->db->begin();

		if (! $error)
		{
			if (! $notrigger)
			{
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.

				//// Call triggers
				//include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				//$interface=new Interfaces($this->db);
				//$result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
				//if ($result < 0) { $error++; $this->errors=$interface->errors; }
				//// End call triggers
			}
		}

		if (! $error)
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."advtargetemailing";
			$sql.= " WHERE rowid=".$this->id;

			dol_syslog(get_class($this)."::delete sql=".$sql);
			$resql = $this->db->query($sql);
			if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		}

		// Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
				dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
				$this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
	}


	/**
	 * Save query in database to retreive it
	 *
	 *	@param  	User		$user    		User that deletes
	 * 	@param		array		$arrayquery		All element to Query
	 * 	@return		int			<0 if KO, >0 if OK
	 */
	function savequery($user,$arrayquery)
	{
		global $langs,$conf;

		if (!empty($arrayquery)) {
			$result=$this->fetch_by_mailing($this->fk_mailing);
			$this->filtervalue=json_encode($arrayquery);
			if ($result<0) {
				return -1;
			}
			if (!empty($this->id)) {
				$this->update($user);
			}else {
				$this->create($user);
			}
		}
	}



	/**
	 * Load object in memory from database
	 *
	 * 	@param		array		$arrayquery	All element to Query
	 * 	@return		int			<0 if KO, >0 if OK
	 */
	function query_thirdparty($arrayquery)
	{
		global $langs,$conf;

		$sql = "SELECT";
		$sql.= " t.rowid";
		$sql.= " FROM " . MAIN_DB_PREFIX . "societe as t";
		$sql.= " LEFT OUTER JOIN " . MAIN_DB_PREFIX . "societe_extrafields as te ON te.fk_object=t.rowid ";

		$sqlwhere=array();

		$sqlwhere[]= 't.entity IN ('.getEntity('societe').')';

		if (count($arrayquery)>0) {

			if (array_key_exists('cust_saleman', $arrayquery)) {
				$sql.= " LEFT OUTER JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as saleman ON saleman.fk_soc=t.rowid ";
			}
			if (array_key_exists('cust_categ', $arrayquery)) {
				$sql.= " LEFT OUTER JOIN " . MAIN_DB_PREFIX . "categorie_societe as custcateg ON custcateg.fk_soc=t.rowid ";
			}

			if (!empty($arrayquery['cust_name'])) {

				$sqlwhere[]= $this->transformToSQL('t.nom',$arrayquery['cust_name']);
			}
			if (!empty($arrayquery['cust_code'])) {
				$sqlwhere[]= $this->transformToSQL('t.code_client',$arrayquery['cust_code']);
			}
			if (!empty($arrayquery['cust_adress'])) {
				$sqlwhere[]= $this->transformToSQL('t.address',$arrayquery['cust_adress']);
			}
			if (!empty($arrayquery['cust_zip'])) {
				$sqlwhere[]= $this->transformToSQL('t.zip',$arrayquery['cust_zip']);
			}
			if (!empty($arrayquery['cust_city'])) {
				$sqlwhere[]= $this->transformToSQL('t.town',$arrayquery['cust_city']);
			}
			if (!empty($arrayquery['cust_mothercompany'])) {
				$str=$this->transformToSQL('nom',$arrayquery['cust_mothercompany']);
				$sqlwhere[]= " (t.parent IN (SELECT rowid FROM " . MAIN_DB_PREFIX . "societe WHERE (".$str.")))";
			}
			if (!empty($arrayquery['cust_status']) && count($arrayquery['cust_status'])>0) {
				$sqlwhere[]= " (t.status IN (".implode(',',$arrayquery['cust_status'])."))";
			}
			if (!empty($arrayquery['cust_typecust']) && count($arrayquery['cust_typecust'])>0) {
				$sqlwhere[]= " (t.client IN (".implode(',',$arrayquery['cust_typecust'])."))";
			}
			if (!empty($arrayquery['cust_comm_status']) && count($arrayquery['cust_comm_status']>0)) {
				$sqlwhere[]= " (t.fk_stcomm IN (".implode(',',$arrayquery['cust_comm_status'])."))";
			}
			if (!empty($arrayquery['cust_prospect_status']) && count($arrayquery['cust_prospect_status'])>0) {
				$sqlwhere[]= " (t.fk_prospectlevel IN ('".implode("','",$arrayquery['cust_prospect_status'])."'))";
			}
			if (!empty($arrayquery['cust_typeent']) && count($arrayquery['cust_typeent'])>0) {
				$sqlwhere[]= " (t.fk_typent IN (".implode(',',$arrayquery['cust_typeent'])."))";
			}
			if (!empty($arrayquery['cust_saleman']) && count($arrayquery['cust_saleman'])>0) {
				$sqlwhere[]= " (saleman.fk_user IN (".implode(',',$arrayquery['cust_saleman'])."))";
			}
			if (!empty($arrayquery['cust_country']) && count($arrayquery['cust_country'])>0) {
				$sqlwhere[]= " (t.fk_pays IN (".implode(',',$arrayquery['cust_country'])."))";
			}
			if (!empty($arrayquery['cust_effectif_id']) && count($arrayquery['cust_effectif_id'])>0) {
				$sqlwhere[]= " (t.fk_effectif IN (".implode(',',$arrayquery['cust_effectif_id'])."))";
			}
			if (!empty($arrayquery['cust_categ']) && count($arrayquery['cust_categ'])>0) {
				$sqlwhere[]= " (custcateg.fk_categorie IN (".implode(',',$arrayquery['cust_categ'])."))";
			}
			if (!empty($arrayquery['cust_language']) && count($arrayquery['cust_language'])>0) {
				$sqlwhere[]= " (t.default_lang IN ('".implode("','",$arrayquery['cust_language'])."'))";
			}

			//Standard Extrafield feature
			if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) {
				// fetch optionals attributes and labels
				dol_include_once('/core/class/extrafields.class.php');
				$extrafields = new ExtraFields($this->db);
				$extralabels=$extrafields->fetch_name_optionals_label('societe');

				foreach($extralabels as $key=>$val) {

					if (($extrafields->attribute_type[$key] == 'varchar') ||
						($extrafields->attribute_type[$key] == 'text')) {
						if (!empty($arrayquery['options_'.$key])) {
							$sqlwhere[]= " (te.".$key." LIKE '".$arrayquery['options_'.$key]."')";
						}
					} elseif (($extrafields->attribute_type[$key] == 'int') ||
						($extrafields->attribute_type[$key] == 'double')) {
						if (!empty($arrayquery['options_'.$key.'_max'])) {
							$sqlwhere[]= " (te.".$key." >= ".$arrayquery['options_'.$key.'_max']." AND te.".$key." <= ".$arrayquery['options_'.$key.'_min'].")";
						}
					} else if (($extrafields->attribute_type[$key] == 'date') ||
						($extrafields->attribute_type[$key] == 'datetime')) {
						if (!empty($arrayquery['options_'.$key.'_end_dt'])){
							$sqlwhere[]= " (te.".$key." >= '".$this->db->idate($arrayquery['options_'.$key.'_st_dt'])."' AND te.".$key." <= '".$this->db->idate($arrayquery['options_'.$key.'_end_dt'])."')";
						}
					}else if ($extrafields->attribute_type[$key] == 'boolean') {
						if ($arrayquery['options_'.$key]!=''){
							$sqlwhere[]= " (te.".$key." = ".$arrayquery['options_'.$key].")";
						}
					}else{
						if (is_array($arrayquery['options_'.$key])) {
							$sqlwhere[]= " (te.".$key." IN ('".implode("','",$arrayquery['options_'.$key])."'))";
						} elseif (!empty($arrayquery['options_'.$key])) {
							$sqlwhere[]= " (te.".$key." LIKE '".$arrayquery['options_'.$key]."')";
						}
					}

				}


			}

			if (count($sqlwhere)>0)	$sql.= " WHERE ".implode(" AND ",$sqlwhere);

		}


		dol_syslog(get_class($this) . "::query_thirdparty sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->thirdparty_lines = array();
			$num = $this->db->num_rows($resql);
			$i = 0;

			if ($num)
			{
				while( $i < $num)
				{
					$obj = $this->db->fetch_object($resql);

					$this->thirdparty_lines[$i] = $obj->rowid;

					$i++;
				}
			}
			$this->db->free($resql);

			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::query_thirdparty " . $this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 * Load object in memory from database
	 *
	 * 	@param		array		$arrayquery	All element to Query
	 * 	@param		int			$withThirdpartyFilter	add contact with tridparty filter
	 * 	@return		int			<0 if KO, >0 if OK
	 */
	function query_contact($arrayquery, $withThirdpartyFilter = 0)
	{
		global $langs,$conf;

		$sql = "SELECT";
		$sql.= " t.rowid";
		$sql.= " FROM " . MAIN_DB_PREFIX . "socpeople as t";
		$sql.= " LEFT OUTER JOIN " . MAIN_DB_PREFIX . "socpeople_extrafields as te ON te.fk_object=t.rowid ";

		if (! empty($withThirdpartyFilter)) {
			$sql .= " LEFT OUTER JOIN " . MAIN_DB_PREFIX . "societe as ts ON ts.rowid=t.fk_soc";
			$sql .= " LEFT OUTER JOIN " . MAIN_DB_PREFIX . "societe_extrafields as tse ON tse.fk_object=ts.rowid ";
		}

		$sqlwhere=array();

		$sqlwhere[]= 't.entity IN ('.getEntity('socpeople').')';

		if (count($arrayquery)>0) {

			if (array_key_exists('contact_categ', $arrayquery)) {
				$sql.= " LEFT OUTER JOIN " . MAIN_DB_PREFIX . "categorie_contact as contactcateg ON contactcateg.fk_socpeople=t.rowid ";
			}

			if (!empty($arrayquery['contact_lastname'])) {
				$sqlwhere[]=$this->transformToSQL('t.lastname',$arrayquery['contact_lastname']);
			}
			if (!empty($arrayquery['contact_firstname'])) {
				$sqlwhere[]=$this->transformToSQL('t.firstname',$arrayquery['contact_firstname']);
			}
			if (!empty($arrayquery['contact_country']) && count($arrayquery['contact_country'])) {
				$sqlwhere[]= " (t.fk_pays IN (".$this->db->escape(implode(',',$arrayquery['contact_country']))."))";
			}
			if (!empty($arrayquery['contact_status']) && count($arrayquery['contact_status'])>0) {
				$sqlwhere[]= " (t.statut IN (".$this->db->escape(implode(',',$arrayquery['contact_status']))."))";
			}
			if (!empty($arrayquery['contact_civility']) && count($arrayquery['contact_civility'])>0) {
				$sqlwhere[]= " (t.civility IN ('".$this->db->escape(implode("','",$arrayquery['contact_civility']))."'))";
			}
			if ($arrayquery['contact_no_email']!='') {
				$sqlwhere[]= " (t.no_email='".$this->db->escape($arrayquery['contact_no_email'])."')";
			}
			if ($arrayquery['contact_update_st_dt']!='') {
				$sqlwhere[]= " (t.tms >= '".$this->db->idate($arrayquery['contact_update_st_dt'])."' AND t.tms <= '".$this->db->idate($arrayquery['contact_update_end_dt'])."')";
			}
			if ($arrayquery['contact_create_st_dt']!='') {
				$sqlwhere[]= " (t.datec >= '".$this->db->idate($arrayquery['contact_create_st_dt'])."' AND t.datec <= '".$this->db->idate($arrayquery['contact_create_end_dt'])."')";
			}
			if (!empty($arrayquery['contact_categ']) && count($arrayquery['contact_categ'])>0) {
				$sqlwhere[]= " (contactcateg.fk_categorie IN (".$this->db->escape(implode(",",$arrayquery['contact_categ']))."))";
			}

			//Standard Extrafield feature
			if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) {
				// fetch optionals attributes and labels
				dol_include_once('/core/class/extrafields.class.php');
				$extrafields = new ExtraFields($this->db);
				$extralabels=$extrafields->fetch_name_optionals_label('socpeople');

				foreach($extralabels as $key=>$val) {

					if (($extrafields->attribute_type[$key] == 'varchar') ||
					($extrafields->attribute_type[$key] == 'text')) {
						if (!empty($arrayquery['options_'.$key.'_cnct'])) {
							$sqlwhere[]= " (te.".$key." LIKE '".$arrayquery['options_'.$key.'_cnct']."')";
						}
					} elseif (($extrafields->attribute_type[$key] == 'int') ||
						($extrafields->attribute_type[$key] == 'double')) {
						if (!empty($arrayquery['options_'.$key.'_max'.'_cnct'])) {
							$sqlwhere[]= " (te.".$key." >= ".$arrayquery['options_'.$key.'_max'.'_cnct']." AND te.".$key." <= ".$arrayquery['options_'.$key.'_min'.'_cnct'].")";
						}
					} else if (($extrafields->attribute_type[$key] == 'date') ||
					($extrafields->attribute_type[$key] == 'datetime')) {
						if (!empty($arrayquery['options_'.$key.'_end_dt'.'_cnct'])){
							$sqlwhere[]= " (te.".$key." >= '".$this->db->idate($arrayquery['options_'.$key.'_st_dt'.'_cnct'])."' AND te.".$key." <= '".$this->db->idate($arrayquery['options_'.$key.'_end_dt'.'_cnct'])."')";
						}
					}else if ($extrafields->attribute_type[$key] == 'boolean') {
						if ($arrayquery['options_'.$key.'_cnct']!=''){
							if ($arrayquery['options_'.$key.'_cnct']==0) {
								$sqlwhere[]= " (te.".$key." = ".$arrayquery['options_'.$key.'_cnct']." OR ((te.".$key." IS NULL) AND (te.fk_object IS NOT NULL)))";
							}else {
								$sqlwhere[]= " (te.".$key." = ".$arrayquery['options_'.$key.'_cnct'].")";
							}
						}
					}else{
						if (is_array($arrayquery['options_'.$key.'_cnct'])) {
							$sqlwhere[]= " (te.".$key." IN ('".implode("','",$arrayquery['options_'.$key.'_cnct'])."'))";
						} elseif (!empty($arrayquery['options_'.$key.'_cnct'])) {
							$sqlwhere[]= " (te.".$key." LIKE '".$arrayquery['options_'.$key.'_cnct']."')";
						}
					}

				}

				if (! empty($withThirdpartyFilter)) {
					if (array_key_exists('cust_saleman', $arrayquery)) {
						$sql.= " LEFT OUTER JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as saleman ON saleman.fk_soc=ts.rowid ";
					}
					if (array_key_exists('cust_categ', $arrayquery)) {
						$sql.= " LEFT OUTER JOIN " . MAIN_DB_PREFIX . "categorie_societe as custcateg ON custcateg.fk_soc=ts.rowid ";
					}

					if (!empty($arrayquery['cust_name'])) {

						$sqlwhere[]= $this->transformToSQL('ts.nom',$arrayquery['cust_name']);
					}
					if (!empty($arrayquery['cust_code'])) {
						$sqlwhere[]= $this->transformToSQL('ts.code_client',$arrayquery['cust_code']);
					}
					if (!empty($arrayquery['cust_adress'])) {
						$sqlwhere[]= $this->transformToSQL('ts.address',$arrayquery['cust_adress']);
					}
					if (!empty($arrayquery['cust_zip'])) {
						$sqlwhere[]= $this->transformToSQL('ts.zip',$arrayquery['cust_zip']);
					}
					if (!empty($arrayquery['cust_city'])) {
						$sqlwhere[]= $this->transformToSQL('ts.town',$arrayquery['cust_city']);
					}
					if (!empty($arrayquery['cust_mothercompany'])) {
						$str=$this->transformToSQL('nom',$arrayquery['cust_mothercompany']);
						$sqlwhere[]= " (ts.parent IN (SELECT rowid FROM " . MAIN_DB_PREFIX . "societe WHERE (".$str.")))";
					}
					if (!empty($arrayquery['cust_status']) && count($arrayquery['cust_status'])>0) {
						$sqlwhere[]= " (ts.status IN (".implode(',',$arrayquery['cust_status'])."))";
					}
					if (!empty($arrayquery['cust_typecust']) && count($arrayquery['cust_typecust'])>0) {
						$sqlwhere[]= " (ts.client IN (".implode(',',$arrayquery['cust_typecust'])."))";
					}
					if (!empty($arrayquery['cust_comm_status']) && count($arrayquery['cust_comm_status']>0)) {
						$sqlwhere[]= " (ts.fk_stcomm IN (".implode(',',$arrayquery['cust_comm_status'])."))";
					}
					if (!empty($arrayquery['cust_prospect_status']) && count($arrayquery['cust_prospect_status'])>0) {
						$sqlwhere[]= " (ts.fk_prospectlevel IN ('".implode("','",$arrayquery['cust_prospect_status'])."'))";
					}
					if (!empty($arrayquery['cust_typeent']) && count($arrayquery['cust_typeent'])>0) {
						$sqlwhere[]= " (ts.fk_typent IN (".implode(',',$arrayquery['cust_typeent'])."))";
					}
					if (!empty($arrayquery['cust_saleman']) && count($arrayquery['cust_saleman'])>0) {
						$sqlwhere[]= " (saleman.fk_user IN (".implode(',',$arrayquery['cust_saleman'])."))";
					}
					if (!empty($arrayquery['cust_country']) && count($arrayquery['cust_country'])>0) {
						$sqlwhere[]= " (ts.fk_pays IN (".implode(',',$arrayquery['cust_country'])."))";
					}
					if (!empty($arrayquery['cust_effectif_id']) && count($arrayquery['cust_effectif_id'])>0) {
						$sqlwhere[]= " (ts.fk_effectif IN (".implode(',',$arrayquery['cust_effectif_id'])."))";
					}
					if (!empty($arrayquery['cust_categ']) && count($arrayquery['cust_categ'])>0) {
						$sqlwhere[]= " (custcateg.fk_categorie IN (".implode(',',$arrayquery['cust_categ'])."))";
					}
					if (!empty($arrayquery['cust_language']) && count($arrayquery['cust_language'])>0) {
						$sqlwhere[]= " (ts.default_lang IN ('".implode("','",$arrayquery['cust_language'])."'))";
					}

					//Standard Extrafield feature
					if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) {
						// fetch optionals attributes and labels
						dol_include_once('/core/class/extrafields.class.php');
						$extrafields = new ExtraFields($this->db);
						$extralabels=$extrafields->fetch_name_optionals_label('societe');

						foreach($extralabels as $key=>$val) {

							if (($extrafields->attribute_type[$key] == 'varchar') ||
									($extrafields->attribute_type[$key] == 'text')) {
										if (!empty($arrayquery['options_'.$key])) {
											$sqlwhere[]= " (tse.".$key." LIKE '".$arrayquery['options_'.$key]."')";
										}
									} elseif (($extrafields->attribute_type[$key] == 'int') ||
											($extrafields->attribute_type[$key] == 'double')) {
												if (!empty($arrayquery['options_'.$key.'_max'])) {
													$sqlwhere[]= " (tse.".$key." >= ".$arrayquery['options_'.$key.'_max']." AND tse.".$key." <= ".$arrayquery['options_'.$key.'_min'].")";
												}
									} else if (($extrafields->attribute_type[$key] == 'date') ||
											($extrafields->attribute_type[$key] == 'datetime')) {
												if (!empty($arrayquery['options_'.$key.'_end_dt'])){
													$sqlwhere[]= " (tse.".$key." >= '".$this->db->idate($arrayquery['options_'.$key.'_st_dt'])."' AND tse.".$key." <= '".$this->db->idate($arrayquery['options_'.$key.'_end_dt'])."')";
												}
											}else if ($extrafields->attribute_type[$key] == 'boolean') {
												if ($arrayquery['options_'.$key]!=''){
													$sqlwhere[]= " (tse.".$key." = ".$arrayquery['options_'.$key].")";
												}
											}else{
												if (is_array($arrayquery['options_'.$key])) {
													$sqlwhere[]= " (tse.".$key." IN ('".implode("','",$arrayquery['options_'.$key])."'))";
												} elseif (!empty($arrayquery['options_'.$key])) {
													$sqlwhere[]= " (tse.".$key." LIKE '".$arrayquery['options_'.$key]."')";
												}
											}
						}
					}
				}
			}
			if (count($sqlwhere)>0)	$sql.= " WHERE ".implode(" AND ",$sqlwhere);
		}

		dol_syslog(get_class($this) . "::query_contact sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->contact_lines = array();
			$num = $this->db->num_rows($resql);
			$i = 0;

			if ($num)
			{
				while( $i < $num)
				{
					$obj = $this->db->fetch_object($resql);

					$this->contact_lines[$i] = $obj->rowid;

					$i++;
				}
			}
			$this->db->free($resql);

			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::query_contact " . $this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 * Parse criteria to return a SQL qury formated
	 *
	 * 	@param		string		$column_to_test	column to test
	 *  @param		string		$criteria	Use %% as magic caracters. For exemple to find all item like <b>jean, joe, jim</b>, you can input <b>j%%</b>, you can also use ; as separator for value,
	 *  									and use ! for except this value.
	 *  									For exemple  jean;joe;jim%%;!jimo;!jima%> will target all jean, joe, start with jim but not jimo and not everythnig taht start by jima
	 * 	@return		string		Sql to use for the where condition
	 */
	public function transformToSQL($column_to_test,$criteria) {
		$return_sql_criteria = '(';

		//This is a multiple value test
		if (preg_match('/;/',$criteria)) {
			$return_sql_not_like=array();
			$return_sql_like=array();

			$criteria_array=explode(';',$criteria);
			foreach($criteria_array as $inter_criteria) {
				if (preg_match('/!/',$inter_criteria)) {
					$return_sql_not_like[]= '('.$column_to_test.' NOT LIKE \''.str_replace('!', '', $inter_criteria).'\')';
				} else {
					$return_sql_like[]= '('.$column_to_test.' LIKE \''.$inter_criteria.'\')';
				}
			}

			if (count($return_sql_like)>0) {
				$return_sql_criteria .= '(' . implode (' OR ', $return_sql_like) .')';
			}
			if (count($return_sql_not_like)>0) {
				$return_sql_criteria .= ' AND (' . implode (' AND ', $return_sql_not_like).')';
			}

		}else {
			$return_sql_criteria .= $column_to_test . ' LIKE \''.$this->db->escape($criteria).'\'';
		}

		$return_sql_criteria .= ')';

		return $return_sql_criteria;
	}


}