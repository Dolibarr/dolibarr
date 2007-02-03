<?php
/* Copyright (C) 2002      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * $Source$
 */

/**
        \file       htdocs/chargesociales.class.php
		\ingroup    facture
		\brief      Fichier de la classe des charges sociales
		\version    $Revision$
*/


/**     \class      ChargeSociales
		\brief      Classe permettant la gestion des paiements des charges
                    La tva collectée n'est calculée que sur les factures payées.
*/
class ChargeSociales
{
    var $db;

    var $id;
    var $date_ech;
    var $lib;
    var $type;
    var $type_libelle;
    var $amount;
    var $paye;
    var $periode;


    function ChargeSociales($DB)
    {
        $this->db = $DB;

        return 1;
    }

    /**
     *   \brief      Retrouve et charge une charge sociale
     *   \return     int     1 si trouve, 0 sinon
     */
    function fetch($id)
    {
        $sql = "SELECT cs.rowid,".$this->db->pdate("cs.date_ech")." as date_ech,";
        $sql.= " cs.libelle as lib, cs.fk_type, cs.amount, cs.paye, ".$this->db->pdate("cs.periode")." as periode,";
		$sql.= " c.libelle";
        $sql.= " FROM ".MAIN_DB_PREFIX."chargesociales as cs, ".MAIN_DB_PREFIX."c_chargesociales as c";
        $sql.= " WHERE cs.fk_type = c.id";
        $sql.= " AND cs.rowid = ".$id;

		dolibarr_syslog("ChargesSociales::fetch sql=".$sql);
        $resql=$this->db->query($sql);
		if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id             = $obj->rowid;
                $this->date_ech       = $obj->date_ech;
                $this->lib            = $obj->lib;
                $this->type           = $obj->fk_type;
                $this->type_libelle   = $obj->libelle;
                $this->amount         = $obj->amount;
                $this->paye           = $obj->paye;
                $this->periode        = $obj->periode;

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
            $this->error=$this->db->error();
            return -1;
        }
    }


    /**
     *      \brief      Crée une charge sociale
     *      \param      user    Utilisateur qui crée
     *      \return     int     <0 si erreur, >0 si ok
     */
    function create($user)
    {
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."chargesociales (fk_type, libelle, date_ech, periode, amount)";
		$sql.= " VALUES (".$this->type.",'".addslashes($this->lib)."',";
		$sql.= "'".$this->date_ech."','".$this->periode."',";
		$sql.= "'".$this->amount."'";
		$sql.= ')';
		
		dolibarr_syslog("ChargesSociales::create sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}	

		
    /**
     *      \brief      Efface un charge sociale
     *      \param      user    Utilisateur qui crée le paiement
     *      \return     int     <0 si erreur, >0 si ok
     */
    function delete($user)
    {
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."chargesociales where rowid='".$this->id."'";

		dolibarr_syslog("ChargesSociales::delete sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}
	
    function solde($year = 0)
    {
        $sql = "SELECT sum(f.amount) as amount";
        $sql .= " FROM ".MAIN_DB_PREFIX."chargesociales as f WHERE paye = 0";

        if ($year) {
            $sql .= " AND f.datev >= '$y-01-01' AND f.datev <= '$y-12-31' ";
        }

        $result = $this->db->query($sql);

        if ($result) {
            if ($this->db->num_rows()) {
                $obj = $this->db->fetch_object();
                return $obj->amount;
            } else {
                return 0;
            }

            $this->db->free();

        } else {
            print $this->db->error();
            return -1;
        }
    }

	/**
	*    \brief      Tag la charge comme payée complètement
	*    \param      rowid       id de la ligne a modifier
	*/
	function set_payed($rowid)
    {
      $sql = "UPDATE ".MAIN_DB_PREFIX."chargesociales set paye=1 WHERE rowid = ".$rowid;
      $return = $this->db->query( $sql);
    }

	/**
	 *    \brief      Retourne le libellé du statut d'une charge (impayé, payée)
	 *    \param      mode          0=libellé long, 1=libellé court, 2=Picto + Libellé court, 3=Picto, 4=Picto + Libellé long
	 *    \return     string        Libelle
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->paye,$mode);
	}

	/**
	 *    	\brief      Renvoi le libellé d'un statut donné
	 *    	\param      statut        	Id statut
	 *    	\param      mode          	0=libellé long, 1=libellé court, 2=Picto + Libellé court, 3=Picto, 4=Picto + Libellé long, 5=Libellé court + Picto
	 *    	\return     string        	Libellé du statut
	 */
	function LibStatut($statut,$mode=0)
	{
		global $langs;
		$langs->load('customers');

		if ($mode == 0)
		{
			if ($statut ==  0) return $langs->trans("Unpayed");
			if ($statut ==  1) return $langs->trans("Payed");
		}
		if ($mode == 1)
		{
			if ($statut ==  0) return $langs->trans("Unpayed");
			if ($statut ==  1) return $langs->trans("Payed");
		}
		if ($mode == 2)
		{
			if ($statut ==  0) return img_picto($langs->trans("Unpayed"), 'statut1').' '.$langs->trans("Unpayed");
			if ($statut ==  1) return img_picto($langs->trans("Payed"), 'statut6').' '.$langs->trans("Payed");
		}
		if ($mode == 3)
		{
			if ($statut ==  0) return img_picto($langs->trans("Unpayed"), 'statut1');
			if ($statut ==  1) return img_picto($langs->trans("Payed"), 'statut6');
		}
		if ($mode == 4)
		{
			if ($statut ==  0) return img_picto($langs->trans("Unpayed"), 'statut1').' '.$langs->trans("Unpayed");
			if ($statut ==  1) return img_picto($langs->trans("Payed"), 'statut6').' '.$langs->trans("Payed");
		}
		if ($mode == 5)
		{
			if ($statut ==  0) return $langs->trans("Unpayed").' '.img_picto($langs->trans("Unpayed"), 'statut1');
			if ($statut ==  1) return $langs->trans("Payed").' '.img_picto($langs->trans("Payed"), 'statut6');
		}

		return "Error, mode/status not found";
	}


	/**
	 *    	\brief      Renvoie nom clicable (avec eventuellement le picto)
	 *		\param		withpicto		Inclut le picto dans le lien
	 *		\return		string			Chaine avec URL
	 */
	function getNomUrl($withpicto=0)
	{
		global $langs;
		
		$result='';
		
		$lien = '<a href="'.DOL_URL_ROOT.'/compta/sociales/charges.php?id='.$this->id.'">';
		$lienfin='</a>';

		if ($withpicto) $result.=($lien.img_object($langs->trans("ShowBill"),'bill').$lienfin.' ');
		$result.=$lien.$this->lib.$lienfin;
		return $result;
	}
	
}


/**     \class      PaiementCharge
		\brief      Classe permettant la gestion des paiements des charges
*/
class PaiementCharge
{
    var $db;
    
    var $id;
    var $chid;
    var $paiementtype;
    var $datepaye;
    var $amounts;
    var $num_paiement;
    var $note;

	
    function PaiementCharge($DB)
	{
        $this->db = $DB;
        return 1;
    }

    /**
     *      \brief      Creation d'un paiement de charge sociale dans la base
     *      \param      user    Utilisateur qui crée le paiement
     *      \return     int     <0 si KO, id du paiement crée si OK
     */
    function create($user)
	{
        $error = 0;

        $this->db->begin();

		$total = 0;
		foreach ($this->amounts as $key => $value)
		{
			$facid = $key;
			$value = trim($value);
			$amount = round(price2num($value), 2);   // Un round est ok si nb avec '.'
			if (is_numeric($amount)) $total += $amount;
		}
		$total = price2num($total);

		if ($total > 0)
		{
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."paiementcharge (fk_charge, datec, datep, amount, fk_typepaiement, num_paiement, note, fk_user_creat)";
			$sql .= " VALUES ($this->chid, now(), $this->datepaye, '$total', $this->paiementtype, '$this->num_paiement', '$this->note', $user->id)";

			dolibarr_syslog("PaiementCharges::create sql=".$sql);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."paiementcharge");
			}
			else
			{
				$error++;
			}

		}

		if ($total > 0 && ! $error)
		{
			$this->db->commit();
			return $this->id;
		}
		else
		{
			$this->error=$this->db->error();
			dolibarr_syslog("PaiementCharges::create ".$this->error);
			$this->db->rollback();
			return -1;
		}
    }

    /**
     *      \brief      Mise a jour du lien entre le paiement de  charge et la ligne dans llx_bank générée
     *      \param      id_bank         Id de la banque
     *      \return     int             1 ou 0
     */
    function update_fk_bank($id_bank)
	{
        $sql = "UPDATE llx_paiementcharge set fk_bank = ".$id_bank." where rowid = ".$this->id;
        $result = $this->db->query($sql);
        if ($result) 
        {	    
        	return 1;
        }
        else
        {
            $this->error=$this->db->error()." sql=".$sql;
        	return 0;
        }
    }
}


?>
