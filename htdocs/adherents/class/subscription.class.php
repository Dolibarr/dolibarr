<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *		\file 		htdocs/adherents/class/subscription.class.php
 *		\ingroup	member
 *		\brief		File of class to manage subscriptions of foundation members
 */

//namespace DolibarrMember;

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**
 *	Class to manage subscriptions of foundation members
 */
class Subscription extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'subscription';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'subscription';

	/**
	 * @var int  Does myobject support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by fk_soc, 'field@table'=Test with link by field@table
	 */
	public $ismultientitymanaged = 'fk_adherent@adherent';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'payment';

	/**
	 * Date creation record (datec)
	 *
	 * @var integer
	 */
	public $datec;

	/**
	 * Date modification record (tms)
	 *
	 * @var integer
	 */
	public $datem;

	/**
	 * Subscription start date (date subscription)
	 *
	 * @var integer
	 */
	public $dateh;

	/**
	 * Subscription end date
	 *
	 * @var integer
	 */
	public $datef;

	/**
	 * @var int ID
	 */
	public $fk_type;

	/**
	 * @var int Member ID
	 */
	public $fk_adherent;

	/**
	 * @var double amount subscription
	 */
	public $amount;

	/**
	 * @var int ID
	 */
	public $fk_bank;

	public $fields = array(
		'rowid' =>array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>10),
		'tms' =>array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>15),
		'datec' =>array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>1, 'visible'=>-1, 'position'=>20),
		'fk_adherent' =>array('type'=>'integer', 'label'=>'Member', 'enabled'=>1, 'visible'=>-1, 'position'=>25),
		'dateadh' =>array('type'=>'datetime', 'label'=>'DateSubscription', 'enabled'=>1, 'visible'=>-1, 'position'=>30),
		'datef' =>array('type'=>'datetime', 'label'=>'DateEndSubscription', 'enabled'=>1, 'visible'=>-1, 'position'=>35),
		'subscription' =>array('type'=>'double(24,8)', 'label'=>'Amount', 'enabled'=>1, 'visible'=>-1, 'position'=>40, 'isameasure'=>1),
		'fk_bank' =>array('type'=>'integer', 'label'=>'BankId', 'enabled'=>1, 'visible'=>-1, 'position'=>45),
		'note' =>array('type'=>'text', 'label'=>'Note', 'enabled'=>1, 'visible'=>-1, 'position'=>50),
		'fk_type' =>array('type'=>'integer', 'label'=>'MemberType', 'enabled'=>1, 'visible'=>-1, 'position'=>55),
		'fk_user_creat' =>array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>1, 'visible'=>-2, 'position'=>60),
		'fk_user_valid' =>array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserValidation', 'enabled'=>1, 'visible'=>-1, 'position'=>65),
	);


	/**
	 *	Constructor
	 *
	 *	@param 		DoliDB		$db		Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 *	Function who permitted cretaion of the subscription
	 *
	 *	@param	User	$user			User that create
	 *	@param  bool 	$notrigger 		false=launch triggers after, true=disable triggers
	 *	@return	int						<0 if KO, Id subscription created if OK
	 */
	public function create($user, $notrigger = false)
	{
		global $langs;

		$error = 0;

		$now = dol_now();

		// Check parameters
		if ($this->datef <= $this->dateh) {
			$this->error = $langs->trans("ErrorBadValueForDate");
			return -1;
		}
		if (empty($this->datec)) {
			$this->datec = $now;
		}


		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."subscription (fk_adherent, fk_type, datec, dateadh, datef, subscription, note)";

		require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
		$member = new Adherent($this->db);
		$result = $member->fetch($this->fk_adherent);

		if ($this->fk_type == null) {	// If type not defined, we use the type of member
			$type = $member->typeid;
		} else {
			$type = $this->fk_type;
		}
		$sql .= " VALUES (".((int) $this->fk_adherent).", '".$this->db->escape($type)."', '".$this->db->idate($now)."',";
		$sql .= " '".$this->db->idate($this->dateh)."',";
		$sql .= " '".$this->db->idate($this->datef)."',";
		$sql .= " ".((float) $this->amount).",";
		$sql .= " '".$this->db->escape($this->note_public ? $this->note_public : $this->note)."')";

		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = $this->db->lasterror();
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);
			$this->fk_type = $type;
		}

		if (!$error && !$notrigger) {
			$this->context = array('member' => $member);
			// Call triggers
			$result = $this->call_trigger('MEMBER_SUBSCRIPTION_CREATE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();
			return -1;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}


	/**
	 *  Method to load a subscription
	 *
	 *  @param	int		$rowid		Id subscription
	 *  @return	int					<0 if KO, =0 if not found, >0 if OK
	 */
	public function fetch($rowid)
	{
		$sql = "SELECT rowid, fk_type, fk_adherent, datec,";
		$sql .= " tms,";
		$sql .= " dateadh as dateh,";
		$sql .= " datef,";
		$sql .= " subscription, note, fk_bank";
		$sql .= " FROM ".MAIN_DB_PREFIX."subscription";
		$sql .= "	WHERE rowid=".((int) $rowid);

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id             = $obj->rowid;
				$this->ref            = $obj->rowid;

				$this->fk_type        = $obj->fk_type;
				$this->fk_adherent    = $obj->fk_adherent;
				$this->datec          = $this->db->jdate($obj->datec);
				$this->datem          = $this->db->jdate($obj->tms);
				$this->dateh          = $this->db->jdate($obj->dateh);
				$this->datef          = $this->db->jdate($obj->datef);
				$this->amount         = $obj->subscription;
				$this->note           = $obj->note;
				$this->fk_bank        = $obj->fk_bank;
				return 1;
			} else {
				return 0;
			}
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}


	/**
	 *	Update subscription
	 *
	 *	@param	User	$user			User who updated
	 *	@param 	int		$notrigger		0=Disable triggers
	 *	@return	int						<0 if KO, >0 if OK
	 */
	public function update($user, $notrigger = 0)
	{
		$error = 0;

		$this->db->begin();

		if (!is_numeric($this->amount)) {
			$this->error = 'BadValueForParameterAmount';
			return -1;
		}

		$sql = "UPDATE ".MAIN_DB_PREFIX."subscription SET ";
		$sql .= " fk_type = ".((int) $this->fk_type).",";
		$sql .= " fk_adherent = ".((int) $this->fk_adherent).",";
		$sql .= " note=".($this->note ? "'".$this->db->escape($this->note)."'" : 'null').",";
		$sql .= " subscription = ".price2num($this->amount).",";
		$sql .= " dateadh='".$this->db->idate($this->dateh)."',";
		$sql .= " datef='".$this->db->idate($this->datef)."',";
		$sql .= " datec='".$this->db->idate($this->datec)."',";
		$sql .= " fk_bank = ".($this->fk_bank ? ((int) $this->fk_bank) : 'null');
		$sql .= " WHERE rowid = ".$this->id;

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
			$member = new Adherent($this->db);
			$result = $member->fetch($this->fk_adherent);
			$result = $member->update_end_date($user);

			if (!$error && !$notrigger) {
				$this->context = array('member'=>$member);
				// Call triggers
				$result = $this->call_trigger('MEMBER_SUBSCRIPTION_MODIFY', $user);
				if ($result < 0) {
					$error++;
				} //Do also here what you must do to rollback action if trigger fail
				// End call triggers
			}
		} else {
			$error++;
			$this->error = $this->db->lasterror();
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();
			return -1;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}

	/**
	 *	Delete a subscription
	 *
	 *	@param	User	$user		User that delete
	 *	@param 	bool 	$notrigger  false=launch triggers after, true=disable triggers
	 *	@return	int					<0 if KO, 0 if not found, >0 if OK
	 */
	public function delete($user, $notrigger = false)
	{
		$error = 0;

		// It subscription is linked to a bank transaction, we get it
		if ($this->fk_bank > 0) {
			require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
			$accountline = new AccountLine($this->db);
			$result = $accountline->fetch($this->fk_bank);
		}

		$this->db->begin();

		if (!$error) {
			if (!$notrigger) {
				// Call triggers
				$result = $this->call_trigger('MEMBER_SUBSCRIPTION_DELETE', $user);
				if ($result < 0) {
					$error++;
				} // Do also here what you must do to rollback action if trigger fail
				// End call triggers
			}
		}

		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."subscription WHERE rowid = ".((int) $this->id);
			dol_syslog(get_class($this)."::delete", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$num = $this->db->affected_rows($resql);
				if ($num) {
					require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
					$member = new Adherent($this->db);
					$result = $member->fetch($this->fk_adherent);
					$result = $member->update_end_date($user);

					if ($this->fk_bank > 0 && is_object($accountline) && $accountline->id > 0) {	// If we found bank account line (this means this->fk_bank defined)
						$result = $accountline->delete($user); // Return false if refused because line is conciliated
						if ($result > 0) {
							$this->db->commit();
							return 1;
						} else {
							$this->error = $accountline->error;
							$this->db->rollback();
							return -1;
						}
					} else {
						$this->db->commit();
						return 1;
					}
				} else {
					$this->db->commit();
					return 0;
				}
			} else {
				$error++;
				$this->error = $this->db->lasterror();
			}
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();
			return -1;
		} else {
			$this->db->commit();
			return 1;
		}
	}


	/**
	 *  Return clicable name (with picto eventually)
	 *
	 *	@param	int		$withpicto					0=No picto, 1=Include picto into link, 2=Only picto
	 *  @param	int  	$notooltip					1=Disable tooltip
	 *	@param	string	$option						Page for link ('', 'nolink', ...)
	 *  @param  string  $morecss        			Add more css on link
	 *  @param  int     $save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return	string								Chaine avec URL
	 */
	public function getNomUrl($withpicto = 0, $notooltip = 0, $option = '', $morecss = '', $save_lastsearch_value = -1)
	{
		global $langs;

		$result = '';

		$langs->load("members");

		$label = img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("Subscription").'</u>';
		/*if (isset($this->statut)) {
			$label .= ' '.$this->getLibStatut(5);
		}*/
		$label .= '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;
		if (!empty($this->dateh)) {
			$label .= '<br><b>'.$langs->trans('DateStart').':</b> '.dol_print_date($this->dateh, 'day');
		}
		if (!empty($this->datef)) {
			$label .= '<br><b>'.$langs->trans('DateEnd').':</b> '.dol_print_date($this->datef, 'day');
		}

		$url = DOL_URL_ROOT.'/adherents/subscription/card.php?rowid='.((int) $this->id);

		if ($option != 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		$linkstart = '<a href="'.$url.'" class="classfortooltip" title="'.dol_escape_htmltag($label, 1).'">';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= $this->ref;
		}
		$result .= $linkend;

		return $result;
	}


	/**
	 *  Retourne le libelle du statut d'une adhesion
	 *
	 *  @param	int		$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return string				Label
	 */
	public function getLibStatut($mode = 0)
	{
		return '';
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Renvoi le libelle d'un statut donne
	 *
	 *  @param	int			$status      			Id status
	 *  @return string      						Label
	 */
	public function LibStatut($status)
	{
		// phpcs:enable
		global $langs;
		$langs->load("members");
		return '';
	}

	/**
	 *  Load information of the subscription object
	 *
	 *  @param	int		$id       Id subscription
	 *  @return	void
	 */
	public function info($id)
	{
		$sql = 'SELECT c.rowid, c.datec,';
		$sql .= ' c.tms as datem';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'subscription as c';
		$sql .= ' WHERE c.rowid = '.((int) $id);

		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;

				$this->date_creation = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
			}

			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}
}
