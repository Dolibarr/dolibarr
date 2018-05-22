<?php
/* Copyright (C) 2017	Florian HENRY <florian.henry@atm-consulting.fr>
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
 *      \file       htdocs/loan/class/loanschedule.class.php
 *		\ingroup    facture
 *		\brief      File of class to manage schedule of loans
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**
 *		Class to manage Schedule of loans
 */
class LoanSchedule extends CommonObject
{
	public $element='loan_schedule';			//!< Id that identify managed objects
	public $table_element='loan_schedule';	//!< Name of table without prefix where object is stored

	var $fk_loan;
	var $datec='';
	var $tms='';
	var $datep='';
    var $amounts=array();   // Array of amounts
    var $amount_capital;    // Total amount of payment
	var $amount_insurance;
	var $amount_interest;
	var $fk_typepayment;
	var $num_payment;
	var $fk_bank;
	var $fk_user_creat;
	var $fk_user_modif;
	var $lines=array();

	/**
	 * @deprecated
	 * @see amount, amounts
	 */
	var $total;

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 *  Create payment of loan into database.
     *  Use this->amounts to have list of lines for the payment
     *
	 *  @param      User		$user   User making payment
	 *  @return     int     			<0 if KO, id of payment if OK
	 */
	function create($user)
	{
		global $conf, $langs;

		$error=0;

        $now=dol_now();

        // Validate parameters
		if (! $this->datepaid)
		{
			$this->error='ErrorBadValueForParameter';
			return -1;
		}

		// Clean parameters
		if (isset($this->fk_loan)) 			$this->fk_loan = trim($this->fk_loan);
		if (isset($this->amount_capital))	$this->amount_capital = trim($this->amount_capital?$this->amount_capital:0);
		if (isset($this->amount_insurance))	$this->amount_insurance = trim($this->amount_insurance?$this->amount_insurance:0);
		if (isset($this->amount_interest))	$this->amount_interest = trim($this->amount_interest?$this->amount_interest:0);
		if (isset($this->fk_typepayment))	$this->fk_typepayment = trim($this->fk_typepayment);
		if (isset($this->fk_bank))			$this->fk_bank = trim($this->fk_bank);
		if (isset($this->fk_user_creat))	$this->fk_user_creat = trim($this->fk_user_creat);
		if (isset($this->fk_user_modif))	$this->fk_user_modif = trim($this->fk_user_modif);

        $totalamount = $this->amount_capital + $this->amount_insurance + $this->amount_interest;
        $totalamount = price2num($totalamount);

        // Check parameters
        if ($totalamount == 0) {
        	$this->errors[]='step1';
        	return -1; // Negative amounts are accepted for reject prelevement but not null
        }


		$this->db->begin();

		if ($totalamount != 0)
		{
			$sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element." (fk_loan, datec, datep, amount_capital, amount_insurance, amount_interest,";
			$sql.= " fk_typepayment, fk_user_creat, fk_bank)";
			$sql.= " VALUES (".$this->fk_loan.", '".$this->db->idate($now)."',";
			$sql.= " '".$this->db->idate($this->datepaid)."',";
			$sql.= " ".$this->amount_capital.",";
			$sql.= " ".$this->amount_insurance.",";
			$sql.= " ".$this->amount_interest.",";
			$sql.= " ".$this->fk_typepayment.", ";
			$sql.= " ".$user->id.",";
			$sql.= " ".$this->fk_bank . ")";

			dol_syslog(get_class($this)."::create", LOG_DEBUG);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."payment_loan");
			}
			else
			{
                $this->error=$this->db->lasterror();
				$error++;
			}

		}

		if ($totalamount != 0 && ! $error)
		{
		    $this->amount_capital=$totalamount;
            $this->total=$totalamount;    // deprecated
		    $this->db->commit();
			return $this->id;
		}
		else
		{
			$this->errors[]=$this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *  Load object in memory from database
	 *
	 *  @param	int		$id         Id object
	 *  @return int         		<0 if KO, >0 if OK
	 */
	function fetch($id)
	{
		global $langs;
		$sql = "SELECT";
		$sql.= " t.rowid,";
		$sql.= " t.fk_loan,";
		$sql.= " t.datec,";
		$sql.= " t.tms,";
		$sql.= " t.datep,";
		$sql.= " t.amount_capital,";
		$sql.= " t.amount_insurance,";
		$sql.= " t.amount_interest,";
		$sql.= " t.fk_typepayment,";
		$sql.= " t.num_payment,";
        $sql.= " t.note_private,";
        $sql.= " t.note_public,";
		$sql.= " t.fk_bank,";
		$sql.= " t.fk_user_creat,";
		$sql.= " t.fk_user_modif,";
		$sql.= " pt.code as type_code, pt.libelle as type_libelle,";
		$sql.= ' b.fk_account';
		$sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as pt ON t.fk_typepayment = pt.id";
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON t.fk_bank = b.rowid';
		$sql.= " WHERE t.rowid = ".$id;

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
	{
		if ($this->db->num_rows($resql))
		{
			$obj = $this->db->fetch_object($resql);

			$this->id = $obj->rowid;
			$this->ref = $obj->rowid;

			$this->fk_loan = $obj->fk_loan;
			$this->datec = $this->db->jdate($obj->datec);
			$this->tms = $this->db->jdate($obj->tms);
			$this->datep = $this->db->jdate($obj->datep);
			$this->amount_capital = $obj->amount_capital;
			$this->amount_insurance = $obj->amount_insurance;
			$this->amount_interest = $obj->amount_interest;
			$this->fk_typepayment = $obj->fk_typepayment;
			$this->num_payment = $obj->num_payment;
			$this->note_private = $obj->note_private;
			$this->note_public = $obj->note_public;
			$this->fk_bank = $obj->fk_bank;
			$this->fk_user_creat = $obj->fk_user_creat;
			$this->fk_user_modif = $obj->fk_user_modif;

			$this->type_code = $obj->type_code;
			$this->type_libelle = $obj->type_libelle;

			$this->bank_account   = $obj->fk_account;
			$this->bank_line      = $obj->fk_bank;
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
	 *  Update database
	 *
	 *  @param	User	$user        	User that modify
	 *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *  @return int         			<0 if KO, >0 if OK
	 */
	function update($user=0, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;

		// Clean parameters
		if (isset($this->fk_loan)) $this->fk_loan=trim($this->fk_loan);
		if (isset($this->amount_capital)) $this->amount_capital=trim($this->amount_capital);
		if (isset($this->amount_insurance)) $this->amount_insurance=trim($this->amount_insurance);
		if (isset($this->amount_interest)) $this->amount_interest=trim($this->amount_interest);
		if (isset($this->fk_typepayment)) $this->fk_typepayment=trim($this->fk_typepayment);
		if (isset($this->num_payment)) $this->num_payment=trim($this->num_payment);
		if (isset($this->note_private)) $this->note_private=trim($this->note_private);
		if (isset($this->note_public)) $this->note_public=trim($this->note_public);
		if (isset($this->fk_bank)) $this->fk_bank=trim($this->fk_bank);
		if (isset($this->fk_user_creat)) $this->fk_user_creat=trim($this->fk_user_creat);
		if (isset($this->fk_user_modif)) $this->fk_user_modif=trim($this->fk_user_modif);

		// Check parameters
		// Put here code to add control on parameters values

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";

		$sql.= " fk_loan=".(isset($this->fk_loan)?$this->fk_loan:"null").",";
		$sql.= " datec=".(dol_strlen($this->datec)!=0 ? "'".$this->db->idate($this->datec)."'" : 'null').",";
		$sql.= " tms=".(dol_strlen($this->tms)!=0 ? "'".$this->db->idate($this->tms)."'" : 'null').",";
		$sql.= " datep=".(dol_strlen($this->datep)!=0 ? "'".$this->db->idate($this->datep)."'" : 'null').",";
		$sql.= " amount_capital=".(isset($this->amount_capital)?$this->amount_capital:"null").",";
		$sql.= " amount_insurance=".(isset($this->amount_insurance)?$this->amount_insurance:"null").",";
		$sql.= " amount_interest=".(isset($this->amount_interest)?$this->amount_interest:"null").",";
		$sql.= " fk_typepayment=".(isset($this->fk_typepayment)?$this->fk_typepayment:"null").",";
		$sql.= " num_payment=".(isset($this->num_payment)?"'".$this->db->escape($this->num_payment)."'":"null").",";
		$sql.= " note_private=".(isset($this->note_private)?"'".$this->db->escape($this->note_private)."'":"null").",";
		$sql.= " note_public=".(isset($this->note_public)?"'".$this->db->escape($this->note_public)."'":"null").",";
		$sql.= " fk_bank=".(isset($this->fk_bank)?$this->fk_bank:"null").",";
		$sql.= " fk_user_creat=".(isset($this->fk_user_creat)?$this->fk_user_creat:"null").",";
		$sql.= " fk_user_modif=".(isset($this->fk_user_modif)?$this->fk_user_modif:"null")."";

		$sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action call a trigger.

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
	 *  @param	User	$user        	User that delete
	 *  @param  int		$notrigger		0=launch triggers after, 1=disable triggers
	 *  @return int						<0 if KO, >0 if OK
	 */
	function delete($user, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;

		$this->db->begin();

	    if (! $error)
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element;
			$sql.= " WHERE rowid=".$this->id;

			dol_syslog(get_class($this)."::delete", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		}

		if (! $error)
		{
			if (! $notrigger)
			{
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action call a trigger.

				//// Call triggers
				//include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				//$interface=new Interfaces($this->db);
				//$result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
				//if ($result < 0) { $error++; $this->errors=$interface->errors; }
				//// End call triggers
			}
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

	function calc_mens($capital,$rate,$nbterm)
	{
		$result='';

		if (!empty($capital)&&!empty($rate)&&!empty($nbterm))
		{
			$result=($capital*($rate/12))/(1-pow((1+($rate/12)),($nbterm*-1)));
		}

		return $result;
	}


	/**
	 *  Load all object in memory from database
	 *
	 *  @param	int		$loanid     Id object
	 *  @return int         		<0 if KO, >0 if OK
	 */
	function fetchAll($loanid)
	{
		global $langs;

		$sql = "SELECT";
		$sql.= " t.rowid,";
		$sql.= " t.fk_loan,";
		$sql.= " t.datec,";
		$sql.= " t.tms,";
		$sql.= " t.datep,";
		$sql.= " t.amount_capital,";
		$sql.= " t.amount_insurance,";
		$sql.= " t.amount_interest,";
		$sql.= " t.fk_typepayment,";
		$sql.= " t.num_payment,";
		$sql.= " t.note_private,";
		$sql.= " t.note_public,";
		$sql.= " t.fk_bank,";
		$sql.= " t.fk_user_creat,";
		$sql.= " t.fk_user_modif";
		$sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
		$sql.= " WHERE t.fk_loan = ".$loanid;

		dol_syslog(get_class($this)."::fetchAll", LOG_DEBUG);
		$resql=$this->db->query($sql);

		if ($resql)
		{
			while($obj = $this->db->fetch_object($resql))
			{
				$line = New LoanSchedule($this->db);
				$line->id = $obj->rowid;
				$line->ref = $obj->rowid;

				$line->fk_loan = $obj->fk_loan;
				$line->datec = $this->db->jdate($obj->datec);
				$line->tms = $this->db->jdate($obj->tms);
				$line->datep = $this->db->jdate($obj->datep);
				$line->amount_capital = $obj->amount_capital;
				$line->amount_insurance = $obj->amount_insurance;
				$line->amount_interest = $obj->amount_interest;
				$line->fk_typepayment = $obj->fk_typepayment;
				$line->num_payment = $obj->num_payment;
				$line->note_private = $obj->note_private;
				$line->note_public = $obj->note_public;
				$line->fk_bank = $obj->fk_bank;
				$line->fk_user_creat = $obj->fk_user_creat;
				$line->fk_user_modif = $obj->fk_user_modif;

				$this->lines[] = $line;
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
	 *  trans_paiment
	 *
	 *  @return void
	 */
	function trans_paiment()
	{
		require_once DOL_DOCUMENT_ROOT.'/loan/class/loan.class.php';
		require_once DOL_DOCUMENT_ROOT.'/core/lib/loan.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

		$toinsert = array();

		$sql = "SELECT l.rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."loan as l ";
		$sql.= " WHERE l.paid = 0";
		$resql=$this->db->query($sql);

		if($resql){
			while($obj = $this->db->fetch_object($resql)){
				$lastrecorded = $this->lastpaiment($obj->rowid);
				$toinsert = $this->paimenttorecord($obj->rowid, $lastrecorded);
				if(count($toinsert)>0){
					foreach ($toinsert as $echid){
						$this->db->begin();
						$sql = "INSERT INTO " .MAIN_DB_PREFIX . "payment_loan ";
						$sql.= "(fk_loan,datec,tms,datep,amount_capital,amount_insurance,amount_interest,fk_typepayment,num_payment,note_private,note_public,fk_bank,fk_user_creat,fk_user_modif) ";
						$sql.= "SELECT fk_loan,datec,tms,datep,amount_capital,amount_insurance,amount_interest,fk_typepayment,num_payment,note_private,note_public,fk_bank,fk_user_creat,fk_user_modif FROM " . MAIN_DB_PREFIX . "loan_schedule WHERE rowid =" .$echid;
						$res=$this->db->query($sql);
						if($res){
							$this->db->commit();
						}else {
							$this->db->rollback();
						}
					}
				}
			}
		}
	}


	/**
	 *  trans_paiment
	 *
	 *  @param  int    $loanid     Loan id
	 *  @return int                < 0 if KO, Date > 0 if OK
	 */
	function lastpaiment($loanid)
	{
		$sql = "SELECT p.datep";
		$sql.= " FROM ".MAIN_DB_PREFIX."payment_loan as p ";
		$sql.= " WHERE p.fk_loan = " . $loanid;
		$sql.= " ORDER BY p.datep DESC ";
		$sql.= " LIMIT 1 ";

		$resql=$this->db->query($sql);

		if($resql){
			$obj = $this->db->fetch_object($resql);
			return $this->db->jdate($obj->datep);
		}else{
			return -1;
		}
	}

	/**
	 *  paimenttorecord
	 *
	 *  @param  int        $loanid     Loan id
	 *  @param  int        $datemax    Date max
	 *  @return array                  Array of id
	 */
	function paimenttorecord($loanid, $datemax)
	{
		$sql = "SELECT p.rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." as p ";
		$sql.= " WHERE p.fk_loan = " . $loanid;
		if (!empty($datemax)) { $sql.= " AND p.datep > '" . $this->db->idate($datemax) ."'";}
		$sql.= " AND p.datep <= '" . $this->db->idate(dol_now()). "'";

		$resql=$this->db->query($sql);

		if($resql){
			while($obj = $this->db->fetch_object($resql))
			{
				$result[] = $obj->rowid;
			}

		}

		return $result;
	}
}

