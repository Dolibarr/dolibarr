<?php
/* Copyright (C) 2014       Alexandre Spangaro   <alexandre.spangaro@gmail.com>
 * Copyright (C) 2015       Frederic France      <frederic.france@free.fr>
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
 *      \file       htdocs/loan/class/loan.class.php
 *		\ingroup    loan
 *		\brief      Class for loan module
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**     \class      Loan
 *		\brief      Class to manage loan
 */
class Loan extends CommonObject
{
    public $element='loan';
    public $table='loan';
    public $table_element='loan';

    var $id;
	var $rowid;
    var $ref;
    var $datestart;
	var $dateend;
    var $label;
    var $capital;
	var $nbterm;
	var $rate;
    var $note_private;
    var $note_public;
	var $paid;
	var $account_capital;
	var $account_insurance;
	var $account_interest;
    var $date_creation;
    var $date_modification;
    var $date_validation;
	var $fk_bank;
	var $fk_user_creat;
	var $fk_user_modif;


    /**
     * Constructor
     *
     * @param	DoliDB		$db		Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
        return 1;
    }

    /**
     *  Load object in memory from database
     *
	 *  @param	int		$id         id object
     *  @return int                 <0 error , >=0 no error
     */
    function fetch($id)
    {
        $sql = "SELECT l.rowid, l.label, l.capital, l.datestart, l.dateend, l.nbterm, l.rate, l.note_private, l.note_public,";
		$sql.= " l.paid";
        $sql.= " FROM ".MAIN_DB_PREFIX."loan as l";
        $sql.= " WHERE l.rowid = ".$id;

        dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id             = $obj->rowid;
				$this->ref            = $obj->rowid;
                $this->datestart      = $this->db->jdate($obj->datestart);
				$this->dateend	      = $this->db->jdate($obj->dateend);
                $this->label          = $obj->label;
                $this->capital        = $obj->capital;
				$this->nbterm		  = $obj->nbterm;
				$this->rate           = $obj->rate;
                $this->note_private   = $obj->note_private;
                $this->note_public    = $obj->note_public;
				$this->paid           = $obj->paid;

                return 1;
            }
            else
            {
                return 0;
            }
            $this->db->free($resql);
        }
        else
        {
            $this->error=$this->db->lasterror();
            return -1;
        }
    }


    /**
     *      Create a loan into database
     *
     *      @param	User	$user   User making creation
     *      @return int     		<0 if KO, id if OK
     */
    function create($user)
    {
    	global $conf;
		
		$error=0;

        $now=dol_now();

        // clean parameters
        $newcapital=price2num($this->capital,'MT');
        if (isset($this->note_private)) $this->note_private = trim($this->note_private);
        if (isset($this->note_public)) $this->note_public = trim($this->note_public);
		if (isset($this->account_capital)) $this->account_capital = trim($this->account_capital);
		if (isset($this->account_insurance)) $this->account_insurance = trim($this->account_insurance);
		if (isset($this->account_interest)) $this->account_interest = trim($this->account_interest);
		if (isset($this->fk_bank)) $this->fk_bank=trim($this->fk_bank);
		if (isset($this->fk_user_creat)) $this->fk_user_creat=trim($this->fk_user_creat);
		if (isset($this->fk_user_modif)) $this->fk_user_modif=trim($this->fk_user_modif);

        // Check parameters
        if (! $newcapital > 0 || empty($this->datestart) || empty($this->dateend))
        {
            $this->error="ErrorBadParameter";
            return -2;
        }
		if (($conf->accounting->enabled) && empty($this->account_capital) && empty($this->account_insurance) && empty($this->account_interest))
		{
            $this->error="ErrorAccountingParameter";
            return -2;
		}

        $this->db->begin();

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."loan (label, fk_bank, capital, datestart, dateend, nbterm, rate, note_private, note_public";
		$sql.= " ,accountancy_account_capital, accountancy_account_insurance, accountancy_account_interest, entity";
		$sql.= " ,datec, fk_user_author)";
		$sql.= " VALUES ('".$this->db->escape($this->label)."',";
		$sql.= " '".$this->db->escape($this->fk_bank)."',";
        $sql.= " '".price2num($newcapital)."',";
		$sql.= " '".$this->db->idate($this->datestart)."',";
		$sql.= " '".$this->db->idate($this->dateend)."',";
        $sql.= " '".$this->db->escape($this->nbterm)."',";
		$sql.= " '".$this->db->escape($this->rate)."',";
		$sql.= " '".$this->db->escape($this->note_private)."',";
		$sql.= " '".$this->db->escape($this->note_public)."',";
		$sql.= " '".$this->db->escape($this->account_capital)."',";
		$sql.= " '".$this->db->escape($this->account_insurance)."',";
		$sql.= " '".$this->db->escape($this->account_interest)."',";
        $sql.= " ".$conf->entity.",";
		$sql.= " '".$this->db->idate($now)."',";
		$sql.= " ".$user->id;
        $sql.= ")";

        dol_syslog(get_class($this)."::create", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->id=$this->db->last_insert_id(MAIN_DB_PREFIX."loan");

            //dol_syslog("Loans::create this->id=".$this->id);
            $this->db->commit();
            return $this->id;
        }
        else
        {
            $this->error=$this->db->error();
            $this->db->rollback();
            return -1;
        }
    }


    /**
     *      Delete a loan
     *
     *      @param		User    $user   Object user making delete
     *      @return     		int 	<0 if KO, >0 if OK
     */
    function delete($user)
    {
        $error=0;

        $this->db->begin();

        // Get bank transaction lines for this loan
        include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
        $account=new Account($this->db);
        $lines_url=$account->get_url('',$this->id,'loan');

        // Delete bank urls
        foreach ($lines_url as $line_url)
        {
            if (! $error)
            {
                $accountline=new AccountLine($this->db);
                $accountline->fetch($line_url['fk_bank']);
                $result=$accountline->delete_urls($user);
                if ($result < 0)
                {
                    $error++;
                }
            }
        }

        // Delete payments
        if (! $error)
        {
            $sql = "DELETE FROM ".MAIN_DB_PREFIX."payment_loan where fk_loan='".$this->id."'";
            dol_syslog(get_class($this)."::delete", LOG_DEBUG);
            $resql=$this->db->query($sql);
            if (! $resql)
            {
                $error++;
                $this->error=$this->db->lasterror();
            }
        }

        if (! $error)
        {
            $sql = "DELETE FROM ".MAIN_DB_PREFIX."loan where rowid='".$this->id."'";
            dol_syslog(get_class($this)."::delete", LOG_DEBUG);
            $resql=$this->db->query($sql);
            if (! $resql)
            {
                $error++;
                $this->error=$this->db->lasterror();
            }
        }

        if (! $error)
        {
            $this->db->commit();
            return 1;
        }
        else
        {
            $this->db->rollback();
            return -1;
        }

    }


    /**
     *      Update loan
     *
     *      @param	User	$user   User who modified
     *      @return int     		<0 if error, >0 if ok
     */
    function update($user)
    {
        $this->db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX."loan";
        $sql.= " SET label='".$this->db->escape($this->label)."',";
        $sql.= " datestart='".$this->db->idate($this->datestart)."',";
        $sql.= " dateend='".$this->db->idate($this->dateend)."',";
		$sql.= " fk_user_modif = ".$user->id;
        $sql.= " WHERE rowid=".$this->id;

        dol_syslog(get_class($this)."::update", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->db->commit();
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            $this->db->rollback();
            return -1;
        }
    }

    /**
     *    Tag loan as payed completely
     *
     *    @param	User	$user       Object user making change
     *    @return	int					<0 if KO, >0 if OK
     */
    function set_paid($user)
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."loan SET";
        $sql.= " paid = 1";
        $sql.= " WHERE rowid = ".$this->id;
        $return = $this->db->query($sql);
		if ($return) {
			return 1;
		} else {
			$this->error=$this->db->lasterror();
			return -1;
		}
    }

    /**
     *  Return label of loan status (unpaid, paid)
     *
     *  @param	int		$mode       	0=label, 1=short label, 2=Picto + Short label, 3=Picto, 4=Picto + Label
	 *  @param  double	$alreadypaid	0=No payment already done, >0=Some payments were already done (we recommand to put here amount payed if you have it, 1 otherwise)
     *  @return	string        			Label
     */
    function getLibStatut($mode=0,$alreadypaid=-1)
    {
        return $this->LibStatut($this->paid,$mode,$alreadypaid);
    }

    /**
     *  Return label for given status
     *
     *  @param	int		$statut        	Id statut
     *  @param  int		$mode          	0=Label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Label, 5=Short label + Picto
	 *  @param  double	$alreadypaid	0=No payment already done, >0=Some payments were already done (we recommand to put here amount payed if you have it, 1 otherwise)
     *  @return string        			Label
     */
    function LibStatut($statut,$mode=0,$alreadypaid=-1)
    {
        global $langs;
        $langs->load('customers');
        $langs->load('bills');
        
        if ($mode == 0)
        {
            if ($statut ==  0) return $langs->trans("Unpaid");
            if ($statut ==  1) return $langs->trans("Paid");
        }
        if ($mode == 1)
        {
            if ($statut ==  0) return $langs->trans("Unpaid");
            if ($statut ==  1) return $langs->trans("Paid");
        }
        if ($mode == 2)
        {
            if ($statut ==  0 && $alreadypaid <= 0) return img_picto($langs->trans("Unpaid"), 'statut1').' '.$langs->trans("Unpaid");
            if ($statut ==  0 && $alreadypaid > 0) return img_picto($langs->trans("BillStatusStarted"), 'statut3').' '.$langs->trans("BillStatusStarted");
            if ($statut ==  1) return img_picto($langs->trans("Paid"), 'statut6').' '.$langs->trans("Paid");
        }
        if ($mode == 3)
        {
            if ($statut ==  0 && $alreadypaid <= 0) return img_picto($langs->trans("Unpaid"), 'statut1');
            if ($statut ==  0 && $alreadypaid > 0) return img_picto($langs->trans("BillStatusStarted"), 'statut3');
            if ($statut ==  1) return img_picto($langs->trans("Paid"), 'statut6');
        }
        if ($mode == 4)
        {
            if ($statut ==  0 && $alreadypaid <= 0) return img_picto($langs->trans("Unpaid"), 'statut1').' '.$langs->trans("Unpaid");
            if ($statut ==  0 && $alreadypaid > 0) return img_picto($langs->trans("BillStatusStarted"), 'statut3').' '.$langs->trans("BillStatusStarted");
            if ($statut ==  1) return img_picto($langs->trans("Paid"), 'statut6').' '.$langs->trans("Paid");
        }
        if ($mode == 5)
        {
            if ($statut ==  0 && $alreadypaid <= 0) return $langs->trans("Unpaid").' '.img_picto($langs->trans("Unpaid"), 'statut1');
            if ($statut ==  0 && $alreadypaid > 0) return $langs->trans("BillStatusStarted").' '.img_picto($langs->trans("BillStatusStarted"), 'statut3');
            if ($statut ==  1) return $langs->trans("Paid").' '.img_picto($langs->trans("Paid"), 'statut6');
        }

        return "Error, mode/status not found";
    }


    /**
     *  Return clicable name (with eventually the picto)
     *
     *	@param	int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
     * 	@param	int		$maxlen			Label max length
     *	@return	string					Chaine with URL
     */
    function getLinkUrl($withpicto=0,$maxlen=0)
    {
        global $langs;

        $result='';

        $tooltip = '<u>' . $langs->trans("ShowLoan") . '</u>';
        if (! empty($this->ref))
            $tooltip .= '<br><b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;
        if (! empty($this->label))
            $tooltip .= '<br><b>' . $langs->trans('Label') . ':</b> ' . $this->label;
        $link = '<a href="'.DOL_URL_ROOT.'/loan/card.php?id='.$this->id.'"';
        $linkclose = '" title="'.str_replace('\n', '', dol_escape_htmltag($tooltip, 1)).'" class="classfortooltip">';
        $linkend = '</a>';

        if ($withpicto) $result.=($link.$linkclose.img_object($langs->trans("ShowLoan").': '.$this->label,'bill', 'class="classfortooltip"').$linkend.' ');
        if ($withpicto && $withpicto != 2) $result.=' ';
        if ($withpicto != 2) $result.=$link.$linkclose.($maxlen?dol_trunc($this->label,$maxlen):$this->label).$linkend;
        return $result;
    }

    /**
     * 	Return amount of payments already done
     *
     *	@return		int		Amount of payment already done, <0 if KO
     */
    function getSumPayment()
    {
        $table='payment_loan';
        $field='fk_loan';

        $sql = 'SELECT sum(amount) as amount';
        $sql.= ' FROM '.MAIN_DB_PREFIX.$table;
        $sql.= ' WHERE '.$field.' = '.$this->id;

        dol_syslog(get_class($this)."::getSumPayment", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $amount=0;

            $obj = $this->db->fetch_object($resql);
            if ($obj) $amount=$obj->amount?$obj->amount:0;

            $this->db->free($resql);
            return $amount;
        }
        else
        {
            $this->error=$this->db->lasterror();
			return -1;
        }
    }

	/**
	 * Information on record
	 *
	 * @param	int		$id      Id of record
	 * @return	void
	 */
	function info($id)
	{
		$sql = 'SELECT l.rowid, l.datec, l.fk_user_author, l.fk_user_modif,';
		$sql.= ' l.tms';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'loan as l';
		$sql.= ' WHERE l.rowid = '.$id;

		dol_syslog(get_class($this).'::info', LOG_DEBUG);
		$result = $this->db->query($sql);

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
					$this->user_creation = $cuser;
				}
				if ($obj->fk_user_modif)
				{
					$muser = new User($this->db);
					$muser->fetch($obj->fk_user_modif);
					$this->user_modification = $muser;
				}
				$this->date_creation     = $this->db->jdate($obj->datec);
				if (empty($obj->fk_user_modif)) $obj->tms = "";
				$this->date_modification = $this->db->jdate($obj->tms);

				return 1;
			}
			else
			{
				return 0;
			}
			$this->db->free($result);
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}
	}
}
