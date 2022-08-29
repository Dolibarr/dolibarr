<?php
/* Copyright (C) 2011-2022  Alexandre Spangaro  <aspangaro@open-dsi.fr>
 * Copyright (C) 2014       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2021       Gauthier VERDOL     <gauthier.verdol@atm-consulting.fr>
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
 *  \file       htdocs/salaries/class/salary.class.php
 *  \ingroup    salaries
 *  \brief      Class for salaries module payment
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**
 *  Class to manage salary payments
 */
class Salary extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'salary';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'salary';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'salary';

	public $tms;

	/**
	 * @var int User ID
	 */
	public $fk_user;

	public $datep;
	public $datev;

	public $salary;
	public $amount;
	/**
	 * @var int ID
	 */
	public $fk_project;

	public $type_payment;

	/**
	 * @var string salary payments label
	 */
	public $label;

	public $datesp;
	public $dateep;

	/**
	 * @var int ID
	 */
	public $fk_bank;

	/**
	 * @var int ID
	 */
	public $fk_user_author;

	/**
	 * @var int ID
	 */
	public $fk_user_modif;

	/**
	 * @var user	User
	 */
	public $user;

	/**
	 * 1 if salary paid COMPLETELY, 0 otherwise (do not use it anymore, use statut and close_code)
	 */
	public $paye;

	const STATUS_UNPAID = 0;
	const STATUS_PAID = 1;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->element = 'salary';
		$this->table_element = 'salary';
	}

	/**
	 * Update database
	 *
	 * @param   User	$user        	User that modify
	 * @param	int		$notrigger	    0=no, 1=yes (no update trigger)
	 * @return  int         			<0 if KO, >0 if OK
	 */
	public function update($user = null, $notrigger = 0)
	{
		global $conf, $langs;

		$error = 0;

		// Clean parameters
		$this->amount = trim($this->amount);
		$this->label = trim($this->label);
		$this->note = trim($this->note);

		// Check parameters
		if (empty($this->fk_user) || $this->fk_user < 0) {
			$this->error = 'ErrorBadParameter';
			return -1;
		}

		$this->db->begin();

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."salary SET";
		$sql .= " tms='".$this->db->idate(dol_now())."',";
		$sql .= " fk_user=".((int) $this->fk_user).",";
		/*$sql .= " datep='".$this->db->idate($this->datep)."',";
		$sql .= " datev='".$this->db->idate($this->datev)."',";*/
		$sql .= " amount=".price2num($this->amount).",";
		$sql .= " fk_projet=".((int) $this->fk_project).",";
		$sql .= " fk_typepayment=".((int) $this->type_payment).",";
		$sql .= " label='".$this->db->escape($this->label)."',";
		$sql .= " datesp='".$this->db->idate($this->datesp)."',";
		$sql .= " dateep='".$this->db->idate($this->dateep)."',";
		$sql .= " note='".$this->db->escape($this->note)."',";
		$sql .= " fk_bank=".($this->fk_bank > 0 ? (int) $this->fk_bank : "null").",";
		$sql .= " fk_user_author=".((int) $this->fk_user_author).",";
		$sql .= " fk_user_modif=".($this->fk_user_modif > 0 ? (int) $this->fk_user_modif : (int) $user->id);
		$sql .= " WHERE rowid=".((int) $this->id);

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}

		// Update extrafield
		if (!$error) {
			if (!$error) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error++;
				}
			}
		}

		if (!$notrigger) {
			// Call trigger
			$result = $this->call_trigger('SALARY_MODIFY', $user);
			if ($result < 0) $error++;
			// End call triggers
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *  Load object in memory from database
	 *
	 *  @param	int		$id         id object
	 *  @param  User	$user       User that load
	 *  @return int         		<0 if KO, >0 if OK
	 */
	public function fetch($id, $user = null)
	{
		global $langs;
		$sql = "SELECT";
		$sql .= " s.rowid,";

		$sql .= " s.tms,";
		$sql .= " s.fk_user,";
		$sql .= " s.datep,";
		$sql .= " s.datev,";
		$sql .= " s.amount,";
		$sql .= " s.fk_projet as fk_project,";
		$sql .= " s.fk_typepayment,";
		$sql .= " s.label,";
		$sql .= " s.datesp,";
		$sql .= " s.dateep,";
		$sql .= " s.note,";
		$sql .= " s.paye,";
		$sql .= " s.fk_bank,";
		$sql .= " s.fk_user_author,";
		$sql .= " s.fk_user_modif,";
		$sql .= " s.fk_account";

		$sql .= " FROM ".MAIN_DB_PREFIX."salary as s";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON s.fk_bank = b.rowid";
		$sql .= " WHERE s.rowid = ".((int) $id);

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id               = $obj->rowid;
				$this->ref				= $obj->rowid;
				$this->tms				= $this->db->jdate($obj->tms);
				$this->fk_user          = $obj->fk_user;
				$this->datep			= $this->db->jdate($obj->datep);
				$this->datev			= $this->db->jdate($obj->datev);
				$this->amount           = $obj->amount;
				$this->fk_project       = $obj->fk_project;
				$this->type_payment     = $obj->fk_typepayment;
				$this->label			= $obj->label;
				$this->datesp			= $this->db->jdate($obj->datesp);
				$this->dateep			= $this->db->jdate($obj->dateep);
				$this->note				= $obj->note;
				$this->paye 			= $obj->paye;
				$this->fk_bank          = $obj->fk_bank;
				$this->fk_user_author   = $obj->fk_user_author;
				$this->fk_user_modif    = $obj->fk_user_modif;
				$this->fk_account       = $this->accountid = $obj->fk_account;

				// Retrieve all extrafield
				// fetch optionals attributes and labels
				$this->fetch_optionals();
			}
			$this->db->free($resql);

			return 1;
		} else {
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}


	/**
	 *  Delete object in database
	 *
	 *	@param	User	$user       User that delete
	 *	@return	int					<0 if KO, >0 if OK
	 */
	public function delete($user)
	{
		global $conf, $langs;

		$error = 0;

		// Call trigger
		$result = $this->call_trigger('SALARY_DELETE', $user);
		if ($result < 0) return -1;
		// End call triggers

		// Delete extrafields
		/*if (!$error)
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."salary_extrafields";
			$sql .= " WHERE fk_object = ".((int) $this->id);

			$resql = $this->db->query($sql);
			if (!$resql)
			{
				$this->errors[] = $this->db->lasterror();
				$error++;
			}
		}*/

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."salary";
		$sql .= " WHERE rowid=".((int) $this->id);

		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = "Error ".$this->db->lasterror();
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
	public function initAsSpecimen()
	{
		$this->id = 0;

		$this->tms = '';
		$this->fk_user = '';
		$this->datep = '';
		$this->datev = '';
		$this->amount = '';
		$this->label = '';
		$this->datesp = '';
		$this->dateep = '';
		$this->note = '';
		$this->fk_bank = '';
		$this->fk_user_author = '';
		$this->fk_user_modif = '';
	}

	/**
	 *  Create in database
	 *
	 *  @param      User	$user       User that create
	 *  @return     int      			<0 if KO, >0 if OK
	 */
	public function create($user)
	{
		global $conf, $langs;

		$error = 0;
		$now = dol_now();

		// Clean parameters
		$this->amount = price2num(trim($this->amount));
		$this->label = trim($this->label);
		$this->note = trim($this->note);
		$this->fk_bank = trim($this->fk_bank);
		$this->fk_user_author = trim($this->fk_user_author);
		$this->fk_user_modif = trim($this->fk_user_modif);
		$this->accountid = trim($this->accountid);
		$this->paye = trim($this->paye);

		// Check parameters
		if (!$this->label) {
			$this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentities("Label"));
			return -3;
		}
		if ($this->fk_user < 0 || $this->fk_user == '') {
			$this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentities("Employee"));
			return -4;
		}
		if ($this->amount == '') {
			$this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentities("Amount"));
			return -5;
		}
		/* if (isModEnabled('banque') && (empty($this->accountid) || $this->accountid <= 0))
		{
			$this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentities("Account"));
			return -6;
		}
		if (isModEnabled('banque') && (empty($this->type_payment) || $this->type_payment <= 0))
		{
			$this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentities("PaymentMode"));
			return -7;
		}*/

		$this->db->begin();

		// Insert into llx_salary
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."salary (fk_user";
		//$sql .= ", datep";
		//$sql .= ", datev";
		$sql .= ", amount";
		$sql .= ", fk_projet";
		$sql .= ", salary";
		$sql .= ", fk_typepayment";
		$sql .= ", fk_account";
		if ($this->note) $sql .= ", note";
		$sql .= ", label";
		$sql .= ", datesp";
		$sql .= ", dateep";
		$sql .= ", fk_user_author";
		$sql .= ", datec";
		$sql .= ", fk_bank";
		$sql .= ", entity";
		$sql .= ") ";
		$sql .= " VALUES (";
		$sql .= "'".$this->db->escape($this->fk_user)."'";
		//$sql .= ", '".$this->db->idate($this->datep)."'";
		//$sql .= ", '".$this->db->idate($this->datev)."'";
		$sql .= ", ".((double) $this->amount);
		$sql .= ", ".($this->fk_project > 0 ? ((int) $this->fk_project) : 0);
		$sql .= ", ".($this->salary > 0 ? ((double) $this->salary) : "null");
		$sql .= ", ".($this->type_payment > 0 ? ((int) $this->type_payment) : 0);
		$sql .= ", ".($this->accountid > 0 ? ((int) $this->accountid) : "null");
		if ($this->note) $sql .= ", '".$this->db->escape($this->note)."'";
		$sql .= ", '".$this->db->escape($this->label)."'";
		$sql .= ", '".$this->db->idate($this->datesp)."'";
		$sql .= ", '".$this->db->idate($this->dateep)."'";
		$sql .= ", '".$this->db->escape($user->id)."'";
		$sql .= ", '".$this->db->idate($now)."'";
		$sql .= ", NULL";
		$sql .= ", ".((int) $conf->entity);
		$sql .= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."salary");

			if ($this->id > 0) {
				// Update extrafield
				if (!$error) {
					if (!$error) {
						$result = $this->insertExtraFields();
						if ($result < 0) {
							$error++;
						}
					}
				}

				// Call trigger
				$result = $this->call_trigger('SALARY_CREATE', $user);
				if ($result < 0) $error++;
				// End call triggers
			} else $error++;

			if (!$error) {
				$this->db->commit();
				return $this->id;
			} else {
				$this->db->rollback();
				return -2;
			}
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -1;
		}
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Update link between payment salary and line generate into llx_bank
	 *
	 *  @param	int		$id_bank    Id bank account
	 *	@return	int					<0 if KO, >0 if OK
	 */
	public function update_fk_bank($id_bank)
	{
        // phpcs:enable
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'salary SET fk_bank = '.((int) $id_bank);
		$sql .= " WHERE rowid = ".((int) $this->id);
		$result = $this->db->query($sql);
		if ($result) {
			return 1;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}


	/**
	 *	Send name clicable (with possibly the picto)
	 *
	 *	@param	int		$withpicto					0=No picto, 1=Include picto into link, 2=Only picto
	 *	@param	string	$option						link option
	 *  @param	int  	$notooltip					1=Disable tooltip
	 *  @param  string  $morecss            		Add more css on link
	 *  @param  int     $save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return	string								Chaine with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $db, $conf, $langs, $hookmanager;
		global $dolibarr_main_authentication, $dolibarr_main_demo;
		global $menumanager;

		if (!empty($conf->dol_no_mouse_hover)) $notooltip = 1; // Force disable tooltips

		$result = '';

		$label = '<u>'.$langs->trans("Salary").'</u>';
		$label .= '<br>';
		$label .= '<b>'.$langs->trans('Ref').':</b> '.$this->ref;
		if ($this->label) {
			$label .= '<br><b>'.$langs->trans('Label').':</b> '.$this->label;
		}
		if ($this->datesp && $this->dateep) {
			$label .= '<br><b>'.$langs->trans('Period').':</b> '.dol_print_date($this->datesp, 'day').' - '.dol_print_date($this->dateep, 'day');
		}
		if (isset($this->amount)) {
			$label .= '<br><b>'.$langs->trans('Amount').':</b> '.price($this->amount);
		}

		$url = DOL_URL_ROOT.'/salaries/card.php?id='.$this->id;

		if ($option != 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values = 1;
			if ($add_save_lastsearch_values) $url .= '&save_lastsearch_values=1';
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$label = $langs->trans("ShowMyObject");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose .= ' class="classfortooltip'.($morecss ? ' '.$morecss : '').'"';

			/*
			 $hookmanager->initHooks(array('myobjectdao'));
			 $parameters=array('id'=>$this->id);
			 $reshook=$hookmanager->executeHooks('getnomurltooltip',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
			 if ($reshook > 0) $linkclose = $hookmanager->resPrint;
			 */
		} else $linkclose = ($morecss ? ' class="'.$morecss.'"' : '');

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) $result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		if ($withpicto != 2) $result .= $this->ref;
		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action, $hookmanager;
		$hookmanager->initHooks(array('salarypayment'));
		$parameters = array('id'=>$this->id, 'getnomurl' => &$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) $result = $hookmanager->resPrint;
		else $result .= $hookmanager->resPrint;

		return $result;
	}

	/**
	 * 	Return amount of payments already done
	 *
	 *	@return		int		Amount of payment already done, <0 if KO
	 */
	public function getSommePaiement()
	{
		$table = 'payment_salary';
		$field = 'fk_salary';

		$sql = 'SELECT sum(amount) as amount';
		$sql .= ' FROM '.MAIN_DB_PREFIX.$table;
		$sql .= " WHERE ".$field." = ".((int) $this->id);

		dol_syslog(get_class($this)."::getSommePaiement", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			$amount = 0;

			$obj = $this->db->fetch_object($resql);
			if ($obj) $amount = $obj->amount ? $obj->amount : 0;

			$this->db->free($resql);
			return $amount;
		} else {
			return -1;
		}
	}

	/**
	 * Information on record
	 *
	 * @param	int		$id      Id of record
	 * @return	void
	 */
	public function info($id)
	{
		$sql = 'SELECT ps.rowid, ps.datec, ps.tms as datem, ps.fk_user_author, ps.fk_user_modif';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'salary as ps';
		$sql .= ' WHERE ps.rowid = '.((int) $id);

		dol_syslog(get_class($this).'::info', LOG_DEBUG);
		$result = $this->db->query($sql);

		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;

				$this->user_creation_id = $obj->fk_user_author;
				$this->user_modification_id = $obj->fk_user_modif;
				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = empty($obj->datem) ? '' : $this->db->jdate($obj->datem);
			}
			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Tag social contribution as payed completely
	 *
	 *    @param    User    $user       Object user making change
	 *    @return   int					<0 if KO, >0 if OK
	 */
	public function set_paid($user)
	{
		// phpcs:enable
		$sql = "UPDATE ".MAIN_DB_PREFIX."salary SET";
		$sql .= " paye = 1";
		$sql .= " WHERE rowid = ".((int) $this->id);
		$return = $this->db->query($sql);
		if ($return) return 1;
		else return -1;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Remove tag payed on social contribution
	 *
	 *    @param	User	$user       Object user making change
	 *    @return	int					<0 if KO, >0 if OK
	 */
	public function set_unpaid($user)
	{
		// phpcs:enable
		$sql = "UPDATE ".MAIN_DB_PREFIX."salary SET";
		$sql .= " paye = 0";
		$sql .= " WHERE rowid = ".((int) $this->id);
		$return = $this->db->query($sql);
		if ($return) return 1;
		else return -1;
	}


	/**
	 * Retourne le libelle du statut d'une facture (brouillon, validee, abandonnee, payee)
	 *
	 * @param	int			$mode       	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 * @param   double		$alreadypaid	0=No payment already done, >0=Some payments were already done (we recommand to put here amount payed if you have it, 1 otherwise)
	 * @return  string						Label
	 */
	public function getLibStatut($mode = 0, $alreadypaid = -1)
	{
		return $this->LibStatut($this->paye, $mode, $alreadypaid);
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Renvoi le libelle d'un statut donne
	 *
	 *  @param	int		$status        	Id status
	 *  @param  int		$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=short label + picto, 6=Long label + picto
	 *  @param  double	$alreadypaid	0=No payment already done, >0=Some payments were already done (we recommand to put here amount payed if you have it, 1 otherwise)
	 *  @return string        			Label
	 */
	public function LibStatut($status, $mode = 0, $alreadypaid = -1)
	{
		// phpcs:enable
		global $langs;

		// Load translation files required by the page
		$langs->loadLangs(array("customers", "bills"));

		// We reinit status array to force to redefine them because label may change according to properties values.
		$this->labelStatus = array();
		$this->labelStatusShort = array();

		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			//$langs->load("mymodule");
			$this->labelStatus[self::STATUS_UNPAID] = $langs->transnoentitiesnoconv('BillStatusNotPaid');
			$this->labelStatus[self::STATUS_PAID] = $langs->transnoentitiesnoconv('BillStatusPaid');
			if ($status == self::STATUS_UNPAID && $alreadypaid <> 0) $this->labelStatus[self::STATUS_UNPAID] = $langs->transnoentitiesnoconv("BillStatusStarted");
			$this->labelStatusShort[self::STATUS_UNPAID] = $langs->transnoentitiesnoconv('BillStatusNotPaid');
			$this->labelStatusShort[self::STATUS_PAID] = $langs->transnoentitiesnoconv('BillStatusPaid');
			if ($status == self::STATUS_UNPAID && $alreadypaid <> 0) $this->labelStatusShort[self::STATUS_UNPAID] = $langs->transnoentitiesnoconv("BillStatusStarted");
		}

		$statusType = 'status1';
		if ($status == 0 && $alreadypaid <> 0) $statusType = 'status3';
		if ($status == 1) $statusType = 'status6';

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}
}
