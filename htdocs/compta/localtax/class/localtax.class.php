<?php
/* Copyright (C) 2011	Juanjo Menent	<jmenent@2byte.es>
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
 *      \file       htdocs/compta/localtax/class/localtax.class.php
 *      \ingroup    tax
 *      \author		Laurent Destailleur
 */

require_once DOL_DOCUMENT_ROOT .'/core/class/commonobject.class.php';


/**
 *	Class to manage local tax
 */
class Localtax extends CommonObject
{
    var $id;
    var $ref;
	var $tms;
	var $datep;
	var $datev;
	var $amount;
	var $label;
	var $note;
	var $fk_bank;
	var $fk_user_creat;
	var $fk_user_modif;

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
     *  Create in database
     *
     *  @param      User	$user       User that create
     *  @return     int      			<0 if KO, >0 if OK
     */
    function create($user)
    {
    	global $conf, $langs;

		$error=0;

		// Clean parameters
		$this->amount=trim($this->amount);
		$this->label=trim($this->label);
		$this->note=trim($this->note);
		$this->fk_bank=trim($this->fk_bank);
		$this->fk_user_creat=trim($this->fk_user_creat);
		$this->fk_user_modif=trim($this->fk_user_modif);

        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."localtax(";
		$sql.= "tms,";
		$sql.= "datep,";
		$sql.= "datev,";
		$sql.= "amount,";
		$sql.= "label,";
		$sql.= "note,";
		$sql.= "fk_bank,";
		$sql.= "fk_user_creat,";
		$sql.= "fk_user_modif";
        $sql.= ") VALUES (";
		$sql.= " ".$this->db->idate($this->tms).",";
		$sql.= " ".$this->db->idate($this->datep).",";
		$sql.= " ".$this->db->idate($this->datev).",";
		$sql.= " '".$this->amount."',";
		$sql.= " '".$this->label."',";
		$sql.= " '".$this->note."',";
		$sql.= " ".($this->fk_bank <= 0 ? "NULL" : "'".$this->fk_bank."'").",";
		$sql.= " '".$this->fk_user_creat."',";
		$sql.= " '".$this->fk_user_modif."'";
		$sql.= ")";

	   	dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."localtax");

            // Appel des triggers
            include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('LOCALTAX_CREATE',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            // Fin appel triggers

            return $this->id;
        }
        else
        {
            $this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     *	Update database
     *
     *	@param		User	$user        	User that modify
     *	@param		int		$notrigger		0=no, 1=yes (no update trigger)
     *	@return		int						<0 if KO, >0 if OK
     */
    function update($user=0, $notrigger=0)
    {
    	global $conf, $langs;

		$error=0;

		// Clean parameters
		$this->amount=trim($this->amount);
		$this->label=trim($this->label);
		$this->note=trim($this->note);
		$this->fk_bank=trim($this->fk_bank);
		$this->fk_user_creat=trim($this->fk_user_creat);
		$this->fk_user_modif=trim($this->fk_user_modif);

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."localtax SET";
		$sql.= " tms=".$this->db->idate($this->tms).",";
		$sql.= " datep=".$this->db->idate($this->datep).",";
		$sql.= " datev=".$this->db->idate($this->datev).",";
		$sql.= " amount='".$this->amount."',";
		$sql.= " label='".$this->db->escape($this->label)."',";
		$sql.= " note='".$this->db->escape($this->note)."',";
		$sql.= " fk_bank='".$this->fk_bank."',";
		$sql.= " fk_user_creat='".$this->fk_user_creat."',";
		$sql.= " fk_user_modif='".$this->fk_user_modif."'";
        $sql.= " WHERE rowid=".$this->id;

        dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (! $resql)
        {
            $this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::update ".$this->error, LOG_ERR);
            return -1;
        }

		if (! $notrigger)
		{
            // Appel des triggers
            include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('LOCALTAX_MODIFY',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            // Fin appel triggers
    	}

        return 1;
    }


    /**
     *	Load object in memory from database
     *
     *	@param		int		$id		Object id
     *	@return		int				<0 if KO, >0 if OK
     */
    function fetch($id)
    {
    	global $langs;
        $sql = "SELECT";
		$sql.= " t.rowid,";
		$sql.= " t.tms,";
		$sql.= " t.datep,";
		$sql.= " t.datev,";
		$sql.= " t.amount,";
		$sql.= " t.label,";
		$sql.= " t.note,";
		$sql.= " t.fk_bank,";
		$sql.= " t.fk_user_creat,";
		$sql.= " t.fk_user_modif,";
		$sql.= " b.fk_account,";
		$sql.= " b.fk_type,";
		$sql.= " b.rappro";
        $sql.= " FROM ".MAIN_DB_PREFIX."localtax as t";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON t.fk_bank = b.rowid";
        $sql.= " WHERE t.rowid = ".$id;

    	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id    = $obj->rowid;
                $this->ref   = $obj->rowid;
				$this->tms   = $this->db->jdate($obj->tms);
				$this->datep = $this->db->jdate($obj->datep);
				$this->datev = $this->db->jdate($obj->datev);
				$this->amount = $obj->amount;
				$this->label = $obj->label;
				$this->note  = $obj->note;
				$this->fk_bank = $obj->fk_bank;
				$this->fk_user_creat = $obj->fk_user_creat;
				$this->fk_user_modif = $obj->fk_user_modif;
				$this->fk_account = $obj->fk_account;
				$this->fk_type = $obj->fk_type;
				$this->rappro  = $obj->rappro;
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
 	 *	Delete object in database
 	 *
 	 *	@param		User	$user		User that delete
 	 *	@return		int					<0 if KO, >0 if OK
 	 */
	function delete($user)
	{
		global $conf, $langs;

		$error=0;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."localtax";
		$sql.= " WHERE rowid=".$this->id;

	   	dol_syslog(get_class($this)."::delete sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql)
		{
			$this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::delete ".$this->error, LOG_ERR);
			return -1;
		}

        // Appel des triggers
        include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
        $interface=new Interfaces($this->db);
        $result=$interface->run_triggers('LOCALTAX_DELETE',$this,$user,$langs,$conf);
        if ($result < 0) { $error++; $this->errors=$interface->errors; }
        // Fin appel triggers

		return 1;
	}


	/**
     *  Initialise an instance with random values.
     *  Used to build previews or test instances.
     *	id must be 0 if object instance is a specimen.
     *
     *  @return	void
	 */
	function initAsSpecimen()
	{
		$this->id=0;

		$this->tms='';
		$this->datep='';
		$this->datev='';
		$this->amount='';
		$this->label='';
		$this->note='';
		$this->fk_bank='';
		$this->fk_user_creat='';
		$this->fk_user_modif='';
	}


    /**
     *	Hum la fonction s'appelle 'Solde' elle doit a mon avis calcluer le solde de localtax, non ?
     *
     *	@param	int		$year		Year
     *	@return	int					???
     */
    function solde($year = 0)
    {

        $reglee = $this->localtax_sum_reglee($year);

        $payee = $this->localtax_sum_payee($year);
        $collectee = $this->localtax_sum_collectee($year);

        $solde = $reglee - ($collectee - $payee);

        return $solde;
    }

    /**
     *	Total de la localtax des factures emises par la societe.
     *
     *	@param	int		$year		Year
     *	@return	int					???
     */
    function localtax_sum_collectee($year = 0)
    {

        $sql = "SELECT sum(f.localtax) as amount";
        $sql .= " FROM ".MAIN_DB_PREFIX."facture as f WHERE f.paye = 1";

        if ($year)
        {
            $sql .= " AND f.datef >= '$year-01-01' AND f.datef <= '$year-12-31' ";
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
     *	localtax payed
     *
     *	@param	int		$year		Year
     *	@return	int					???
     */
    function localtax_sum_payee($year = 0)
    {

        $sql = "SELECT sum(f.total_localtax) as total_localtax";
        $sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";

        if ($year)
        {
            $sql .= " WHERE f.datef >= '$year-01-01' AND f.datef <= '$year-12-31' ";
        }
        $result = $this->db->query($sql);

        if ($result)
        {
            if ($this->db->num_rows($result))
            {
                $obj = $this->db->fetch_object($result);
                return $obj->total_localtax;
            }
            else
            {
                return 0;
            }

            $this->db->free();

        }
        else
        {
            print $this->db->error();
            return -1;
        }
    }


    /**
     *	localtax payed
     *  Total de la localtax payed
     *
     *	@param	int		$year		Year
     *	@return	int					???
     */
    function localtax_sum_reglee($year = 0)
    {

        $sql = "SELECT sum(f.amount) as amount";
        $sql .= " FROM ".MAIN_DB_PREFIX."localtax as f";

        if ($year)
        {
            $sql .= " WHERE f.datev >= '$year-01-01' AND f.datev <= '$year-12-31' ";
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

            $this->db->free();

        }
        else
        {
            print $this->db->error();
            return -1;
        }
    }


    /**
     *	Add a payment of localtax
     *
	 *	@param		User	$user		Object user that insert
	 *	@return		int					<0 if KO, rowid in localtax table if OK
     */
    function addPayment($user)
    {
        global $conf,$langs;

        $this->db->begin();

        // Check parameters
        $this->amount=price2num($this->amount);
		if (! $this->label)
		{
			$this->error=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Label"));
			return -3;
		}
        if ($this->amount <= 0)
        {
            $this->error=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Amount"));
            return -4;
        }
        if (! empty($conf->banque->enabled) && (empty($this->accountid) || $this->accountid <= 0))
        {
            $this->error=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Account"));
            return -5;
        }
        if (! empty($conf->banque->enabled) && (empty($this->paymenttype) || $this->paymenttype <= 0))
        {
            $this->error=$langs->trans("ErrorFieldRequired",$langs->transnoentities("PaymentMode"));
            return -5;
        }

        // Insertion dans table des paiement localtax
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."localtax (datep, datev, amount";
        if ($this->note)  $sql.=", note";
        if ($this->label) $sql.=", label";
        $sql.= ", fk_user_creat, fk_bank";
		$sql.= ") ";
        $sql.= " VALUES ('".$this->db->idate($this->datep)."',";
        $sql.= "'".$this->db->idate($this->datev)."'," . $this->amount;
        if ($this->note)  $sql.=", '".$this->db->escape($this->note)."'";
        if ($this->label) $sql.=", '".$this->db->escape($this->label)."'";
        $sql.=", '".$user->id."', NULL";
        $sql.= ")";

		dol_syslog(get_class($this)."::addPayment sql=".$sql);
        $result = $this->db->query($sql);
        if ($result)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."localtax");    // TODO devrait s'appeler paiementlocaltax
            if ($this->id > 0)
            {
                $ok=1;
				if (! empty($conf->banque->enabled))
                {
                    // Insertion dans llx_bank
                    require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

                    $acc = new Account($this->db);
					$result=$acc->fetch($this->accountid);
					if ($result <= 0) dol_print_error($this->db);

                    $bank_line_id = $acc->addline($this->datep, $this->paymenttype, $this->label, -abs($this->amount), '', '', $user);

                    // Mise a jour fk_bank dans llx_localtax. On connait ainsi la ligne de localtax qui a g�n�r� l'�criture bancaire
                    if ($bank_line_id > 0)
					{
                        $this->update_fk_bank($bank_line_id);
                    }
					else
					{
						$this->error=$acc->error;
						$ok=0;
					}

                    // Mise a jour liens
                    $result=$acc->add_url_line($bank_line_id, $this->id, DOL_URL_ROOT.'/compta/localtax/fiche.php?id=', "(VATPayment)", "payment_vat");
                    if ($result < 0)
                    {
                    	$this->error=$acc->error;
                    	$ok=0;
                    }
	            }

				if ($ok)
				{
					$this->db->commit();
					return $this->id;
				}
				else
				{
					$this->db->rollback();
					return -3;
				}
            }
            else
            {
                $this->error=$this->db->error();
                $this->db->rollback();
                return -2;
            }
        }
        else
        {
            $this->error=$this->db->error();
            $this->db->rollback();
            return -1;
        }
    }

    /**
     *	Update the link betwen localtax payment and the line into llx_bank
     *
     *	@param		int		$id		Id bank account
	 *	@return		int				<0 if KO, >0 if OK
     */
	function update_fk_bank($id)
	{
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'localtax SET fk_bank = '.$id;
		$sql.= ' WHERE rowid = '.$this->id;
		$result = $this->db->query($sql);
		if ($result)
		{
			return 1;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}


	/**
	 *	Returns clickable name
	 *
	 *	@param		int		$withpicto		0=Link, 1=Picto into link, 2=Picto
	 *	@param		string	$option			Sur quoi pointe le lien
	 *	@return		string					Chaine avec URL
	 */
	function getNomUrl($withpicto=0, $option='')
	{
		global $langs;

		$result='';

		$lien = '<a href="'.DOL_URL_ROOT.'/compta/localtax/fiche.php?id='.$this->id.'">';
		$lienfin='</a>';

		$picto='payment';
		$label=$langs->trans("ShowVatPayment").': '.$this->ref;

		if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$lien.$this->ref.$lienfin;
		return $result;
	}

}
?>