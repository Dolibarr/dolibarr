<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Christophe Combelles <ccomb@free.fr>
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
        \file       htdocs/compta/bank/account.class.php
        \ingroup    banque
        \brief      Fichier de la classe des comptes bancaires
        \version    $Revision$
*/


/**
        \class      Account
        \brief      Classe permettant la gestion des comptes bancaires
*/

class Account
{
    var $rowid;

    var $label;
    var $type;
    var $bank;
    var $clos;
    var $code_banque;
    var $code_guichet;
    var $number;
    var $cle_rib;
    var $bic;
    var $iban_prefix;
    var $proprio;
    var $adresse_proprio;
    var $type_lib=array();


    function Account($DB, $rowid=0)
    {
        global $langs;

        $this->db = $DB;
        $this->rowid = $rowid;

        $this->clos = 0;
        $this->solde = 0;

        $this->type_lib[0]=$langs->trans("BankType0");
        $this->type_lib[1]=$langs->trans("BankType1");
        $this->type_lib[2]=$langs->trans("BankType2");

        return 1;
    }

    /*
     * Efface une entree dans la table ".MAIN_DB_PREFIX."bank
     */
    function deleteline($rowid)
    {
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."bank_class WHERE lineid=$rowid";
        $result = $this->db->query($sql);

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."bank WHERE rowid=$rowid";
        $result = $this->db->query($sql);

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."bank_url WHERE fk_bank=$rowid";
        $result = $this->db->query($sql);
    }

    /**
     *      \brief      Ajoute lien entre ecriture bancaire et sources
     *      \param      line_id     Id ecriture bancaire
     *      \param      url_id      Id parametre url
     *      \param      url         Url
     *      \param      type        Type de lien (payment, company, member, ...)
     *      \return     int         <0 si ko, id line si ok
     */
    function add_url_line($line_id, $url_id, $url, $label, $type='')
    {
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."bank_url (fk_bank, url_id, url, label, type)";
        $sql .= " VALUES ('$line_id', '$url_id', '$url', '$label', '$type')";

        if ($this->db->query($sql))
        {
            $rowid = $this->db->last_insert_id(MAIN_DB_PREFIX."bank_url");
            return $rowid;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }

    /*
     *
     */
    function get_url($line_id)
    {
        $lines = array();
        $sql = "SELECT fk_bank, url_id, url, label FROM ".MAIN_DB_PREFIX."bank_url WHERE fk_bank = $line_id";
        $result = $this->db->query($sql);

        if ($result)
        {
            $i = 0;
            $num = $this->db->num_rows();
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($result);
                $lines[$i][0] = $obj->url;
                $lines[$i][1] = $obj->url_id;
                $lines[$i][2] = $obj->label;
                $i++;
            }
            return $lines;
        }
    }

    /**
     *    \brief      Ajoute une entree dans la table ".MAIN_DB_PREFIX."bank
     *    \return     int     rowid de l'entrée ajoutée, <0 si erreur
     */
    function addline($date, $oper, $label, $amount, $num_chq='', $categorie='', $user='')
    {
        dolibarr_syslog("Account::addline: $date, $oper, $label, $amount, $num_chq, $categorie, $user");
        if ($this->rowid)
        {
            $this->db->begin();

            switch ($oper)
            {
                case 1:
                $oper = 'TIP';
                break;
                case 2:
                $oper = 'VIR';
                break;
                case 3:
                $oper = 'PRE';
                break;
                case 4:
                $oper = 'LIQ';
                break;
                case 5:
                $oper = 'VAD';
                break;
                case 6:
                $oper = 'CB';
                break;
                case 7:
                $oper = 'CHQ';
                break;
            }

            $datev = $date;

            $sql = "INSERT INTO ".MAIN_DB_PREFIX."bank (datec, dateo, datev, label, amount, fk_user_author, num_chq,fk_account, fk_type)";
            $sql.= " VALUES (now(), '".$date."', '$datev', '$label', '" . ereg_replace(',','.',$amount) . "', '".$user->id."' ,'$num_chq', '".$this->rowid."', '$oper')";

            if ($this->db->query($sql))
            {
                $rowid = $this->db->last_insert_id(MAIN_DB_PREFIX."bank");
                if ($categorie)
                {
                    $sql = "INSERT INTO ".MAIN_DB_PREFIX."bank_class (lineid, fk_categ) VALUES ('$rowid', '$categorie')";
                    $result = $this->db->query($sql);
                    if (! $result)
                    {
                        $this->db->rollback();
                        $this->error=$this->db->error();
                        return -3;
                    }
                }
                $this->db->commit();
                return $rowid;
            }
            else
            {
                $this->db->rollback();
                $this->error=$this->db->error();
                return -2;
            }
        }
        else 
        {
            $this->error="Account::addline rowid not defined";
            return -1;
        }
    }

    /*
     *      \brief          Creation du compte bancaire en base
     *      \return         int     < 0 si erreur, > 0 si ok
     */
    function create()
    {
        global $langs;

        // Chargement librairie pour acces fonction controle RIB
        require_once DOL_DOCUMENT_ROOT . '/compta/bank/bank.lib.php';

        if (! verif_rib($this->code_banque,$this->code_guichet,$this->number,$this->cle_rib,$this->iban_prefix)) {
            $this->error="Le contrôle de la clé indique que les informations de votre compte bancaire sont incorrectes.";
            return 0;
        }

        if (! $pcgnumber) $pcgnumber="51";

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."bank_account (datec, label, account_number) values (now(),'$this->label','$pcgnumber');";
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->affected_rows($resql))
            {
                $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."bank_account");
                if ( $this->update() )
                {
                    $sql = "INSERT INTO ".MAIN_DB_PREFIX."bank (datec, label, amount, fk_account,datev,dateo,fk_type,rappro) ";
                    $sql .= " VALUES (now(),'".$langs->trans("Balance")."','" . ereg_replace(',','.',$this->solde) . "','$this->id','".$this->db->idate($this->date_solde)."','".$this->db->idate($this->date_solde)."','SOLD',1);";
                    $this->db->query($sql);
                }
                return $this->id;
            }
        }
        else
        {
            if ($this->db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
                $this->error=$langs->trans("ErrorBankLabelAlreadyExists");
                return -1;
            }
            else {
                $this->error=$this->db->error();
                return -2;
            }
        }
    }

    /*
     *    \brief      Mise a jour compte
     *    \param      user        Object utilisateur qui modifie
     */
    function update($user='')
    {
        // Chargement librairie pour acces fonction controle RIB
        require_once DOL_DOCUMENT_ROOT . '/compta/bank/bank.lib.php';

        if (! verif_rib($this->code_banque,$this->code_guichet,$this->number,$this->cle_rib,$this->iban_prefix)) {
            $this->error="Le contrôle de la clé indique que les informations de votre compte bancaire sont incorrectes.";
            return 0;
        }

        if (! $this->label) $this->label = "???";

        $sql = "UPDATE ".MAIN_DB_PREFIX."bank_account SET ";

        $sql .= " bank = '" .$this->bank ."'";
        $sql .= ",label = '".$this->label ."'";

        $sql .= ",code_banque='".$this->code_banque."'";
        $sql .= ",code_guichet='".$this->code_guichet."'";
        $sql .= ",number='".$this->number."'";
        $sql .= ",cle_rib='".$this->cle_rib."'";
        $sql .= ",bic='".$this->bic."'";
        $sql .= ",iban_prefix = '".$this->iban_prefix."'";
        $sql .= ",domiciliation='".$this->domiciliation."'";
        $sql .= ",proprio = '".$this->proprio."'";
        $sql .= ",adresse_proprio = '".$this->adresse_proprio."'";
        $sql .= ",courant = ".$this->courant;
        $sql .= ",clos = ".$this->clos;

        $sql .= " WHERE rowid = ".$this->id;

        $result = $this->db->query($sql);

        if ($result)
        {
            return 1;
        }
        else
        {
            dolibarr_print_error($this->db);
            return 0;
        }
    }

    /*
     *    \brief      Charge en memoire depuis la base le compte
     */
    function fetch($id)
    {
        $this->id = $id;
        $sql = "SELECT rowid, label, bank, number, courant, clos, code_banque, code_guichet, cle_rib, bic, iban_prefix, domiciliation, proprio, adresse_proprio FROM ".MAIN_DB_PREFIX."bank_account";
        $sql .= " WHERE rowid  = ".$id;

        $result = $this->db->query($sql);

        if ($result)
        {
            if ($this->db->num_rows($result))
            {
                $obj = $this->db->fetch_object($result);

                $this->label         = $obj->label;
                $this->type          = $obj->courant;
                $this->courant       = $obj->courant;
                $this->bank          = $obj->bank;
                $this->clos          = $obj->clos;
                $this->code_banque   = $obj->code_banque;
                $this->code_guichet  = $obj->code_guichet;
                $this->number        = $obj->number;
                $this->cle_rib       = $obj->cle_rib;
                $this->bic           = $obj->bic;
                $this->iban_prefix   = $obj->iban_prefix;
                $this->domiciliation = $obj->domiciliation;
                $this->proprio       = $obj->proprio;
                $this->adresse_proprio = $obj->adresse_proprio;
            }
            $this->db->free($result);
        }
        else
        {
            dolibarr_print_error($this->db);
        }
    }


    /*
     *    \brief      Efface le compte
     */
    function delete()
    {
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."bank_account";
        $sql .= " WHERE rowid  = ".$this->rowid;
        $result = $this->db->query($sql);
        if ($result) {
            return 1;
        }
        else {
            dolibarr_print_error($this->db);
            return -1;
        }
    }


    /*
     *    \brief      Renvoi si un compte peut etre supprimer ou non (sans mouvements)
     *    \return     boolean     vrai si peut etre supprimé, faux sinon
     */
    function can_be_deleted()
    {
        $can_be_deleted=false;

        $sql = "SELECT COUNT(rowid) as nb";
        $sql.= " FROM ".MAIN_DB_PREFIX."bank";
        $sql.= " WHERE fk_account=".$this->id;
        $resql = $this->db->query($sql);
        if ($resql) {
            $obj=$this->db->fetch_object($resql);
            if ($obj->nb <= 1) $can_be_deleted=true;    // Juste le solde
        }
        else {
            dolibarr_print_error($this->db);
        }
        return $can_be_deleted;
    }


    /*
     *
     */
    function error()
    {
        return $this->error;
    }

    /*
     *
     */
    function solde()
    {
        $sql = "SELECT sum(amount) FROM ".MAIN_DB_PREFIX."bank WHERE fk_account=$this->id AND dateo <=" . $this->db->idate(time() );

        $result = $this->db->query($sql);

        if ($result)
        {
            if ($this->db->num_rows())
            {
                $solde = $this->db->result(0,0);

                return $solde;
            }
            $this->db->free();
        }
    }

    /*
     *
     */
    function datev_next($rowid)
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."bank SET ";

        $sql .= " datev = adddate(datev, interval 1 day)";

        $sql .= " WHERE rowid = $rowid";

        $result = $this->db->query($sql);

        if ($result)
        {
            if ($this->db->affected_rows())
            {
                return 1;
            }
        }
        else
        {
            dolibarr_print_error($this->db);
            return 0;
        }
    }
 
    /*
     *
     */
    function datev_previous($rowid)
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."bank SET ";

        $sql .= " datev = adddate(datev, interval -1 day)";

        $sql .= " WHERE rowid = $rowid";

        $result = $this->db->query($sql);

        if ($result)
        {
            if ($this->db->affected_rows())
            {
                return 1;
            }
        }
        else
        {
            dolibarr_print_error($this->db);
            return 0;
        }
    }


    /**
     *      \brief      Charge indicateurs this->nbtodo et this->nbtodolate de tableau de bord
     *      \param      user        Objet user
     *      \return     int         <0 si ko, >0 si ok
     */
    function load_board($user)
    {
        global $conf;
        
        if ($user->societe_id) return -1;   // protection pour eviter appel par utilisateur externe

        $this->nbtodo=$this->nbtodolate=0;
        $sql = "SELECT b.rowid,".$this->db->pdate("b.datev")." as datefin";
        $sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
        $sql.= " WHERE b.rappro=0";
        $resql=$this->db->query($sql);
        if ($resql)
        {
            while ($obj=$this->db->fetch_object($resql))
            {
                $this->nbtodo++;
                if ($obj->datefin < (time() - $conf->bank->rappro->warning_delay)) $this->nbtodolate++;
            }
            return 1;
        }
        else 
        {
            dolibarr_print_error($this->db);
            $this->error=$this->db->error();
            return -1;
        }
    }

}

?>
