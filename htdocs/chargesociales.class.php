<?php
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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


/**     \class      PaiementCharge
		\brief      Classe permettant la gestion des paiements des charges
*/
class PaiementCharge {
    var $db;
    
    var $id;
    var $chid;
    var $paiementtype;
    var $datepaye;
    var $amounts;
    var $num_paiement;
    var $note;

    function PaiementCharge($DB) {
        $this->db = $DB;

        return 1;
    }

    function create($user) {
        $sql_err = 0;
        /*
         *  Insertion dans la base
         */
        if ($this->db->begin())
        {
            $total = 0;
            foreach ($this->amounts as $key => $value)
            {
                $facid = $key;
                $value = trim($value);
                $amount = round(ereg_replace(",",".",$value), 2);

                if (is_numeric($amount))
                {
                    $total += $amount;
                }
            }
            $total = round(ereg_replace(",",".",$total), 2);
            if ($total > 0)
            {
                $sql = "INSERT INTO ".MAIN_DB_PREFIX."paiementcharge (fk_charge, datec, datep, amount, fk_typepaiement, num_paiement, note, fk_user_creat)";
                $sql .= " VALUES ($this->chid, now(), $this->datepaye, '$total', $this->paiementtype, '$this->num_paiement', '$this->note', $user->id)";

                if ( $this->db->query($sql) )
                {

                    $this->id = $this->db->last_insert_id();

                }
                else
                {
                    $sql_err++;
                    print "Error: $sql : ".$this->db->error();
                }

            }

            if ( $total > 0 && $sql_err == 0 )
            {
                $this->db->commit();
                return $this->id;
            }
            else
            {
                $this->db->rollback();
                return -1;
            }

        }
    }

    /*
    * Mise a jour du lien entre le paiement de  charge et la ligne dans llx_bank générée
    *
    */
    function update_fk_bank($id_bank) {
        $sql = "UPDATE llx_paiementcharge set fk_bank = ".$id_bank." where rowid = ".$this->id;
        $result = $this->db->query($sql);
        if ($result) 
        {	    
        	return 1;
        }
        else
        {
            print $this->db->error() ."<br>".$sql;
        	return 0;
        }
    }
}


/**     \class      ChargeSociales
		\brief      Classe permettant la gestion des paiements des charges
                    La tva collectée n'est calculée que sur les factures payées.
*/
class ChargeSociales {
    var $db;

    var $id;
    var $date_ech;
    var $date_pai;
    var $lib;
    var $type;
    var $type_libelle;
    var $amount;
    var $paye;
    var $periode;

    function ChargeSociales($DB) {
        $this->db = $DB;

        return 1;
    }

    /*
    * Retrouve et charge une charge sociale
    * Retour: 1 si trouve, 0 sinon
    */
    function fetch($id) {
        $sql = "SELECT cs.rowid,".$this->db->pdate("cs.date_ech")." as date_ech,".$this->db->pdate("cs.date_pai")." as date_pai";
        $sql .=", cs.libelle as lib, cs.fk_type, cs.amount, cs.paye, ".$this->db->pdate("cs.periode")." as periode, c.libelle";
        $sql .= " FROM ".MAIN_DB_PREFIX."chargesociales as cs, ".MAIN_DB_PREFIX."c_chargesociales as c";
        $sql .= " WHERE cs.fk_type = c.id";
        $sql .=" AND cs.rowid = ".$id;

        if ($this->db->query($sql))
        {
            if ($this->db->num_rows())
            {
                $obj = $this->db->fetch_object();

                $this->id             = $obj->rowid;
                $this->date_ech       = $obj->date_ech;
                $this->date_pai       = $obj->date_pai;
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
            $this->db->free();
        }
        else
        {
            print $this->db->error();
            return 0;
        }
    }

    function solde($year = 0) {

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
   * Tag la charge comme payée complètement
   *
   */
  function set_payed($rowid)
    {
      $sql = "UPDATE ".MAIN_DB_PREFIX."chargesociales set paye=1 WHERE rowid = $rowid ;";
      $return = $this->db->query( $sql);
    }


  /**
   * Renvoi le staut sous forme de libellé d'une charge
   *
   */
    function getLibStatut() {
        if ($this->paye == 0) { return "Non Payé"; }
        else { return "Payé"; }
    }
}
/*
* $Id$
* $Source$
*/
?>
