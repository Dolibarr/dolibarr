<?php
/* Copyright (C) 2007-2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014-2016  Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 * \file        class/emailsenderprofile.class.php
 * \ingroup     monmodule
 * \brief       This file is a CRUD class file for EmailSenderProfile (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for EmailSenderProfile
 */
class EmailSenderProfile extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'emailsenderprofile';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'c_email_senderprofile';

	/**
	 * @var array  Does emailsenderprofile support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 */
	public $ismultientitymanaged = 1;

	/**
	 * @var string String with name of icon for emailsenderprofile
	 */
	public $picto = 'emailsenderprofile@monmodule';


	/**
	 *             'type' if the field format.
	 *             'label' the translation key.
	 *             'enabled' is a condition when the filed must be managed.
	 *             'visible' says if field is visible in list (-1 means not shown by default but can be added into list to be viewed).
	 *             'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *             'index' if we want an index in database.
	 *             'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *             'position' is the sort order of field.
	 *             'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *             'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *             'help' is a string visible as a tooltip on field
	 *             'comment' is not used. You can store here any text of your choice.
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields=array(
		'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'visible'=>-1, 'enabled'=>1, 'position'=>1, 'notnull'=>1, 'index'=>1, 'comment'=>'Id',),
		'entity' => array('type'=>'integer', 'label'=>'Entity', 'visible'=>-1, 'enabled'=>1, 'position'=>20, 'notnull'=>1, 'index'=>1,),
		'label' => array('type'=>'varchar(255)', 'label'=>'Label', 'visible'=>1, 'enabled'=>1, 'position'=>30, 'notnull'=>-1),
		'email' => array('type'=>'varchar(255)', 'label'=>'Email', 'visible'=>1, 'enabled'=>1, 'position'=>40, 'notnull'=>-1),
		//'fk_user_creat' => array('type'=>'integer', 'label'=>'UserAuthor', 'visible'=>-1, 'enabled'=>1, 'position'=>500, 'notnull'=>1,),
		//'fk_user_modif' => array('type'=>'integer', 'label'=>'UserModif', 'visible'=>-1, 'enabled'=>1, 'position'=>500, 'notnull'=>-1,),
		'signature' => array('type'=>'text', 'label'=>'Signature', 'visible'=>-1, 'enabled'=>1, 'position'=>1000, 'notnull'=>-1, 'index'=>1,),
		'position' => array('type'=>'integer', 'label'=>'Position', 'visible'=>-1, 'enabled'=>1, 'position'=>1000, 'notnull'=>-1, 'index'=>1,),
		'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'visible'=>-1, 'enabled'=>1, 'position'=>500, 'notnull'=>1,),
		'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'visible'=>-1, 'enabled'=>1, 'position'=>500, 'notnull'=>1,),
		'active' => array('type'=>'integer', 'label'=>'Status', 'visible'=>1, 'enabled'=>1, 'position'=>1000, 'notnull'=>-1, 'index'=>1),
	);

	/**
	 * @var int ID
	 */
	public $rowid;

	/**
	 * @var int Entity
	 */
	public $entity;

	/**
     * @var string Email Sender Profile label
     */
    public $label;

	public $email;
	public $date_creation;
	public $tms;
	//public $fk_user_creat;
	//public $fk_user_modif;
	public $signature;
	public $position;
	public $active;
	// END MODULEBUILDER PROPERTIES



	// If this object has a subtable with lines

	/**
	 * @var int    Name of subtable line
	 */
	//public $table_element_line = 'emailsenderprofiledet';
	/**
	 * @var int    Field with ID of parent key if this field has a parent
	 */
	//public $fk_element = 'fk_emailsenderprofile';
	/**
	 * @var int    Name of subtable class that manage subtable lines
	 */
	//public $class_element_line = 'EmailSenderProfileline';
	/**
	 * @var array  Array of child tables (child tables to delete before deleting a record)
	 */
	//protected $childtables=array('emailsenderprofiledet');
	/**
	 * @var EmailSenderProfileLine[]     Array of subtable lines
	 */
	//public $lines = array();



	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID)) $this->fields['rowid']['visible']=0;
		if (empty($conf->multicompany->enabled)) $this->fields['entity']['enabled']=0;
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		return $this->createCommon($user, $notrigger);
	}

	/**
	 * Clone and object into another one
	 *
	 * @param  	User 	$user      	User that creates
	 * @param  	int 	$fromid     Id of object to clone
	 * @return 	mixed 				New object created, <0 if KO
	 */
	public function createFromClone(User $user, $fromid)
	{
		global $hookmanager, $langs;
		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$object = new self($this->db);

		$this->db->begin();

		// Load source object
		$object->fetchCommon($fromid);
		// Reset some properties
		unset($object->id);
		unset($object->fk_user_creat);
		unset($object->import_key);

		// Clear fields
		$object->ref = "copy_of_".$object->ref;
		$object->title = $langs->trans("CopyOf")." ".$object->title;
		// ...

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->createCommon($user);
		if ($result < 0) {
			$error++;
			$this->error = $object->error;
			$this->errors = $object->errors;
		}

		// End
		if (!$error) {
			$this->db->commit();
			return $object;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id   Id object
	 * @param string $ref  Ref
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		$result = $this->fetchCommon($id, $ref);
		if ($result > 0 && ! empty($this->table_element_line)) $this->fetchLines();
		return $result;
	}

	/**
	 * Load object lines in memory from the database
	 *
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchLines()
	{
		$this->lines=array();

		// Load lines with object EmailSenderProfileLine

		return count($this->lines)?1:0;
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user       User that deletes
	 * @param bool $notrigger  false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		return $this->deleteCommon($user, $notrigger);
	}

	/**
	 *  Return a link to the object card (with optionaly the picto)
	 *
	 *	@param	int		$withpicto					Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *	@return	string								String with URL
	 */
	function getNomUrl($withpicto=0)
	{
		global $db, $conf, $langs;
		global $dolibarr_main_authentication, $dolibarr_main_demo;
		global $menumanager;

		$result = '';
		$companylink = '';

    $label=$this->label;

    $url='';
		//$url = dol_buildpath('/monmodule/emailsenderprofile_card.php',1).'?id='.$this->id;

		$linkstart = '';
		$linkend='';

		if ($withpicto)
		{
			$result.=($linkstart.img_object($label, 'label', 'class="classfortooltip"').$linkend);
			if ($withpicto != 2) $result.=' ';
		}
		$result.= $linkstart . $this->label . $linkend;
		return $result;
	}

	/**
	 * Return link to download file from a direct external access
	 *
	 * @param	int				$withpicto			Add download picto into link
	 * @return	string			HTML link to file
	 */
	function getDirectExternalLink($withpicto=0)
	{
		return 'todo';
	}

	/**
	 *  Retourne le libelle du status d'un user (actif, inactif)
	 *
	 *  @param	int		$mode          0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return	string 			       Label of status
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->status,$mode);
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *  Return the status
	 *
	 *  @param	int		$status        	Id status
	 *  @param  int		$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string 			       	Label of status
	 */
	static function LibStatut($status,$mode=0)
	{
		global $langs;

		if ($mode == 0 || $mode == 1)
		{
			if ($status == 1) return $langs->trans('Enabled');
			if ($status == 0) return $langs->trans('Disabled');
		}
		elseif ($mode == 2)
		{
			if ($status == 1) return img_picto($langs->trans('Enabled'),'statut4').' '.$langs->trans('Enabled');
			if ($status == 0) return img_picto($langs->trans('Disabled'),'statut5').' '.$langs->trans('Disabled');
		}
		elseif ($mode == 3)
		{
			if ($status == 1) return img_picto($langs->trans('Enabled'),'statut4');
			if ($status == 0) return img_picto($langs->trans('Disabled'),'statut5');
		}
		elseif ($mode == 4)
		{
			if ($status == 1) return img_picto($langs->trans('Enabled'),'statut4').' '.$langs->trans('Enabled');
			if ($status == 0) return img_picto($langs->trans('Disabled'),'statut5').' '.$langs->trans('Disabled');
		}
		elseif ($mode == 5)
		{
			if ($status == 1) return $langs->trans('Enabled').' '.img_picto($langs->trans('Enabled'),'statut4');
			if ($status == 0) return $langs->trans('Disabled').' '.img_picto($langs->trans('Disabled'),'statut5');
		}
		elseif ($mode == 6)
		{
			if ($status == 1) return $langs->trans('Enabled').' '.img_picto($langs->trans('Enabled'),'statut4');
			if ($status == 0) return $langs->trans('Disabled').' '.img_picto($langs->trans('Disabled'),'statut5');
		}
	}

	/**
	 *	Charge les informations d'ordre info dans l'objet commande
	 *
	 *	@param  int		$id       Id of order
	 *	@return	void
	 */
	function info($id)
	{
		$sql = 'SELECT rowid, date_creation as datec, tms as datem,';
		$sql.= ' fk_user_creat, fk_user_modif';
		$sql.= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		$sql.= ' WHERE t.rowid = '.$id;
		$result=$this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
				if ($obj->fk_user_author)
				{
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_author);
					$this->user_creation   = $cuser;
				}

				if ($obj->fk_user_valid)
				{
					$vuser = new User($this->db);
					$vuser->fetch($obj->fk_user_valid);
					$this->user_validation = $vuser;
				}

				if ($obj->fk_user_cloture)
				{
					$cluser = new User($this->db);
					$cluser->fetch($obj->fk_user_cloture);
					$this->user_cloture   = $cluser;
				}

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
				$this->date_validation   = $this->db->jdate($obj->datev);
			}

			$this->db->free($result);
		}
		else
		{
			dol_print_error($this->db);
		}
	}

	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	public function initAsSpecimen()
	{
		$this->initAsSpecimenCommon();
	}
}

/**
 * Class EmailSenderProfileLine. You can also remove this and generate a CRUD class for lines objects.
 */
/*
class EmailSenderProfileLine
{
	// @var int ID
	public $id;
	// @var mixed Sample line property 1
	public $prop1;
	// @var mixed Sample line property 2
	public $prop2;
}
*/