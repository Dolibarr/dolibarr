<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
        \file       htdocs/compta/tva/tva.class.php
		\ingroup    tax
		\version    $Id$
		\author		Laurent Destailleur
*/

// Put here all includes required by your class file
require_once(DOL_DOCUMENT_ROOT ."/commonobject.class.php");


/**
        \class      Tva
        \brief      Put here description of your class
		\remarks	Initialy built by build_class_from_table on 2008-04-03 21:01
*/
class Tva extends CommonObject
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	//var $element='tva';			//!< Id that identify managed objects
	//var $table_element='tva';	//!< Name of table without prefix where object is stored
    
    var $id;
    
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
     *      \brief      Constructor
     *      \param      DB      Database handler
     */
    function Tva($DB) 
    {
        $this->db = $DB;
        return 1;
    }

	
    /**
     *      \brief      Create in database
     *      \param      user        User that create
     *      \return     int         <0 si ko, >0 si ok
     */
    function create($user)
    {
    	global $conf, $langs;
    	
		// Clean parameters
        
		$this->amount=trim($this->amount);
		$this->label=trim($this->label);
		$this->note=trim($this->note);
		$this->fk_bank=trim($this->fk_bank);
		$this->fk_user_creat=trim($this->fk_user_creat);
		$this->fk_user_modif=trim($this->fk_user_modif);

        

		// Check parameters
		// Put here code to add control on parameters values
		
        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."tva(";
		
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

	   	dolibarr_syslog("Tva::create sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."tva");
    
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            // Fin appel triggers

            return $this->id;
        }
        else
        {
            $this->error="Error ".$this->db->lasterror();
            dolibarr_syslog("Tva::create ".$this->error, LOG_ERR);
            return -1;
        }
    }

    /*
     *      \brief      Update database
     *      \param      user        	User that modify
     *      \param      notrigger	    0=no, 1=yes (no update trigger)
     *      \return     int         	<0 if KO, >0 if OK
     */
    function update($user=0, $notrigger=0)
    {
    	global $conf, $langs;
    	
		// Clean parameters
        
		$this->amount=trim($this->amount);
		$this->label=trim($this->label);
		$this->note=trim($this->note);
		$this->fk_bank=trim($this->fk_bank);
		$this->fk_user_creat=trim($this->fk_user_creat);
		$this->fk_user_modif=trim($this->fk_user_modif);

        

		// Check parameters
		// Put here code to add control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."tva SET";
        
		$sql.= " tms=".$this->db->idate($this->tms).",";
		$sql.= " datep=".$this->db->idate($this->datep).",";
		$sql.= " datev=".$this->db->idate($this->datev).",";
		$sql.= " amount='".$this->amount."',";
		$sql.= " label='".addslashes($this->label)."',";
		$sql.= " note='".addslashes($this->note)."',";
		$sql.= " fk_bank='".$this->fk_bank."',";
		$sql.= " fk_user_creat='".$this->fk_user_creat."',";
		$sql.= " fk_user_modif='".$this->fk_user_modif."'";

        
        $sql.= " WHERE rowid=".$this->id;

        dolibarr_syslog("Tva::update sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (! $resql)
        {
            $this->error="Error ".$this->db->lasterror();
            dolibarr_syslog("Tva::update ".$this->error, LOG_ERR);
            return -1;
        }

		if (! $notrigger)
		{
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            // Fin appel triggers
    	}

        return 1;
    }
  
  
    /*
     *    \brief      Load object in memory from database
     *    \param      id          id object
     *    \param      user        User that load
     *    \return     int         <0 if KO, >0 if OK
     */
    function fetch($id, $user=0)
    {
    	global $langs;
        $sql = "SELECT";
		$sql.= " t.rowid,";
		
		$sql.= " ".$this->db->pdate('t.tms')." as tms,";
		$sql.= " ".$this->db->pdate('t.datep')." as datep,";
		$sql.= " ".$this->db->pdate('t.datev')." as datev,";
		$sql.= " t.amount,";
		$sql.= " t.label,";
		$sql.= " t.note,";
		$sql.= " t.fk_bank,";
		$sql.= " t.fk_user_creat,";
		$sql.= " t.fk_user_modif,";
		$sql.= " b.fk_account,";
		$sql.= " b.fk_type,";
		$sql.= " b.rappro";
		
        $sql.= " FROM ".MAIN_DB_PREFIX."tva as t";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON t.fk_bank = b.rowid";
        $sql.= " WHERE t.rowid = ".$id;
    
    	dolibarr_syslog("Tva::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);
    
                $this->id    = $obj->rowid;
                $this->ref   = $obj->rowid;
				$this->tms = $obj->tms;
				$this->datep = $obj->datep;
				$this->datev = $obj->datev;
				$this->amount = $obj->amount;
				$this->label = $obj->label;
				$this->note = $obj->note;
				$this->fk_bank = $obj->fk_bank;
				$this->fk_user_creat = $obj->fk_user_creat;
				$this->fk_user_modif = $obj->fk_user_modif;
				$this->fk_account = $obj->fk_account;
				$this->fk_type = $obj->fk_type;
				$this->rappro = $obj->rappro;
            }
            $this->db->free($resql);
            
            return 1;
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            dolibarr_syslog("Tva::fetch ".$this->error, LOG_ERR);
            return -1;
        }
    }
    
    
 	/*
	*   \brief      Delete object in database
    *	\param      user        User that delete
	*	\return		int			<0 if KO, >0 if OK
	*/
	function delete($user)
	{
		global $conf, $langs;
	
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."tva";
		$sql.= " WHERE rowid=".$this->id;
	
	   	dolibarr_syslog("Tva::delete sql=".$sql);
		$resql = $this->db->query($sql);
		if (! $resql)
		{
			$this->error="Error ".$this->db->lasterror();
            dolibarr_syslog("Tva::delete ".$this->error, LOG_ERR);
			return -1;
		}
	
        // Appel des triggers
        include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
        $interface=new Interfaces($this->db);
        $result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
        if ($result < 0) { $error++; $this->errors=$interface->errors; }
        // Fin appel triggers

		return 1;
	}

  
	/**
	 *		\brief		Initialise object with example values
	 *		\remarks	id must be 0 if object instance is a specimen.
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

	
    /*
     *      \brief      Hum la fonction s'appelle 'Solde' elle doit a mon avis calcluer le solde de TVA, non ?
     *
     */
    function solde($year = 0)
    {

        $reglee = $this->tva_sum_reglee($year);

        $payee = $this->tva_sum_payee($year);
        $collectee = $this->tva_sum_collectee($year);

        $solde = $reglee - ($collectee - $payee) ;

        return $solde;
    }

    /*
     *      \brief      Total de la TVA des factures emises par la societe.
     *
     */

    function tva_sum_collectee($year = 0)
    {

        $sql = "SELECT sum(f.tva) as amount";
        $sql .= " FROM ".MAIN_DB_PREFIX."facture as f WHERE f.paye = 1";

        if ($year)
        {
            $sql .= " AND f.datef >= '$year-01-01' AND f.datef <= '$year-12-31' ";
        }

        $result = $this->db->query($sql);

        if ($result)
        {
            if ($this->db->num_rows())
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

    /*
     *      \brief      Tva payée
     *
     */

    function tva_sum_payee($year = 0)
    {

        $sql = "SELECT sum(f.total_tva) as total_tva";
        $sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";

        if ($year)
        {
            $sql .= " WHERE f.datef >= '$year-01-01' AND f.datef <= '$year-12-31' ";
        }
        $result = $this->db->query($sql);

        if ($result)
        {
            if ($this->db->num_rows())
            {
                $obj = $this->db->fetch_object($result);
                return $obj->total_tva;
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


    /*
     *      \brief      Tva réglée
     *                  Total de la TVA réglee aupres de qui de droit
     *
     */

    function tva_sum_reglee($year = 0)
    {

        $sql = "SELECT sum(f.amount) as amount";
        $sql .= " FROM ".MAIN_DB_PREFIX."tva as f";

        if ($year)
        {
            $sql .= " WHERE f.datev >= '$year-01-01' AND f.datev <= '$year-12-31' ";
        }

        $result = $this->db->query($sql);

        if ($result)
        {
            if ($this->db->num_rows())
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


    /*
     *      \brief      Ajoute un paiement de TVA
	 *		\param		user		Object user that insert
	 *		\return		int			<0 if KO, rowid in tva table if OK
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
        if ($conf->banque->enabled && (empty($this->accountid) || $this->accountid <= 0))
        {
            $this->error=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Account"));
            return -5;   
        }
        if ($conf->banque->enabled && (empty($this->paymenttype) || $this->paymenttype <= 0))
        {
            $this->error=$langs->trans("ErrorFieldRequired",$langs->transnoentities("PaymentMode"));
            return -5;   
        }
                
        // Insertion dans table des paiement tva
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."tva (datep, datev, amount";
        if ($this->note)  $sql.=", note";
        if ($this->label) $sql.=", label";
        $sql.= ", fk_user_creat, fk_bank";
		$sql.= ") ";
        $sql.= " VALUES ('".$this->db->idate($this->datep)."',";
        $sql.= "'".$this->db->idate($this->datev)."'," . $this->amount;
        if ($this->note)  $sql.=", '".addslashes($this->note)."'";
        if ($this->label) $sql.=", '".addslashes($this->label)."'";
        $sql.=", '".$user->id."', NULL";
        $sql.= ")";

		dolibarr_syslog("Tva::addPayment sql=".$sql);
        $result = $this->db->query($sql);
        if ($result)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."tva");    // \todo devrait s'appeler paiementtva
            if ($this->id > 0)
            {
                $ok=1;
				if ($conf->banque->enabled)
                {
                    // Insertion dans llx_bank
                    require_once(DOL_DOCUMENT_ROOT.'/compta/bank/account.class.php');

                    $acc = new Account($this->db);
					$result=$acc->fetch($this->accountid);
					if ($result <= 0) dolibarr_print_error($db);
									
                    $bank_line_id = $acc->addline($this->datep, $this->paymenttype, $this->label, -abs($this->amount), '', '', $user);
            	  
                    // Mise a jour fk_bank dans llx_tva. On connait ainsi la ligne de tva qui a généré l'écriture bancaire
                    if ($bank_line_id > 0)
					{
                        $this->update_fk_bank($bank_line_id);
                    }
					else
					{
						$this->error=$acc->error;
						$ok=0;
					}
            	  
                    // Mise a jour liens (pour chaque charge concernée par le paiement)
                    //foreach ($paiement->amounts as $key => $value)
            	    //{
                    //    $chid = $key;
                    //    $fac = new Facture($db);
                    //    $fac->fetch($chid);
                    //    $fac->fetch_client();
                    //    $acc->add_url_line($bank_line_id, $paiement_id, DOL_URL_ROOT.'/compta/paiement/fiche.php?id=', "(paiement)");
                    //    $acc->add_url_line($bank_line_id, $fac->client->id, DOL_URL_ROOT.'/compta/fiche.php?socid=', $fac->client->nom);
            	    //}
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
     *      \brief      Mise a jour du lien entre le paiement tva et la ligne générée dans llx_bank
     *      \param      id_bank     Id compte bancaire
	 *		\return		int			<0 if KO, >0 if OK
     */
	function update_fk_bank($id_bank)
	{
		$sql = 'UPDATE llx_tva set fk_bank = '.$id_bank;
		$sql.= ' WHERE rowid = '.$this->id;
		$result = $this->db->query($sql);
		if ($result)
		{
			return 1;
		}
		else
		{
			dolibarr_print_error($this->db);
			return -1;
		}
	}


	/**
		\brief      Renvoie nom clicable (avec eventuellement le picto)
		\param		withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
		\param		option			Sur quoi pointe le lien
		\return		string			Chaine avec URL
	*/
	function getNomUrl($withpicto=0,$option='')
	{
		global $langs;
		
		$result='';
		
		$lien = '<a href="'.DOL_URL_ROOT.'/compta/tva/fiche.php?id='.$this->id.'">';
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
