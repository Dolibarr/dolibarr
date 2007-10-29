<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 */

/**
        \file       htdocs/tva.class.php
		\ingroup    compta
		\brief      Fichier de la classe de tva
        \remarks    La tva collectée n'est calculée que sur les factures payées.
		\version    $Revision$
*/


/**     \class      Tva
		\brief      Classe permettant la gestion de la tva
*/
class Tva
{
    var $db;
    var $note;

    /*
     *      \brief      Constructeur
     *      \param      DB      Handler d'accès base
     */
    function Tva($DB)
    {
        $this->db = $DB;

        return 1;
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
        if ($conf->banque->enabled && ! $this->accountid)
        {
            $this->error=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Account"));
            return -5;   
        }
                
        // Insertion dans table des paiement tva
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."tva (datep, datev, amount";
        if ($this->note)  $sql.=", note";
        if ($this->label) $sql.=", label";
        $sql.= ", fk_user_creat";
		$sql.= ") ";
        $sql.= " VALUES ('".$this->db->idate($this->datep)."',";
        $sql.= "'".$this->db->idate($this->datev)."'," . $this->amount;
        if ($this->note)  $sql.=", '".addslashes($this->note)."'";
        if ($this->label) $sql.=", '".addslashes($this->label)."'";
        $sql.=", '".$user->id."'";
        $sql.= ")";

        $result = $this->db->query($sql);
        if ($result)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."tva");    // \todo devrait s'appeler paiementtva
            if ($this->id > 0)
            {
                if ($conf->banque->enabled)
                {
                    // Insertion dans llx_bank

                    require_once(DOL_DOCUMENT_ROOT.'/compta/bank/account.class.php');

                    $acc = new Account($this->db, $this->accountid);
                    $bank_line_id = $acc->addline($this->datep, $this->paymenttype, $this->label, -abs($this->amount), '', '', $user);
            	  
                    // Mise a jour fk_bank dans llx_tva. On connait ainsi la ligne de tva qui a généré l'écriture bancaire
                    if ($bank_line_id)
					{
                        $this->update_fk_bank($bank_line_id);
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
                $this->db->commit();
                return $this->id;
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
	
}
?>
