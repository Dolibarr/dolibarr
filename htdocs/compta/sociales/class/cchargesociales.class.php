<?php
/* Copyright (C) 2016       Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/compta/sociales/class/cchargesociales.class.php
 * \ingroup tax
 * \brief   File to manage type of social/fiscal taxes
 */

// Put here all includes required by your class file
//require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class Cchargesociales
 */
class Cchargesociales
{
	/**
	 * @var string Id to identify managed objects
	 */
	public $element = 'cchargesociales';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'c_chargesociales';

	/**
	 * @var string Label
	 */
	public $libelle;

	public $deductible;
	public $active;
	public $code;

	/**
	 * @var int ID
	 */
	public $fk_pays;

	/**
	 * @var string module
	 */
	public $module;
	public $accountancy_code;


	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 *
	 * @return int <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$error = 0;

		// Clean parameters
		$this->trimParameters(
			array(
				'libelle',
				'deductible',
				'active',
				'code',
				'fk_pays',
				'module',
				'accountancy_code',
			)
		);

		// Check parameters
		// Put here code to add control on parameters values

		// Insert request
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.$this->table_element.'(';

		$sql .= 'libelle,';
		$sql .= 'deductible,';
		$sql .= 'active,';
		$sql .= 'code,';
		$sql .= 'fk_pays,';
		$sql .= 'module';
		$sql .= 'accountancy_code';


		$sql .= ') VALUES (';

		$sql .= ' '.(!isset($this->libelle) ? 'NULL' : "'".$this->db->escape($this->libelle)."'").',';
		$sql .= ' '.(!isset($this->deductible) ? 'NULL' : $this->deductible).',';
		$sql .= ' '.(!isset($this->active) ? 'NULL' : $this->active).',';
		$sql .= ' '.(!isset($this->code) ? 'NULL' : "'".$this->db->escape($this->code)."'").',';
		$sql .= ' '.(!isset($this->fk_pays) ? 'NULL' : $this->fk_pays).',';
		$sql .= ' '.(!isset($this->module) ? 'NULL' : "'".$this->db->escape($this->module)."'").',';
		$sql .= ' '.(!isset($this->accountancy_code) ? 'NULL' : "'".$this->db->escape($this->accountancy_code)."'");


		$sql .= ')';

		$this->db->begin();

		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);

			//if (!$notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action to call a trigger.

				//// Call triggers
				//$result=$this->call_trigger('MYOBJECT_CREATE',$user);
				//if ($result < 0) $error++;
				//// End call triggers
			//}
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();

			return -1 * $error;
		} else {
			$this->db->commit();

			return $this->id;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id  Id object
	 * @param string $ref Ref
	 *
	 * @return int <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = 'SELECT';
		$sql .= " t.id,";
		$sql .= " t.libelle,";
		$sql .= " t.deductible,";
		$sql .= " t.active,";
		$sql .= " t.code,";
		$sql .= " t.fk_pays,";
		$sql .= " t.module,";
		$sql .= " t.accountancy_code";
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		if (null !== $ref) {
			$sql .= " WHERE t.code = '".$this->db->escape($ref)."'";
		} else {
			$sql .= ' WHERE t.id = '.$id;
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$numrows = $this->db->num_rows($resql);
			if ($numrows) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->id;

				$this->libelle = $obj->libelle;
				$this->deductible = $obj->deductible;
				$this->active = $obj->active;
				$this->code = $obj->code;
				$this->fk_pays = $obj->fk_pays;
				$this->module = $obj->module;
				$this->accountancy_code = $obj->accountancy_code;
			}
			$this->db->free($resql);

			if ($numrows) {
				return 1;
			} else {
				return 0;
			}
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		// Clean parameters

		$this->trimParameters(
			array(
				'libelle',
				'deductible',
				'active',
				'code',
				'fk_pays',
				'module',
				'accountancy_code',
			)
		);


		// Check parameters
		// Put here code to add a control on parameters values

		// Update request
		$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element.' SET';
		$sql .= ' libelle = '.(isset($this->libelle) ? "'".$this->db->escape($this->libelle)."'" : "null").',';
		$sql .= ' deductible = '.(isset($this->deductible) ? $this->deductible : "null").',';
		$sql .= ' active = '.(isset($this->active) ? $this->active : "null").',';
		$sql .= ' code = '.(isset($this->code) ? "'".$this->db->escape($this->code)."'" : "null").',';
		$sql .= ' fk_pays = '.(isset($this->fk_pays) ? $this->fk_pays : "null").',';
		$sql .= ' module = '.(isset($this->module) ? "'".$this->db->escape($this->module)."'" : "null").',';
		$sql .= ' accountancy_code = '.(isset($this->accountancy_code) ? "'".$this->db->escape($this->accountancy_code)."'" : "null");
		$sql .= ' WHERE id='.$this->id;

		$this->db->begin();

		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
		}

		//if (!$error && !$notrigger) {
			// Uncomment this and change MYOBJECT to your own tag if you
			// want this action calls a trigger.

			//// Call triggers
			//$result=$this->call_trigger('MYOBJECT_MODIFY',$user);
			//if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
			//// End call triggers
		//}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();

			return -1 * $error;
		} else {
			$this->db->commit();

			return 1;
		}
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user      User that deletes
	 * @param bool $notrigger false=launch triggers after, true=disable triggers
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$error = 0;

		$this->db->begin();

		//if (!$error) {
			//if (!$notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.

				//// Call triggers
				//$result=$this->call_trigger('MYOBJECT_DELETE',$user);
				//if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
				//// End call triggers
			//}
		//}

		if (!$error) {
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX.$this->table_element;
			$sql .= ' WHERE id='.$this->id;

			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->errors[] = 'Error '.$this->db->lasterror();
				dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
			}
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();

			return -1 * $error;
		} else {
			$this->db->commit();

			return 1;
		}
	}

	/**
	 * Load an object from its id and create a new one in database
	 *
	 * @param	User	$user		User making the clone
	 * @param   int     $fromid     Id of object to clone
	 * @return  int                 New id of clone
	 */
	public function createFromClone(User $user, $fromid)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$error = 0;
		$object = new Cchargesociales($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		// Reset object
		$object->id = 0;

		// Clear fields
		// ...

		// Create clone
		$this->context['createfromclone'] = 'createfromclone';
		$result = $object->create($user);

		// Other options
		if ($result < 0) {
			$error++;
			$this->errors = $object->errors;
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
		}

		unset($this->context['createfromclone']);

		// End
		if (!$error) {
			$this->db->commit();

			return $object->id;
		} else {
			$this->db->rollback();

			return -1;
		}
	}

	/**
	 *  Return a link to the user card (with optionaly the picto)
	 * 	Use this->id,this->lastname, this->firstname
	 *
	 *	@param	int		$withpicto			Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *	@param	string	$option				On what the link point to
	 *  @param	integer	$notooltip			1=Disable tooltip
	 *  @param	int		$maxlen				Max length of visible user name
	 *  @param  string  $morecss            Add more css on link
	 *	@return	string						String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $maxlen = 24, $morecss = '')
	{
		global $langs, $conf, $db;
		global $dolibarr_main_authentication, $dolibarr_main_demo;
		global $menumanager;


		$result = '';
		$companylink = '';

		$label = '<u>'.$langs->trans("MyModule").'</u>';
		$label .= '<div width="100%">';
		$label .= '<b>'.$langs->trans('Ref').':</b> '.$this->ref;

		$link = '<a href="'.DOL_URL_ROOT.'/tax/card.php?id='.$this->id.'"';
		$link .= ($notooltip ? '' : ' title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip'.($morecss ? ' '.$morecss : '').'"');
		$link .= '>';
		$linkend = '</a>';

		if ($withpicto)
		{
			$result .= ($link.img_object(($notooltip ? '' : $label), 'label', ($notooltip ? '' : 'class="classfortooltip"'), 0, 0, $notooltip ? 0 : 1).$linkend);
			if ($withpicto != 2) $result .= ' ';
		}
		$result .= $link.$this->ref.$linkend;
		return $result;
	}

	/**
	 *  Retourne le libelle du status d'un user (actif, inactif)
	 *
	 *  @param	int		$mode          0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Renvoi le libelle d'un status donne
	 *
	 *  @param	int		$status        	Id status
	 *  @param  int		$mode          	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return string 			       	Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		global $langs;

		if ($mode == 0)
		{
			if ($status == 1) return $langs->trans('Enabled');
			elseif ($status == 0) return $langs->trans('Disabled');
		} elseif ($mode == 1)
		{
			if ($status == 1) return $langs->trans('Enabled');
			elseif ($status == 0) return $langs->trans('Disabled');
		} elseif ($mode == 2)
		{
			if ($status == 1) return img_picto($langs->trans('Enabled'), 'statut4').' '.$langs->trans('Enabled');
			elseif ($status == 0) return img_picto($langs->trans('Disabled'), 'statut5').' '.$langs->trans('Disabled');
		} elseif ($mode == 3)
		{
			if ($status == 1) return img_picto($langs->trans('Enabled'), 'statut4');
			elseif ($status == 0) return img_picto($langs->trans('Disabled'), 'statut5');
		} elseif ($mode == 4)
		{
			if ($status == 1) return img_picto($langs->trans('Enabled'), 'statut4').' '.$langs->trans('Enabled');
			elseif ($status == 0) return img_picto($langs->trans('Disabled'), 'statut5').' '.$langs->trans('Disabled');
		} elseif ($mode == 5)
		{
			if ($status == 1) return $langs->trans('Enabled').' '.img_picto($langs->trans('Enabled'), 'statut4');
			elseif ($status == 0) return $langs->trans('Disabled').' '.img_picto($langs->trans('Disabled'), 'statut5');
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
		$this->id = 0;

		$this->libelle = '';
		$this->deductible = '';
		$this->active = '';
		$this->code = '';
		$this->fk_pays = '';
		$this->module = '';
		$this->accountancy_code = '';
	}

	/**
	 * Trim object parameters
	 *
	 * @param string[] $parameters array of parameters to trim
	 * @return void
	 */
	private function trimParameters($parameters)
	{
		foreach ($parameters as $parameter) {
			if (isset($this->$parameter)) {
				$this->$parameter = trim($this->$parameter);
			}
		}
	}
}
