<?php
/* Copyright (C) 2011-2022  Alexandre Spangaro  <aspangaro@open-dsi.fr>
 * Copyright (C) 2014       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2021       Gauthier VERDOL     <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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

	/**
	 * @var array<string, array<string>>	List of child tables. To test if we can delete object.
	 */
	protected $childtables = array('payment_salary' => array('name' => 'SalaryPayment', 'fk_element' => 'fk_salary'));

	// /**
	//  * @var array    List of child tables. To know object to delete on cascade.
	//  *               If name matches '@ClassNAme:FilePathClass;ParentFkFieldName' it will
	//  *               call method deleteByParentField(parentId, ParentFkFieldName) to fetch and delete child object
	//  */
	//protected $childtablesoncascade = array('mymodule_myobjectdet');


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

	public $type_payment;	// TODO Rename into type_payment_id
	public $type_payment_code;

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
	 * @var int
	 * @see $accountid
	 */
	public $fk_account;

	/**
	 * @var int
	 */
	public $accountid;

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
	 * @var int 1 if salary paid COMPLETELY, 0 otherwise (do not use it anymore, use statut and close_code)
	 * @deprecated
	 */
	public $paye;

	const STATUS_UNPAID = 0;
	const STATUS_PAID = 1;

	public $resteapayer;


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
	 * @return  int         			Return integer <0 if KO, >0 if OK
	 */
	public function update($user = null, $notrigger = 0)
	{
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
			$result = $this->insertExtraFields();
			if ($result < 0) {
				$error++;
			}
		}

		if (!$notrigger) {
			// Call trigger
			$result = $this->call_trigger('SALARY_MODIFY', $user);
			if ($result < 0) {
				$error++;
			}
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
	 *  @return int         		Return integer <0 if KO, >0 if OK
	 */
	public function fetch($id, $user = null)
	{
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
		$sql .= " s.note as note_private,";
		$sql .= " s.note_public,";
		$sql .= " s.paye,";
		$sql .= " s.fk_bank,";
		$sql .= " s.fk_user_author,";
		$sql .= " s.fk_user_modif,";
		$sql .= " s.fk_account,";
		$sql .= " cp.code as type_payment_code";
		$sql .= " FROM ".MAIN_DB_PREFIX."salary as s";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON s.fk_bank = b.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as cp ON s.fk_typepayment = cp.id";
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
				$this->type_payment_code = $obj->type_payment_code;
				$this->label			= $obj->label;
				$this->datesp			= $this->db->jdate($obj->datesp);
				$this->dateep			= $this->db->jdate($obj->dateep);
				$this->note				= $obj->note_private;
				$this->note_private		= $obj->note_private;
				$this->note_public		= $obj->note_public;
				$this->paye 			= $obj->paye;
				$this->status 			= $obj->paye;
				$this->fk_bank          = $obj->fk_bank;
				$this->fk_user_author   = $obj->fk_user_author;
				$this->fk_user_modif    = $obj->fk_user_modif;
				$this->fk_account = $obj->fk_account;
				$this->accountid = $obj->fk_account;

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
	 *  @param  int 	$notrigger 	0=launch triggers after, 1=disable triggers
	 *	@return	int					Return integer <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{
		return $this->deleteCommon($user, $notrigger);
	}


	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *	id must be 0 if object instance is a specimen.
	 *
	 *  @return int
	 */
	public function initAsSpecimen()
	{
		$this->id = 0;

		$this->tms = dol_now();
		$this->fk_user = 0;
		$this->datep = '';
		$this->datev = '';
		$this->amount = '';
		$this->label = '';
		$this->datesp = '';
		$this->dateep = '';
		$this->note = '';
		$this->fk_bank = 0;
		$this->fk_user_author = 0;
		$this->fk_user_modif = 0;

		return 1;
	}

	/**
	 *  Create in database
	 *
	 *  @param      User	$user       User that create
	 *  @return     int      			Return integer <0 if KO, >0 if OK
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
		$this->fk_bank = (int) $this->fk_bank;
		$this->fk_user_author = (int) $this->fk_user_author;
		$this->fk_user_modif = (int) $this->fk_user_modif;
		$this->accountid = (int) $this->accountid;
		$this->paye = (int) $this->paye;

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
		if ($this->note) {
			$sql .= ", note";
		}
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
		$sql .= ", ".((float) $this->amount);
		$sql .= ", ".($this->fk_project > 0 ? ((int) $this->fk_project) : 0);
		$sql .= ", ".($this->salary > 0 ? ((float) $this->salary) : "null");
		$sql .= ", ".($this->type_payment > 0 ? ((int) $this->type_payment) : 0);
		$sql .= ", ".($this->accountid > 0 ? ((int) $this->accountid) : "null");
		if ($this->note) {
			$sql .= ", '".$this->db->escape($this->note)."'";
		}
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
					$result = $this->insertExtraFields();
					if ($result < 0) {
						$error++;
					}
				}

				// Call trigger
				$result = $this->call_trigger('SALARY_CREATE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			} else {
				$error++;
			}

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
	 *	@return	int					Return integer <0 if KO, >0 if OK
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
	 * getTooltipContentArray
	 *
	 * @param array 	$params 		params to construct tooltip data
	 * @since v18
	 * @return array
	 */
	public function getTooltipContentArray($params)
	{
		global $langs;

		$langs->loadLangs(['salaries']);

		// Complete datas
		if (!empty($params['fromajaxtooltip']) && !isset($this->alreadypaid)) {
			// Load the alreadypaid field
			$this->alreadypaid = $this->getSommePaiement(0);
		}

		$datas = [];

		$datas['picto'] = '<u>'.$langs->trans("Salary").'</u>';
		if (isset($this->status) && isset($this->alreadypaid)) {
			$datas['picto'] .= ' '.$this->getLibStatut(5, $this->alreadypaid);
		}
		$datas['ref'] = '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;

		return $datas;
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
		global $conf, $langs, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1;
		} // Force disable tooltips

		$result = '';
		$params = [
			'id' => $this->id,
			'objecttype' => $this->element,
			'option' => $option,
		];
		$classfortooltip = 'classfortooltip';
		$dataparams = '';
		if (getDolGlobalInt('MAIN_ENABLE_AJAX_TOOLTIP')) {
			$classfortooltip = 'classforajaxtooltip';
			$dataparams = ' data-params="'.dol_escape_htmltag(json_encode($params)).'"';
			$label = '';
		} else {
			$label = implode($this->getTooltipContentArray($params));
		}

		$url = DOL_URL_ROOT.'/salaries/card.php?id='.$this->id;

		if ($option != 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && isset($_SERVER["PHP_SELF"]) && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$label = $langs->trans("ShowMyObject");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ($label ? ' title="'.dol_escape_htmltag($label, 1).'"' : ' title="tocomplete"');
			$linkclose .= $dataparams.' class="'.$classfortooltip.($morecss ? ' '.$morecss : '').'"';
		} else {
			$linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
		}

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'"'), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= $this->ref;
		}
		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action, $hookmanager;
		$hookmanager->initHooks(array('salarypayment'));
		$parameters = array('id' => $this->id, 'getnomurl' => &$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}

		return $result;
	}

	/**
	 * 	Return amount of payments already done
	 *
	 *  @param 		int 			$multicurrency 		Return multicurrency_amount instead of amount. -1=Return both.
	 *	@return		float|int|array						Amount of payment already done, <0 and set ->error if KO
	 */
	public function getSommePaiement($multicurrency = 0)
	{
		$table = 'payment_salary';
		$field = 'fk_salary';

		$sql = "SELECT sum(amount) as amount";
		//sum(multicurrency_amount) as multicurrency_amount		// Not yet supported
		$sql .= " FROM ".MAIN_DB_PREFIX.$table;
		$sql .= " WHERE ".$field." = ".((int) $this->id);

		dol_syslog(get_class($this)."::getSommePaiement for salary id=".((int) $this->id), LOG_DEBUG);

		$resql = $this->db->query($sql);

		if ($resql) {
			$obj = $this->db->fetch_object($resql);

			$this->db->free($resql);

			if ($obj) {
				if ($multicurrency < 0) {
					//$this->sumpayed = $obj->amount;
					//$this->sumpayed_multicurrency = $obj->multicurrency_amount;
					//return array('alreadypaid'=>(float) $obj->amount, 'alreadypaid_multicurrency'=>(float) $obj->multicurrency_amount);
					return array();	// Not yet supported
				} elseif ($multicurrency) {
					//$this->sumpayed_multicurrency = $obj->multicurrency_amount;
					//return (float) $obj->multicurrency_amount;
					return -1;		// Not yet supported
				} else {
					//$this->sumpayed = $obj->amount;
					return (float) $obj->amount;
				}
			} else {
				return 0;
			}
		} else {
			$this->error = $this->db->lasterror();
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
	 *    Tag social contribution as paid completely
	 *
	 *	  @deprecated
	 *    @see setPaid()
	 *    @param    User    $user       Object user making change
	 *    @return   int					Return integer <0 if KO, >0 if OK
	 */
	public function set_paid($user)
	{
		// phpcs:enable
		dol_syslog(get_class($this)."::set_paid is deprecated, use setPaid instead", LOG_NOTICE);
		return $this->setPaid($user);
	}

	/**
	 *    Tag social contribution as paid completely
	 *
	 *    @param    User    $user       Object user making change
	 *    @return   int					Return integer <0 if KO, >0 if OK
	 */
	public function setPaid($user)
	{
		$sql = "UPDATE ".MAIN_DB_PREFIX."salary SET";
		$sql .= " paye = 1";
		$sql .= " WHERE rowid = ".((int) $this->id);

		$return = $this->db->query($sql);

		if ($return) {
			$this->paye = 1;
			return 1;
		} else {
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Remove tag paid on social contribution
	 *
	 *    @param	User	$user       Object user making change
	 *    @return	int					Return integer <0 if KO, >0 if OK
	 */
	public function set_unpaid($user)
	{
		// phpcs:enable
		$sql = "UPDATE ".MAIN_DB_PREFIX."salary SET";
		$sql .= " paye = 0";
		$sql .= " WHERE rowid = ".((int) $this->id);

		$return = $this->db->query($sql);

		if ($return) {
			$this->paye = 0;
			return 1;
		} else {
			return -1;
		}
	}


	/**
	 * Return label of current status
	 *
	 * @param	int			$mode       	0=label long, 1=labels short, 2=Picto + Label short, 3=Picto, 4=Picto + Label long, 5=Label short + Picto
	 * @param   double		$alreadypaid	0=No payment already done, >0=Some payments were already done (we recommend to put here amount paid if you have it, 1 otherwise)
	 * @return  string						Label
	 */
	public function getLibStatut($mode = 0, $alreadypaid = -1)
	{
		return $this->LibStatut($this->paye, $mode, $alreadypaid);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return label of a given status
	 *
	 *  @param	int		$status        	Id status
	 *  @param  int		$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=short label + picto, 6=Long label + picto
	 *  @param  double	$alreadypaid	0=No payment already done, >0=Some payments were already done (we recommend to put here amount paid if you have it, 1 otherwise)
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
			if ($status == self::STATUS_UNPAID && $alreadypaid != 0) {
				$this->labelStatus[self::STATUS_UNPAID] = $langs->transnoentitiesnoconv("BillStatusStarted");
			}
			$this->labelStatusShort[self::STATUS_UNPAID] = $langs->transnoentitiesnoconv('BillStatusNotPaid');
			$this->labelStatusShort[self::STATUS_PAID] = $langs->transnoentitiesnoconv('BillStatusPaid');
			if ($status == self::STATUS_UNPAID && $alreadypaid != 0) {
				$this->labelStatusShort[self::STATUS_UNPAID] = $langs->transnoentitiesnoconv("BillStatusStarted");
			}
		}

		$statusType = 'status1';
		if ($status == 0 && $alreadypaid != 0) {
			$statusType = 'status3';
		}
		if ($status == 1) {
			$statusType = 'status6';
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}

	/**
	 *	Return clicable link of object (with eventually picto)
	 *
	 *	@param      string	    $option                 Where point the link (0=> main card, 1,2 => shipment, 'nolink'=>No link)
	 *  @param		array		$arraydata				Array of data
	 *  @return		string								HTML Code for Kanban thumb.
	 */
	public function getKanbanView($option = '', $arraydata = null)
	{
		global $langs;

		$selected = (empty($arraydata['selected']) ? 0 : $arraydata['selected']);

		$return = '<div class="box-flex-item box-flex-grow-zero">';
		$return .= '<div class="info-box info-box-sm">';
		$return .= '<span class="info-box-icon bg-infobox-action">';
		$return .= img_picto('', $this->picto);
		$return .= '</span>';
		$return .= '<div class="info-box-content">';
		$return .= '<span class="info-box-ref inline-block tdoverflowmax150 valignmiddle">'.(method_exists($this, 'getNomUrl') ? $this->getNomUrl(1) : $this->ref).'</span>';
		if ($selected >= 0) {
			$return .= '<input id="cb'.$this->id.'" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="'.$this->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		if (!empty($arraydata['user']) && is_object($arraydata['user'])) {
			$return .= '<br><span class="info-box-label">'.$arraydata['user']->getNomUrl(1, '', 0, 0, 16, 0, '', 'maxwidth100').'</span>';
		}
		if (property_exists($this, 'amount')) {
			$return .= '<br><span class="info-box-label amount">'.price($this->amount).'</span>';
			if (property_exists($this, 'type_payment') && !empty($this->type_payment)) {
				$return .= ' <span class="info-box-label opacitymedium small">';
				if ($langs->trans("PaymentTypeShort".$this->type_payment) != "PaymentTypeShort".$this->type_payment) {
					$return .= $langs->trans("PaymentTypeShort".$this->type_payment);
				} elseif ($langs->trans("PaymentType".$this->type_payment) != "PaymentType".$this->type_payment) {
					$return .= $langs->trans("PaymentType".$this->type_payment);
				}
				$return .= '</span>';
			}
		}
		if (method_exists($this, 'LibStatut')) {
			$return .= '<br><div class="info-box-status">'.$this->getLibStatut(3, isset($this->alreadypaid) ? $this->alreadypaid : $this->totalpaid).'</div>';
		}
		$return .= '</div>';
		$return .= '</div>';
		$return .= '</div>';
		return $return;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Create a withdrawal request for a direct debit order or a credit transfer order.
	 *  Use the remain to pay excluding all existing open direct debit requests.
	 *
	 *	@param      User	$fuser      				User asking the direct debit transfer
	 *  @param		float	$amount						Amount we request direct debit for
	 *  @param		string	$type						'direct-debit' or 'bank-transfer'
	 *  @param		string	$sourcetype					Source ('facture' or 'supplier_invoice')
	 *  @param		int		$checkduplicateamongall		0=Default (check among open requests only to find if request already exists). 1=Check also among requests completely processed and cancel if at least 1 request exists whatever is its status.
	 *	@return     int         						Return integer <0 if KO, 0 if a request already exists, >0 if OK
	 */
	public function demande_prelevement($fuser, $amount = 0, $type = 'direct-debit', $sourcetype = 'salaire', $checkduplicateamongall = 0)
	{
		// phpcs:enable
		global $conf, $mysoc;

		$error = 0;

		dol_syslog(get_class($this)."::demande_prelevement", LOG_DEBUG);
		if ($this->paye == 0) {
			require_once DOL_DOCUMENT_ROOT.'/societe/class/companybankaccount.class.php';
			$bac = new CompanyBankAccount($this->db);
			// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
			$bac->fetch(0, '', $mysoc->id);

			$sql = "SELECT count(rowid) as nb";
			$sql .= " FROM ".$this->db->prefix()."prelevement_demande";
			if ($type == 'salaire') {
				$sql .= " WHERE fk_salary = ".((int) $this->id);
			} else {
				$sql .= " WHERE fk_facture = ".((int) $this->id);
			}
			$sql .= " AND type = 'ban'"; // To exclude record done for some online payments
			if (empty($checkduplicateamongall)) {
				$sql .= " AND traite = 0";
			}

			dol_syslog(get_class($this)."::demande_prelevement", LOG_DEBUG);

			$resql = $this->db->query($sql);
			if ($resql) {
				$obj = $this->db->fetch_object($resql);
				if ($obj && $obj->nb == 0) {	// If no request found yet
					$now = dol_now();

					$totalpaid = $this->getSommePaiement();
					// $totalcreditnotes = $this->getSumCreditNotesUsed();
					// $totaldeposits = $this->getSumDepositsUsed();
					//print "totalpaid=".$totalpaid." totalcreditnotes=".$totalcreditnotes." totaldeposts=".$totaldeposits;

					// We can also use bcadd to avoid pb with floating points
					// For example print 239.2 - 229.3 - 9.9; does not return 0.
					//$resteapayer=bcadd($this->total_ttc,$totalpaid,$conf->global->MAIN_MAX_DECIMALS_TOT);
					//$resteapayer=bcadd($resteapayer,$totalavoir,$conf->global->MAIN_MAX_DECIMALS_TOT);
					// if (empty($amount)) {
					// 	$amount = price2num($this->total_ttc - $totalpaid - $totalcreditnotes - $totaldeposits, 'MT');
					// }

					if (is_numeric($amount) && $amount != 0) {
						$sql = 'INSERT INTO '.$this->db->prefix().'prelevement_demande(';
						if ($type == 'salaire') {
							$sql .= 'fk_salary, ';
						} else {
							$sql .= 'fk_facture, ';
						}
						$sql .= ' amount, date_demande, fk_user_demande, code_banque, code_guichet, number, cle_rib, sourcetype, type, entity)';
						$sql .= " VALUES (".((int) $this->id);
						$sql .= ", ".((float) price2num($amount));
						$sql .= ", '".$this->db->idate($now)."'";
						$sql .= ", ".((int) $fuser->id);
						$sql .= ", '".$this->db->escape($bac->code_banque)."'";
						$sql .= ", '".$this->db->escape($bac->code_guichet)."'";
						$sql .= ", '".$this->db->escape($bac->number)."'";
						$sql .= ", '".$this->db->escape($bac->cle_rib)."'";
						$sql .= ", '".$this->db->escape($sourcetype)."'";
						$sql .= ", 'ban'";
						$sql .= ", ".((int) $conf->entity);
						$sql .= ")";

						dol_syslog(get_class($this)."::demande_prelevement", LOG_DEBUG);
						$resql = $this->db->query($sql);
						if (!$resql) {
							$this->error = $this->db->lasterror();
							dol_syslog(get_class($this).'::demandeprelevement Erreur');
							$error++;
						}
					} else {
						$this->error = 'WithdrawRequestErrorNilAmount';
						dol_syslog(get_class($this).'::demandeprelevement WithdrawRequestErrorNilAmount');
						$error++;
					}

					if (!$error) {
						// Force payment mode of invoice to withdraw
						$payment_mode_id = dol_getIdFromCode($this->db, ($type == 'bank-transfer' ? 'VIR' : 'PRE'), 'c_paiement', 'code', 'id', 1);
						if ($payment_mode_id > 0) {
							$result = $this->setPaymentMethods($payment_mode_id);
						}
					}

					if ($error) {
						return -1;
					}
					return 1;
				} else {
					$this->error = "A request already exists";
					dol_syslog(get_class($this).'::demandeprelevement Can t create a request to generate a direct debit, a request already exists.');
					return 0;
				}
			} else {
				$this->error = $this->db->error();
				dol_syslog(get_class($this).'::demandeprelevement Error -2');
				return -2;
			}
		} else {
			$this->error = "Status of invoice does not allow this";
			dol_syslog(get_class($this)."::demandeprelevement ".$this->error." $this->status, $this->paye, $this->mode_reglement_id");
			return -3;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Remove a direct debit request or a credit transfer request
	 *
	 *  @param  User	$fuser      User making delete
	 *  @param  int		$did        ID of request to delete
	 *  @return	int					Return integer <0 if OK, >0 if KO
	 */
	public function demande_prelevement_delete($fuser, $did)
	{
		// phpcs:enable
		$sql = 'DELETE FROM '.$this->db->prefix().'prelevement_demande';
		$sql .= ' WHERE rowid = '.((int) $did);
		$sql .= ' AND traite = 0';
		if ($this->db->query($sql)) {
			return 0;
		} else {
			$this->error = $this->db->lasterror();
			dol_syslog(get_class($this).'::demande_prelevement_delete Error '.$this->error);
			return -1;
		}
	}
}
