<?php
/* Copyright (C) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2016 Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2018 Andreu Bisquerra     <jove@bisquerra.com>
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
 * \file       cashcontrol/class/cashcontrol.class.php
 * \ingroup    cashdesk|takepos
 * \brief      This file is CRUD class file (Create/Read/Update/Delete) for cash fence table
 */

/**
 *    Class to manage cash fence
 */
class CashControl extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'cashcontrol';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'pos_cash_fence';

	/**
	 * @var int  Does pos_cash_fence support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 */
	public $ismultientitymanaged = 1;

	/**
	 * @var int  Does pos_cash_fence support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 0;

	/**
	 * @var string String with name of icon for pos_cash_fence. Must be the part after the 'object_' into object_pos_cash_fence.png
	 */
	public $picto = 'account';

	public $fields=array(
	'rowid' =>array('type'=>'integer', 'label'=>'ID', 'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'position'=>10),
	'entity' =>array('type'=>'integer', 'label'=>'Entity', 'enabled'=>1, 'visible'=>0, 'notnull'=>1, 'position'=>15),
	'ref' =>array('type'=>'varchar(64)', 'label'=>'Ref', 'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'position'=>18),
	'posmodule' =>array('type'=>'varchar(30)', 'label'=>'Module', 'enabled'=>1, 'visible'=>1, 'notnul'=>1, 'position'=>19),
	'posnumber' =>array('type'=>'varchar(30)', 'label'=>'CashDesk', 'enabled'=>1, 'visible'=>1, 'notnul'=>1, 'position'=>20),
	'label' =>array('type'=>'varchar(255)', 'label'=>'Label', 'enabled'=>1, 'visible'=>0, 'position'=>24),
	'opening' =>array('type'=>'double(24,8)', 'label'=>'Opening', 'enabled'=>1, 'visible'=>1, 'position'=>25),
	'cash' =>array('type'=>'double(24,8)', 'label'=>'Cash', 'enabled'=>1, 'visible'=>1, 'position'=>30),
	'cheque' =>array('type'=>'double(24,8)', 'label'=>'Cheque', 'enabled'=>1, 'visible'=>1, 'position'=>33),
	'card' =>array('type'=>'double(24,8)', 'label'=>'CreditCard', 'enabled'=>1, 'visible'=>1, 'position'=>36),
	'year_close' =>array('type'=>'integer', 'label'=>'Year close', 'enabled'=>1, 'visible'=>1, 'notnul'=>1, 'position'=>50),
	'month_close' =>array('type'=>'integer', 'label'=>'Month close', 'enabled'=>1, 'visible'=>1, 'position'=>55),
	'day_close' =>array('type'=>'integer', 'label'=>'Day close', 'enabled'=>1, 'visible'=>1, 'position'=>60),
	'date_valid' =>array('type'=>'datetime', 'label'=>'DateValid', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>490),
	'date_creation' =>array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>500),
	'tms' =>array('type'=>'timestamp', 'label'=>'Tms', 'enabled'=>1, 'visible'=>0, 'notnull'=>1, 'position'=>505),
	'import_key' =>array('type'=>'varchar(14)', 'label'=>'Import key', 'enabled'=>1, 'visible'=>0, 'position'=>510),
	'status' => array('type'=>'integer', 'label'=>'Status', 'enabled'=>1, 'visible'=>1, 'position'=>1000, 'notnull'=>1, 'index'=>1, 'arrayofkeyval'=>array('0'=>'Brouillon', '1'=>'Validated')),
	);

	public $id;
	public $opening;
	public $status;
	public $year_close;
	public $month_close;
	public $day_close;
	public $posmodule;
	public $posnumber;
	public $cash;
	public $cheque;
	public $card;
	public $date_valid;
	public $date_creation;
	public $date_modification;

	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;


	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}


	/**
	 *  Create in database
	 *
	 * @param  User $user User that create
	 * @param  int $notrigger 0=launch triggers after, 1=disable triggers
	 * @return int <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = 0)
	{
		global $conf;

		$error = 0;

		// Clean data
		if (empty($this->cash)) $this->cash=0;
		if (empty($this->cheque)) $this->cheque=0;
		if (empty($this->card)) $this->card=0;

		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."pos_cash_fence (";
		$sql .= "entity";
		//$sql .= ", ref";
		$sql .= ", opening";
		$sql .= ", status";
		$sql .= ", date_creation";
		$sql .= ", posmodule";
		$sql .= ", posnumber";
		$sql .= ", day_close";
		$sql .= ", month_close";
		$sql .= ", year_close";
		$sql .= ", cash";
		$sql .= ", cheque";
		$sql .= ", card";
		$sql .= ") VALUES (";
		//$sql .= "'(PROV)', ";
		$sql .= $conf->entity;
		$sql .= ", ".(is_numeric($this->opening) ? $this->opening : 0);
		$sql .= ", 0";										// Draft by default
		$sql .= ", '".$this->db->idate(dol_now())."'";
		$sql .= ", '".$this->db->escape($this->posmodule)."'";
		$sql .= ", '".$this->db->escape($this->posnumber)."'";
		$sql .= ", ".($this->day_close > 0 ? $this->day_close : "null");
		$sql .= ", ".($this->month_close > 0 ? $this->month_close : "null");
		$sql .= ", ".$this->year_close;
		$sql .= ", ".$this->cash;
		$sql .= ", ".$this->cheque;
		$sql .= ", ".$this->card;
		$sql .= ")";

		$this->db->begin();

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."pos_cash_fence");

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'pos_cash_fence SET ref = rowid where rowid = '.$this->id;
			$this->db->query($sql);
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}

	/**
	 * Validate cash fence
	 *
	 * @param 	User 		$user		User
	 * @param 	number 		$notrigger	No trigger
	 * @return 	int						<0 if KO, >0 if OK
	 */
	public function valid(User $user, $notrigger = 0)
	{
		global $conf,$langs;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status == self::STATUS_VALIDATED)
		{
			dol_syslog(get_class($this)."::valid action abandonned: already validated", LOG_WARNING);
			return 0;
		}

		/*
		 $posmodule = $this->posmodule;
		 if (! empty($user->rights->$posmodule->use))
		 {
		 $this->error='NotEnoughPermissions';
		 dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
		 return -1;
		 }
		 */

		$now=dol_now();

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."pos_cash_fence";
		$sql.= " SET status = ".self::STATUS_VALIDATED.",";
		$sql.= " date_valid='".$this->db->idate($now)."',";
		$sql.= " fk_user_valid = ".$user->id;
		$sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::close", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			$this->status = self::STATUS_VALIDATED;
			$this->date_valid = $now;
			$this->fk_user_valid = $user->id;
		}

		if (! $error && ! $notrigger)
		{
			// Call trigger
			$result=$this->call_trigger('CASHCONTROL_VALIDATE', $user);
			if ($result < 0) $error++;
			// End call triggers
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
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
	 * Delete object in database
	 *
	 * @param User $user       User that deletes
	 * @param bool $notrigger  false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
	    return $this->deleteCommon($user, $notrigger);
	    //return $this->deleteCommon($user, $notrigger, 1);
	}

	/**
	 *  Return label of the status
	 *
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the status
	 *
	 *  @param	int		$status        Id status
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string 			       Label of status
	 */
	public function LibStatut($status, $mode=0)
	{
		// phpcs:enable
		if (empty($this->labelstatus))
		{
			global $langs;
			//$langs->load("mymodule");
			$this->labelstatus[0] = $langs->trans('Draft');
			$this->labelstatus[1] = $langs->trans('Closed');
		}

		if ($mode == 0)
		{
			return $this->labelstatus[$status];
		}
		elseif ($mode == 1)
		{
			return $this->labelstatus[$status];
		}
		elseif ($mode == 2)
		{
			if ($status == 1) return img_picto($this->labelstatus[$status],'statut6', '', false, 0, 0, '', 'valignmiddle').' '.$this->labelstatus[$status];
			elseif ($status == 0) return img_picto($this->labelstatus[$status],'statut0', '', false, 0, 0, '', 'valignmiddle').' '.$this->labelstatus[$status];
		}
		elseif ($mode == 3)
		{
			if ($status == 1) return img_picto($this->labelstatus[$status],'statut6', '', false, 0, 0, '', 'valignmiddle');
			elseif ($status == 0) return img_picto($this->labelstatus[$status],'statut0', '', false, 0, 0, '', 'valignmiddle');
		}
		elseif ($mode == 4)
		{
			if ($status == 1) return img_picto($this->labelstatus[$status],'statut6', '', false, 0, 0, '', 'valignmiddle').' '.$this->labelstatus[$status];
			elseif ($status == 0) return img_picto($this->labelstatus[$status],'statut0', '', false, 0, 0, '', 'valignmiddle').' '.$this->labelstatus[$status];
		}
		elseif ($mode == 5)
		{
			if ($status == 1) return $this->labelstatus[$status].' '.img_picto($this->labelstatus[$status],'statut6', '', false, 0, 0, '', 'valignmiddle');
			elseif ($status == 0) return $this->labelstatus[$status].' '.img_picto($this->labelstatus[$status],'statut0', '', false, 0, 0, '', 'valignmiddle');
		}
		elseif ($mode == 6)
		{
			if ($status == 1) return $this->labelstatus[$status].' '.img_picto($this->labelstatus[$status],'statut6', '', false, 0, 0, '', 'valignmiddle');
			elseif ($status == 0) return $this->labelstatus[$status].' '.img_picto($this->labelstatus[$status],'statut0', '', false, 0, 0, '', 'valignmiddle');
		}
	}

	/**
	 *    Return clicable link of object (with eventually picto)
	 *
	 * @param  int    $withpicto             Add picto into link
	 * @param  string $option                Where point the link ('stock', 'composition', 'category', 'supplier', '')
	 * @param  int    $maxlength             Maxlength of ref
	 * @param  int    $save_lastsearch_value -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 * @param  int    $notooltip			 No tooltip
	 * @return string                                String with URL
	 */
	public function getNomUrl($withpicto=0, $option='', $maxlength=0, $save_lastsearch_value=-1, $notooltip=0)
	{
		global $conf, $langs, $hookmanager;
		include_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';

		$result='';
		$newref=($this->ref?$this->ref:$this->id);
		if ($maxlength) { $newref=dol_trunc($newref, $maxlength, 'middle'); }

		$label = '<u>' . $langs->trans("ShowCashFence") . '</u>';
		$label .= '<br><b>' . $langs->trans('ProductRef') . ':</b> ' . ($this->ref?$this->ref:$this->id);

		$linkclose='';
		if (empty($notooltip)) {
			if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$label=$langs->trans("ShowCashFence");
				$linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
			}

			$linkclose.= ' title="'.dol_escape_htmltag($label, 1, 1).'"';
			$linkclose.= ' class="classfortooltip"';

			/*
			 $hookmanager->initHooks(array('productdao'));
			 $parameters=array('id'=>$this->id);
			 $reshook=$hookmanager->executeHooks('getnomurltooltip',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
			 if ($reshook > 0) $linkclose = $hookmanager->resPrint;
			 */
		}

		$url = DOL_URL_ROOT.'/compta/cashcontrol/cashcontrol_card.php?id='.$this->id;

		if ($option !== 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values=($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) { $add_save_lastsearch_values=1;
			}
			if ($add_save_lastsearch_values) { $url.='&save_lastsearch_values=1';
			}
		}

		$linkstart = '<a href="'.$url.'"';
		$linkstart.=$linkclose.'>';
		$linkend='</a>';

		$result.=$linkstart;
		if ($withpicto) {
			$result.=(img_object(($notooltip?'':$label), 'bank', ($notooltip?'class="paddingright"':'class="paddingright classfortooltip"'), 0, 0, $notooltip?0:1));
		}
		$result.= $newref;
		$result.= $linkend;

		global $action;
		$hookmanager->initHooks(array('cashfencedao'));
		$parameters=array('id'=>$this->id, 'getnomurl'=>$result);
		$reshook=$hookmanager->executeHooks('getNomUrl', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) { $result = $hookmanager->resPrint;
		} else { $result .= $hookmanager->resPrint;
		}

		return $result;
	}
}