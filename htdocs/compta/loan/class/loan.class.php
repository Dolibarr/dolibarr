<?php
/* Copyright (C) 2014		Alexandre Spangaro	 <alexandre.spangaro@gmail.com>
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
 *      \file       htdocs/compta/loan/class/loan.class.php
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
    var $ref;
    var $datestart;
	var $dateend;
    var $label;
    var $capital;
	var $nbterm;
	var $rate;
	var $note;
	var $account_capital;
	var $account_insurance;
	var $account_interest;
    var $date_creation;
    var $date_modification;
    var $date_validation;


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
     *  @return	void
     */
    function fetch($id)
    {
        $sql = "SELECT l.rowid, l.datestart,";
        $sql.= " l.label, l.capital";
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
                $this->label          = $obj->label;
                $this->capital        = $obj->capital;

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
     *      Create a social contribution into database
     *
     *      @param	User	$user   User making creation
     *      @return int     		<0 if KO, id if OK
     */
    function create($user)
    {
    	global $conf;

        // clean parameters
        $newcapital=price2num($this->capital,'MT');

        // Check parameters
        if (! $newcapital > 0 || empty($this->datestart) || empty($this->dateend))
        {
            $this->error="ErrorBadParameter";
            return -2;
        }

        $this->db->begin();

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."loan (label, date_ech, periode, amount, entity)";
        $sql.= " VALUES ('".$this->db->escape($this->label)."',";
        $sql.= " '".$this->db->idate($this->datestart)."','".$this->db->idate($this->dateend)."',";
        $sql.= " '".price2num($newcapital)."',";
        $sql.= " ".$conf->entity;
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

        // Get bank transaction lines for this social contributions
        include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
        $account=new Account($this->db);
        $lines_url=$account->get_url('',$this->id,'sc');

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
            $sql = "DELETE FROM ".MAIN_DB_PREFIX."paiementcharge where fk_charge='".$this->id."'";
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
            $sql = "DELETE FROM ".MAIN_DB_PREFIX."chargesociales where rowid='".$this->id."'";
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
     *      @param	User	$user   Utilisateur qui modifie
     *      @return int     		<0 si erreur, >0 si ok
     */
    function update($user)
    {
        $this->db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX."chargesociales";
        $sql.= " SET libelle='".$this->db->escape($this->lib)."',";
        $sql.= " date_ech='".$this->db->idate($this->date_ech)."',";
        $sql.= " periode='".$this->db->idate($this->periode)."'";
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
     * Enter description here ...
     *
     * @param	int		$year		Year
     * @return	number
     */
    function solde($year = 0)
    {
    	global $conf;

        $sql = "SELECT SUM(f.amount) as amount";
        $sql.= " FROM ".MAIN_DB_PREFIX."chargesociales as f";
        $sql.= " WHERE f.entity = ".$conf->entity;
        $sql.= " AND paye = 0";

        if ($year) {
            $sql .= " AND f.datev >= '$y-01-01' AND f.datev <= '$y-12-31' ";
        }

        $result = $this->db->query($sql);
        if ($result)
        {
            if ($this->db->num_rows($result))
            {
                $obj = $this->db->fetch_object($result);
                return $obj->amount;
            }
            else
            {
                return 0;
            }

            $this->db->free($result);

        }
        else
        {
            print $this->db->error();
            return -1;
        }
    }

    /**
     *    Tag social contribution as payed completely
     *
     *    @param	User	$user       Object user making change
     *    @return	int					<0 if KO, >0 if OK
     */
    function set_paid($user)
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."chargesociales SET";
        $sql.= " paye = 1";
        $sql.= " WHERE rowid = ".$this->id;
        $return = $this->db->query($sql);
        if ($return) return 1;
        else return -1;
    }

    /**
     *  Retourne le libelle du statut d'une charge (impaye, payee)
     *
     *  @param	int		$mode       	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long
	 *  @param  double	$alreadypaid	0=No payment already done, >0=Some payments were already done (we recommand to put here amount payed if you have it, 1 otherwise)
     *  @return	string        			Label
     */
    function getLibStatut($mode=0,$alreadypaid=-1)
    {
        return $this->LibStatut($this->paye,$mode,$alreadypaid);
    }

    /**
     *  Return label for given status
     *
     *  @param	int		$statut        	Id statut
     *  @param  int		$mode          	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
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
    function getNameUrl($withpicto=0,$maxlen=0)
    {
        global $langs;

        $result='';

        if (empty($this->ref)) $this->ref=$this->lib;

        $lien = '<a href="'.DOL_URL_ROOT.'/compta/loan/card.php?id='.$this->id.'">';
        $lienfin='</a>';

        if ($withpicto) $result.=($lien.img_object($langs->trans("ShowSocialContribution").': '.$this->lib,'bill').$lienfin.' ');
        if ($withpicto && $withpicto != 2) $result.=' ';
        if ($withpicto != 2) $result.=$lien.($maxlen?dol_trunc($this->ref,$maxlen):$this->ref).$lienfin;
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
        $sql = "SELECT l.rowid, l.tms as datem, l.datec as datec";
        $sql.= " FROM ".MAIN_DB_PREFIX."loan as l";
        $sql.= " WHERE l.rowid = ".$id;

        dol_syslog(get_class($this)."::info", LOG_DEBUG);
        $result=$this->db->query($sql);
        if ($result)
        {
            if ($this->db->num_rows($result))
            {
                $obj = $this->db->fetch_object($result);

                $this->id = $obj->rowid;

                if ($obj->fk_user_author) {
                    $cuser = new User($this->db);
                    $cuser->fetch($obj->fk_user_author);
                    $this->user_creation     = $cuser;
                }

                $this->date_creation     = $this->db->jdate($obj->datec);
                $this->date_modification = $this->db->jdate($obj->datem);

            }

            $this->db->free($result);

        }
        else
        {
            dol_print_error($this->db);
        }
    }
}

