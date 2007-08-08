<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
        \file       htdocs/bon-prelevement.class.php
        \ingroup    prelevement
        \brief      Fichier de la classe des bons de prélévements
        \version    $Revision$
*/


require_once (DOL_DOCUMENT_ROOT."/facture.class.php");
require_once (DOL_DOCUMENT_ROOT."/societe.class.php");

class BonPrelevement
{
    var $db;

    var $date_echeance;
    var $raison_sociale;
    var $reference_remise;
    var $emetteur_code_guichet;
    var $emetteur_numero_compte;
    var $emetteur_code_etablissement;
    var $total;
    var $_fetched;

    function BonPrelevement($DB, $filename='')
    {
        $error = 0;
        $this->db = $DB;

        $this->filename=$filename;
        
        $this->date_echeance = time();
        $this->raison_sociale = "";
        $this->reference_remise = "";

        $this->emetteur_code_guichet = "";
        $this->emetteur_numero_compte = "";
        $this->emetteur_code_etablissement = "";

        $this->factures = array();

        $this->numero_national_emetteur = "";

        $this->methodes_trans = array();

        $this->methodes_trans[0] = "Internet";

        $this->_fetched = 0;

        return 1;
    }

    /**
     *
     *
     */
    function AddFacture($facture_id, $client_id, $client_nom, $amount, $code_banque, $code_guichet, $number)
    {
        $result = 0;
        $ligne_id = 0;

        $result = $this->AddLigne($ligne_id, $client_id, $client_nom,
        $amount, $code_banque, $code_guichet, $number);

        if ($result == 0)
        {
            if ($ligne_id > 0)
            {
                $sql = "INSERT INTO ".MAIN_DB_PREFIX."prelevement_facture ";
                $sql .= " (fk_facture,fk_prelevement_lignes)";
                $sql .= " VALUES (".$facture_id.",".$ligne_id.")";

                if ($this->db->query($sql))
                {
                    $result = 0;
                }
                else
                {
                    $result = -1;
                    dolibarr_syslog("BonPrelevement::AddFacture Erreur $result");
                }
            }
            else
            {
                $result = -2;
                dolibarr_syslog("BonPrelevement::AddFacture Erreur $result");
            }
        }
        else
        {
            $result = -3;
            dolibarr_syslog("BonPrelevement::AddFacture Erreur $result");
        }

        return $result;

    }

    /**
     *
     *
     */
    function AddLigne(&$ligne_id, $client_id, $client_nom, $amount, $code_banque, $code_guichet, $number)
    {
        $result = -1;
        $concat = 0;

        if ($concat == 1)
        {
            /*
             * On aggrège les lignes
             */
            $sql = "SELECT rowid FROM  ".MAIN_DB_PREFIX."prelevement_lignes";
            $sql .= " WHERE fk_prelevement_bons".$this->id;
            $sql .= " AND fk_soc       =".$client_id;
            $sql .= " AND code_banque  ='".$code_banque."'";
            $sql .= " AND code_guichet ='".$code_guichet."'";
            $sql .= " AND number       ='".$number."'";

            if ($this->db->query($sql))
            {
                $num = $this->db->num_rows();
            }
            else
            {
                $result = -1;
            }
        }
        else
        {
            /*
             * Pas de d'agrégation
             */
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."prelevement_lignes (fk_prelevement_bons";
            $sql .= " , fk_soc , client_nom ";
            $sql .= " , amount";
            $sql .= " , code_banque , code_guichet , number)";

            $sql .= " VALUES (".$this->id;
            $sql .= ",".$client_id.",'".addslashes($client_nom)."'";
            $sql .= ",'".price2num($amount)."'";
            $sql .= ", '$code_banque', '$code_guichet', '$number')";

            if ($this->db->query($sql))
            {
                $ligne_id = $this->db->last_insert_id(MAIN_DB_PREFIX."prelevement_lignes");
                $result = 0;
            }
            else
            {
                dolibarr_syslog("BonPrelevement::AddLigne Erreur -2");
                $result = -2;
            }

        }

        return $result;
    }

    /**
     *
     *
     */
    function ReadError($error)
    {
        $errors = array();

        $errors[1027] = "Date invalide";

        return $errors[abs($error)];
    }

    /**
     *
     *
     */
    function Fetch($rowid)
    {
        $sql = "SELECT p.rowid, p.ref, p.amount, p.note, p.credite";
        $sql .= ",".$this->db->pdate("p.datec")." as dc";
        $sql .= ",".$this->db->pdate("p.date_trans")." as date_trans";
        $sql .= " , method_trans, fk_user_trans";
        $sql .= ",".$this->db->pdate("p.date_credit")." as date_credit";
        $sql .= " , fk_user_credit";
        $sql .= " , statut";
        $sql .= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";

        $sql .= " WHERE p.rowid=".$rowid;

        $result=$this->db->query($sql);
        if ($result)
        {
            if ($this->db->num_rows($result))
            {
                $obj = $this->db->fetch_object();

                $this->id                 = $obj->rowid;
                $this->ref                = $obj->ref;
                $this->amount             = $obj->amount;
                $this->note               = stripslashes($obj->note);
                $this->datec              = $obj->dc;
                $this->credite            = $obj->credite;

                $this->date_trans         = $obj->date_trans;
                $this->method_trans       = $obj->method_trans;
                $this->user_trans         = $obj->fk_user_trans;

                $this->date_credit        = $obj->date_credit;
                $this->user_credit        = $obj->fk_user_credit;

                $this->statut             = $obj->statut;

                $this->_fetched = 1;

                return 0;
            }
            else
            {
                dolibarr_syslog("BonPrelevement::Fetch Erreur aucune ligne retournée");
                return -1;
            }
        }
        else
        {
            dolibarr_syslog("BonPrelevement::Fetch Erreur ");
            dolibarr_syslog($sql);
            return -2;
        }
    }

    /**
     *
     *
     */
    function set_credite()
    {
        global $user;
        
        $error == 0;

        if ($this->db->begin())
        {
            $sql = " UPDATE ".MAIN_DB_PREFIX."prelevement_bons ";
            $sql .= " SET credite = 1";
            $sql .= " WHERE rowid=".$this->id;

            $result=$this->db->query($sql);
            if (! $result)
            {
                dolibarr_syslog("bon-prelevement::set_credite Erreur 1");
                $error++;
            }

            if ($error == 0)
            {
                $facs = array();
                $facs = $this->_get_list_factures();

                for ($i = 0 ; $i < sizeof($facs) ; $i++)
                {
                    /* Tag la facture comme impayée */
                    dolibarr_syslog("BonPrelevement::set_credite set_payed fac ".$facs[$i]);
                    $fac = new Facture($this->db);
                    $fac->fetch($facs[$i]);
                    $result = $fac->set_payed($user);
                }
            }

            if ($error == 0)
            {

                $sql = " UPDATE ".MAIN_DB_PREFIX."prelevement_lignes ";
                $sql .= " SET statut  = 2";
                $sql .= " WHERE fk_prelevement_bons=".$this->id;

                if (! $this->db->query($sql))
                {
                    dolibarr_syslog("BonPrelevement::set_credite Erreur 1");
                    $error++;
                }
            }

            /*
             * Fin de la procédure
             *
             */
            if ($error == 0)
            {
                $this->db->commit();
                return 0;
            }
            else
            {

                $this->db->rollback();
                dolibarr_syslog("BonPrelevement::set_credite ROLLBACK ");

                return -1;
            }


        }
        else
        {

            dolibarr_syslog("BonPrelevement::set_credite Ouverture transaction SQL impossible ");
            return -2;
        }
    }

    /**
     *
     *
     */
    function set_infocredit($user, $date)
    {
        $error == 0;

        if ($this->_fetched == 1)
        {
            if ($date >= $this->date_trans)
            {
                if ($this->db->begin())
                {
                    $sql = " UPDATE ".MAIN_DB_PREFIX."prelevement_bons ";
                    $sql .= " SET fk_user_credit = ".$user->id;
                    $sql .= " , statut = 2";
                    $sql .= " , date_credit='".$this->db->idate($date)."'";
                    $sql .= " WHERE rowid=".$this->id;
                    $sql .= " AND statut = 1";

                    if ($this->db->query($sql))
                    {
                        $subject = "Crédit prélèvement ".$this->ref." à la banque";
                        $message = "Le bon de prélèvement ".$this->ref;
                        $message .= " a été crédité par la banque.\n";
                        $message .= "Date crédit : ".dolibarr_print_date($date,'dayhour');

                        $this->Notify($user, "cr", $subject, $message);
                    }
                    else
                    {
                        dolibarr_syslog("BonPrelevement::set_infocredit Erreur 1");
                        $error++;
                    }

                    /*
                     * Fin de la procédure
                     *
                     */
                    if ($error == 0)
                    {
                        $this->db->commit();
                        return 0;
                    }
                    else
                    {
                        $this->db->rollback();
                        dolibarr_syslog("bon-prelevment::set_infocredit ROLLBACK ");
                        return -1;
                    }
                }
                else
                {
                    dolibarr_syslog("bon-prelevement::set_infocredit Ouverture transaction SQL impossible ");
                    return -1025;
                }
            }
            else
            {
                dolibarr_syslog("bon-prelevment::set_infocredit 1027 Date de credit < Date de trans ");
                return -1027;
            }
        }
        else
        {
            return -1026;
        }
    }

    /**
     *
     *
     */
    function set_infotrans($user, $date, $method)
    {
        $error == 0;
        dolibarr_syslog("bon-prelevement::set_infotrans Start",LOG_INFO);
        if ($this->db->begin())
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX."prelevement_bons ";
            $sql .= " SET fk_user_trans = ".$user->id;
            $sql .= " , date_trans='".$this->db->idate($date)."'";
            $sql .= " , method_trans=".$method;
            $sql .= " , statut = 1";
            $sql .= " WHERE rowid=".$this->id;
            $sql .= " AND statut = 0";

            if ($this->db->query($sql))
            {
                $this->method_trans = $method;

                $subject = "Transmission du prélèvement ".$this->ref." à la banque";
                $message = "Le bon de prélèvement ".$this->ref;
                $message .= " a été transmis à la banque par ".$user->prenom. " ".$user->nom;
                $message .= "\n\n";
                $message .= "\nMontant : ".price($this->amount);
                $message .= "\nMéthode : ".$this->methodes_trans[$this->method_trans];
                $message .= "\nDate  : ".dolibarr_print_date($date,'day');

                $this->Notify($user,"tr", $subject, $message, 1);
            }
            else
            {
                dolibarr_syslog("bon-prelevement::set_infotrans Erreur 1", LOG_ERR);
                dolibarr_syslog($this->db->error());
                $error++;
            }

            /*
             * Fin de la procédure
             *
             */
            if ($error == 0)
            {
                $this->db->commit();
                return 0;
            }
            else
            {
                $this->db->rollback();
                dolibarr_syslog("BonPrelevement::set_infotrans ROLLBACK", LOG_ERR);

                return -1;
            }
        }
        else
        {

            dolibarr_syslog("BonPrelevement::set_infotrans Ouverture transaction SQL impossible", LOG_CRIT);
            return -2;
        }
    }

    /**
     *
     *
     */
    function Notify($user, $action, $subject, $message, $joinfile=0)
    {
        $message .= "\n\n--\n";
        $message .= "Ceci est un message automatique envoyé par Dolibarr";

        $sql = "SELECT u.name, u.firstname, u.email";
        $sql .= " FROM ".MAIN_DB_PREFIX."user as u";
        $sql .= " , ".MAIN_DB_PREFIX."prelevement_notifications as pn";
        $sql .= " WHERE pn.action ='".$action."'";
        $sql .= " AND u.rowid = pn.fk_user;";

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($resql);

                require_once(DOL_DOCUMENT_ROOT."/lib/CMailFile.class.php");

                $sendto = $obj->firstname . " " .$obj->name . "<".$obj->email.">";
                $from = $user->prenom . " " .$user->nom . "<".$user->email.">";
                $arr_file = array();
                $arr_mime = array();
                $arr_name = array();
                $msgishtml=0;
                
                if ($joinfile == 1)
                {
                    $arr_file = array(DOL_DATA_ROOT.'/prelevement/bon/'.$this->ref.'.ps');
                    $arr_mime = array("application/ps");
                    $arr_name = array($this->ref.".ps");
                }

                $mailfile = new CMailFile($subject,$sendto,$from,$message,
                							$arr_file,$arr_mime,$arr_name,
                							'', '', 0, $msgishtml);

                $result=$mailfile->sendfile();

                $i++;
            }
            $this->db->free($resql);
        }
    }

    /**
     *    \brief      Recupére la liste des factures concernées
     */
    function _get_list_factures()
    {
        $arr = array();

        /*
         * Renvoie toutes les factures présente
         * dans un bon de prélèvement
         */
        $sql = "SELECT fk_facture";
        $sql .= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
        $sql .= " , ".MAIN_DB_PREFIX."prelevement_lignes as pl";
        $sql .= " , ".MAIN_DB_PREFIX."prelevement_facture as pf";
        $sql .= " WHERE pf.fk_prelevement_lignes = pl.rowid";
        $sql .= " AND pl.fk_prelevement_bons = p.rowid";
        $sql .= " AND p.rowid=".$this->id;

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);

            if ($num)
            {
                $i = 0;
                while ($i < $num)
                {
                    $row = $this->db->fetch_row($resql);
                    $arr[$i] = $row[0];
                    $i++;
                }
            }
            $this->db->free($resql);
        }
        else
        {
            dolibarr_syslog("Bon-Prelevement::_get_list_factures Erreur");
        }

        return $arr;
    }

    /**
     *
     *
     */
    function SommeAPrelever()
    {
        $sql = "SELECT sum(f.total_ttc)";
        $sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
        $sql .= " , ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";

        $sql .= " WHERE f.fk_statut = 1";
        $sql .= " AND f.rowid = pfd.fk_facture";
        $sql .= " AND f.paye = 0";
        $sql .= " AND pfd.traite = 0";
        $sql .= " AND f.total_ttc > 0";
        $sql .= " AND f.fk_mode_reglement = 3";

        $resql = $this->db->query($sql);

        if ( $resql )
        {
            $row = $this->db->fetch_row($resql);

            return $row[0];

            $this->db->free($resql);
        }
        else
        {
            $error = 1;
            dolibarr_syslog("BonPrelevement::SommeAPrelever Erreur -1");
            dolibarr_syslog($this->db->error());
        }
    }

    /**
     *      \brief      Renvoi nombre de factures a prélever
     *      \param      banque      bank
     *      \param      agence      agence
     *      \return     int         <O si erreur, sinon nbre de factures
     */
    function NbFactureAPrelever($banque=0,$agence=0)
    {
        $sql = "SELECT count(f.total_ttc)";
        $sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
        $sql .= " , ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
        $sql .= " , ".MAIN_DB_PREFIX."societe_rib as sr";

        $sql .= " WHERE f.fk_statut = 1";
        $sql .= " AND f.rowid = pfd.fk_facture";
        $sql .= " AND f.fk_soc = sr.fk_soc";
        $sql .= " AND f.paye = 0";
        $sql .= " AND pfd.traite = 0";
        $sql .= " AND f.total_ttc > 0";
        $sql .= " AND f.fk_mode_reglement = 3";

        if ($banque == 1)
        {
            $sql .= " AND sr.code_banque = '".PRELEVEMENT_CODE_BANQUE."'";
        }

        if ($agence == 1)
        {
            $sql .= " AND sr.code_guichet = '".PRELEVEMENT_CODE_GUICHET."'";
        }

        $resql = $this->db->query($sql);

        if ( $resql )
        {
            $row = $this->db->fetch_row($resql);

            $this->db->free($resql);

            return $row[0];
        }
        else
        {
            $this->error="BonPrelevement::SommeAPrelever Erreur -1 sql=".$this->db->error();
            dolibarr_syslog($this->error);
            return -1;
        }
    }

    /**
     *      \brief      Cree prelevement
     *      \return     int     <0 si ko, nbre de facture prélevé sinon
     */
    function Create($banque=0, $guichet=0)
    {
        global $conf;
        
        dolibarr_syslog("BonPrelevement::Create");

        require_once (DOL_DOCUMENT_ROOT."/bon-prelevement.class.php");
        require_once (DOL_DOCUMENT_ROOT."/facture.class.php");
        require_once (DOL_DOCUMENT_ROOT."/societe.class.php");
        require_once (DOL_DOCUMENT_ROOT."/paiement.class.php");

        $error = 0;

        $datetimeprev = time();

        $month = strftime("%m", $datetimeprev);
        $year = strftime("%Y", $datetimeprev);

        $user = new user($this->db, PRELEVEMENT_USER);

        /**
         * Lectures des factures
         */
        $factures = array();
        $factures_prev = array();
        $factures_result = array();

        if (! $error)
        {

            $sql = "SELECT f.rowid, pfd.rowid as pfdrowid, f.fk_soc";
            $sql .= ", pfd.code_banque, pfd.code_guichet, pfd.number, pfd.cle_rib";
            $sql .= ", pfd.amount";
            $sql .= ", s.nom";
            $sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
            $sql .= " , ".MAIN_DB_PREFIX."societe as s";
            $sql .= " , ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
            $sql .= " , ".MAIN_DB_PREFIX."societe_rib as sr";

            $sql .= " WHERE f.rowid = pfd.fk_facture";
            $sql .= " AND s.rowid = f.fk_soc";
            $sql .= " AND s.rowid = sr.fk_soc";
            $sql .= " AND f.fk_statut = 1";
            $sql .= " AND f.paye = 0";
            $sql .= " AND pfd.traite = 0";
            $sql .= " AND f.total_ttc > 0";
            $sql .= " AND f.fk_mode_reglement = 3"; // Mode prélèvement

            if ($banque == 1)
            {
                $sql .= " AND sr.code_banque = '".PRELEVEMENT_CODE_BANQUE."'";
            }
            if ($agence == 1)
            {
                $sql .= " AND sr.code_guichet = '".PRELEVEMENT_CODE_GUICHET."'";
            }

            $resql = $this->db->query($sql);

            if ($resql)
            {
                $num = $this->db->num_rows($resql);
                $i = 0;

                while ($i < $num)
                {
                    $row = $this->db->fetch_row($resql);
                    $factures[$i] = $row;
                    $i++;
                }
                $this->db->free($resql);
                dolibarr_syslog("$i factures à prélever");
            }
            else
            {
                $error = 1;
                dolibarr_syslog("Erreur -1");
                dolibarr_syslog($this->db->error());
            }
        }

        /*
         *
         * Verif des clients
         *
         */

        if (! $error)
        {
            /*
             * Vérification des RIB
             *
             */
            $i = 0;
            dolibarr_syslog("Début vérification des RIB");

            if (sizeof($factures) > 0)
            {
                foreach ($factures as $fac)
                {
                    $fact = new Facture($this->db);

                    if ($fact->fetch($fac[0]) >= 0)
                    {
                        $soc = new Societe($this->db);
                        if ($soc->fetch($fact->socid) >= 0)
                        {
                            if ($soc->verif_rib() == 1)
                            {
                                $factures_prev[$i] = $fac;
                                /* second tableau necessaire pour bon-prelevement */
                                $factures_prev_id[$i] = $fac[0];
                                $i++;
                            }
                            else
                            {
                                dolibarr_syslog("Erreur de RIB societe $fact->socid $soc->nom");
                                $facture_errors[$fac[0]]="Erreur de RIB societe $fact->socid $soc->nom";
                            }
                        }
                        else
                        {
                            dolibarr_syslog("Impossible de lire la société");
                        }
                    }
                    else
                    {
                        dolibarr_syslog("Impossible de lire la facture");
                    }
                }
            }
            else
            {
                dolibarr_syslog("Aucune factures a traiter");
            }
        }


        /*
         *
         *
         */

        dolibarr_syslog(sizeof($factures_prev)." factures seront prélevées");

        if (sizeof($factures_prev) > 0)
        {
            /*
             * Ouverture de la transaction
             *
             */
            $result=$this->db->begin();
            if ($result <= 0)
            {
                $error++;
            }

            /*
             * Traitements
             *
             */
            if (!$error)
            {
                $ref = "T".substr($year,-2).$month;

                $sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."prelevement_bons";
                $sql .= " WHERE ref LIKE '$ref%'";

                $resql = $this->db->query($sql);

                if ($resql)
                {
                    $row = $this->db->fetch_row($resql);
                }
                else
                {
                    $error++;
                    dolibarr_syslog("Erreur recherche reference");
                }

                $ref = $ref . substr("00".($row[0]+1), -2);

                $filebonprev = $ref;

                /*
                 * Creation du bon de prelevement
                 *
                 */

                $sql = "INSERT INTO ".MAIN_DB_PREFIX."prelevement_bons (ref,datec)";
                $sql .= " VALUES ('".$ref."',now())";

                $resql = $this->db->query($sql);

                if ($resql)
                {
                    $prev_id = $this->db->last_insert_id(MAIN_DB_PREFIX."prelevement_bons");

                    $dir=$conf->prelevement->dir_output.'/bon';
                    $file=$filebonprev;
                    if (! is_dir($dir)) create_exdir($dir);
                    
                    $bonprev = new BonPrelevement($this->db, $dir."/".$file);
                    $bonprev->id = $prev_id;
                }
                else
                {
                    $error++;
                    dolibarr_syslog("Erreur création du bon de prelevement");
                }
            }

            /*
             *
             *
             */
            if (!$error)
            {
                dolibarr_syslog("Début génération des paiements");
                dolibarr_syslog("Nombre de factures ".sizeof($factures_prev));

                if (sizeof($factures_prev) > 0)
                {
                    foreach ($factures_prev as $fac)
                    {
                        $fact = new Facture($this->db);
                        $fact->fetch($fac[0]);

                        $pai = new Paiement($this->db);

                        $pai->amounts = array();
                        $pai->amounts[$fac[0]] = $fact->total_ttc;
                        $pai->datepaye = $this->db->idate($datetimeprev);
                        $pai->paiementid = 3; // prélèvement
                        $pai->num_paiement = $ref;

                        if ($pai->create($user, 1) == -1)  // on appelle en no_commit
                        {
                            $error++;
                            dolibarr_syslog("Erreur creation paiement facture ".$fac[0]);
                        }
                        else
                        {
                            /*
                            * Validation du paiement
                            */
                            $pai->valide();

                            /*
                            * Ajout d'une ligne de prélèvement
                            *
                            *
                            * $fac[3] : banque
                            * $fac[4] : guichet
                            * $fac[5] : number
                            * $fac[6] : cle rib
                            * $fac[7] : amount
                            * $fac[8] : client nom
                            * $fac[2] : client id
                            */

                            $ri = $bonprev->AddFacture($fac[0], $fac[2], $fac[8], $fac[7],
                            $fac[3], $fac[4], $fac[5], $fac[6]);
                            if ($ri <> 0)
                            {
                                $error++;
                            }

                            /*
                             * Mise à jour des demandes
                             *
                             */
                            $sql = "UPDATE ".MAIN_DB_PREFIX."prelevement_facture_demande";
                            $sql .= " SET traite = 1";
                            $sql .= ", date_traite=now()";
                            $sql .= ", fk_prelevement_bons = ".$prev_id;
                            $sql .= " WHERE rowid=".$fac[1];

                            if ($this->db->query($sql))
                            {

                            }
                            else
                            {
                                $error++;
                                dolibarr_syslog("Erreur mise a jour des demandes");
                                dolibarr_syslog($this->db->error());
                            }

                        }
                    }
                }

                dolibarr_syslog("Fin des paiements");
            }

            if (!$error)
            {
                /*
                 * Bon de Prelevement
                 *
                 *
                 */

                dolibarr_syslog("Debut prelevement");
                dolibarr_syslog("Nombre de factures ".sizeof($factures_prev));

                if (sizeof($factures_prev) > 0)
                {
                    $bonprev->date_echeance = $datetimeprev;
                    $bonprev->reference_remise = $ref;

                    $bonprev->numero_national_emetteur = PRELEVEMENT_NUMERO_NATIONAL_EMETTEUR;
                    $bonprev->raison_sociale = PRELEVEMENT_RAISON_SOCIALE;

                    $bonprev->emetteur_code_etablissement = PRELEVEMENT_CODE_BANQUE;
                    $bonprev->emetteur_code_guichet       = PRELEVEMENT_CODE_GUICHET;
                    $bonprev->emetteur_numero_compte      = PRELEVEMENT_NUMERO_COMPTE;


                    $bonprev->factures = $factures_prev_id;

                    $bonprev->generate();
                }
                dolibarr_syslog( $filebonprev ) ;
                dolibarr_syslog("Fin prelevement");
            }

            /*
             * Mise à jour du total
             *
             */

            $sql = "UPDATE ".MAIN_DB_PREFIX."prelevement_bons";
            $sql .= " SET amount = ".price2num($bonprev->total);
            $sql .= " WHERE rowid = ".$prev_id;

            $resql=$this->db->query($sql);
            if (! $resql)
            {
                $error++;
                dolibarr_syslog("Erreur mise à jour du total - $sql");
            }

            /*
             * Rollback ou Commit
             *
             */
            if (!$error)
            {
                $this->db->commit();
                dolibarr_syslog("COMMIT");
            }
            else
            {
                $this->db->rollback();
                dolibarr_syslog("ROLLBACK");
            }

            return sizeof($factures_prev);
        }
        else
        {
            return 0;
        }
    }

    /**
     *
     *
     */
    function DeleteNotificationById($rowid)
    {
        $result = 0;

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."prelevement_notifications ";
        $sql .= " WHERE rowid = '".$rowid."';";

        if ($this->db->query($sql))
        {
            return 0;
        }
        else
        {
            return -1;
        }
    }

    /**
     *
     *
     */
    function DeleteNotification($user, $action)
    {
        $result = 0;

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."prelevement_notifications ";
        $sql .= " WHERE fk_user = '".$user."' AND action = '".$action."';";

        if ($this->db->query($sql))
        {
            return 0;
        }
        else
        {
            return -1;
        }
    }

    /*
     *
     *
     */
    function AddNotification($user, $action)
    {
        $result = 0;

        if ($this->DeleteNotification($user, $action) == 0)
        {

            $sql = "INSERT INTO ".MAIN_DB_PREFIX."prelevement_notifications ";
            $sql .= " (fk_user, action )";
            $sql .= " VALUES ('".$user."','".$action."');";

            if ($this->db->query($sql))
            {
                $result = 0;
            }
            else
            {
                $result = -1;
                dolibarr_syslog("BonPrelevement::AddNotification Erreur $result");
            }
        }

        return $result;
    }

    /**
     * Génération d'un bon de prélèvement
     *
     */
    function Generate()
    {
        $result = -1;

        $this->file = fopen ($this->filename,"w");


        /*
         * En-tete Emetteur
         */
        $this->EnregEmetteur();

        /*
         * Lignes
         */
        $this->total = 0;

        $sql = "SELECT rowid, client_nom, code_banque, code_guichet, number, amount";
        $sql .= " FROM ".MAIN_DB_PREFIX."prelevement_lignes";
        $sql .= " WHERE fk_prelevement_bons = ".$this->id;

        $i = 0;

        if ($this->db->query($sql))
        {
            $num = $this->db->num_rows();

            while ($i < $num)
            {
                $row = $this->db->fetch_row();

                $this->EnregDestinataire($row[0],
                $row[1],
                $row[2],
                $row[3],
                $row[4],
                $row[5]);

                $this->total = $this->total + $row[5];

                $i++;
            }
        }
        else
        {
            $result = -2;
        }
        /*
        $nbfactures = sizeof($this->factures);
        for ($i = 0 ; $i < $nbfactures ; $i++)
        {
        $fac = new Facture($this->db);
        $fac->fetch($this->factures[$i]);
        $fac->fetch_client();
        $fac->client->rib();
        if ($fac->client->bank_account->verif()) {
        $this->total = $this->total + $fac->total_ttc;
        $this->EnregDestinataire($fac);
        }else{
        print $fac->client->bank_account->error_message;
        print $fac->client->nom; }
        }
        */

        /*
        * Pied de page total
        */

        $this->EnregTotal($this->total);

        fclose($this->file);

        return $result;
    }


    /**
     * Enregistrements destinataires
     *
     *
     */
    function EnregDestinataire($rowid, $client_nom, $rib_banque, $rib_guichet, $rib_number, $amount)
    {
        fputs ($this->file, "06");
        fputs ($this->file, "08"); // Prélèvement ordinaire

        fputs ($this->file, "        "); // Zone Réservée B2

        fputs ($this->file, $this->numero_national_emetteur); // Numéro National d'emmetteur B3

        // Date d'échéance C1

        fputs ($this->file, "       ");
        fputs ($this->file, strftime("%d%m", $this->date_echeance));
        fputs ($this->file, substr(strftime("%y", $this->date_echeance),1));

        // Raison Sociale Destinataire C2

        fputs ($this->file, substr($client->nom. "                           ",0,24));

        // Domiciliation facultative D1

        fputs ($this->file, substr("                                    ",0,24));

        // Zone Réservée D2

        fputs ($this->file, substr("                             ",0,8));

        // Code Guichet  D3

        fputs ($this->file, $rib_guichet);

        // Numero de compte D4

        fputs ($this->file, substr("000000000000000".$rib_number, -11));

        // Zone E Montant

        $montant = (round($amount,2) * 100);

        fputs ($this->file, substr("000000000000000".$montant, -16));

        // Libellé F

        fputs ($this->file, substr("*".$this->ref.$rowid."                                   ",0,13));
        fputs ($this->file, substr("                                        ",0,18));

        // Code établissement G1

        fputs ($this->file, $rib_banque);

        // Zone Réservée G2

        fputs ($this->file, substr("                                        ",0,5));

        fputs ($this->file, "\n");
    }


    /**
     * Enregistrements destinataires
     *
     *
     */
    function EnregDestinataireVersion1($fac)
    {
        fputs ($this->file, "06");
        fputs ($this->file, "08"); // Prélèvement ordinaire

        fputs ($this->file, "        "); // Zone Réservée B2

        fputs ($this->file, $this->numero_national_emetteur); // Numéro National d'emmetteur B3

        // Date d'échéance C1

        fputs ($this->file, "       ");
        fputs ($this->file, strftime("%d%m", $this->date_echeance));
        fputs ($this->file, substr(strftime("%y", $this->date_echeance),1));

        // Raison Sociale Destinataire C2

        fputs ($this->file, substr($fac->client->nom. "                           ",0,24));

        // Reference de la remise créancier D1

        fputs ($this->file, substr("                                    ",0,24));

        // Zone Réservée D2

        fputs ($this->file, substr("                             ",0,8));

        // Code Guichet  D3

        fputs ($this->file, $fac->client->bank_account->code_guichet);

        // Numero de compte D4

        fputs ($this->file, substr("000000000000000".$fac->client->bank_account->number, -11));

        // Zone E Montant

        $montant = (round($fac->total_ttc,2) * 100);

        fputs ($this->file, substr("000000000000000".$montant, -16));

        // Libellé F

        fputs ($this->file, substr("*".$fac->ref."                                   ",0,13));
        fputs ($this->file, substr("                                        ",0,18));

        // Code établissement G1

        fputs ($this->file, $fac->client->bank_account->code_banque);

        // Zone Réservée G2

        fputs ($this->file, substr("                                        ",0,5));

        fputs ($this->file, "\n");
    }

    /**
     *
     *
     */
    function EnregEmetteur()
    {
        fputs ($this->file, "03");
        fputs ($this->file, "08"); // Prélèvement ordinaire

        fputs ($this->file, "        "); // Zone Réservée B2

        fputs ($this->file, $this->numero_national_emetteur); // Numéro National d'emmetteur B3

        // Date d'échéance C1

        fputs ($this->file, "       ");
        fputs ($this->file, strftime("%d%m", $this->date_echeance));
        fputs ($this->file, substr(strftime("%y", $this->date_echeance),1));

        // Raison Sociale C2

        fputs ($this->file, substr($this->raison_sociale. "                           ",0,24));

        // Reference de la remise créancier D1 sur 7 caractéres

        fputs ($this->file, substr($this->reference_remise. "                           ",0,7));

        // Zone Réservée D1-2

        fputs ($this->file, substr("                                    ",0,17));

        // Zone Réservée D2

        fputs ($this->file, substr("                             ",0,2));
        fputs ($this->file, "E");
        fputs ($this->file, substr("                             ",0,5));

        // Code Guichet  D3

        fputs ($this->file, $this->emetteur_code_guichet);

        // Numero de compte D4

        fputs ($this->file, substr("000000000000000".$this->emetteur_numero_compte, -11));

        // Zone Réservée E

        fputs ($this->file, substr("                                        ",0,16));

        // Zone Réservée F

        fputs ($this->file, substr("                                        ",0,31));

        // Code établissement

        fputs ($this->file, $this->emetteur_code_etablissement);

        // Zone Réservée G

        fputs ($this->file, substr("                                        ",0,5));

        fputs ($this->file, "\n");

    }

    /**
     * Pied de page
     *
     */
    function EnregTotal($total)
    {
        fputs ($this->file, "08");
        fputs ($this->file, "08"); // Prélèvement ordinaire

        fputs ($this->file, "        "); // Zone Réservée B2

        fputs ($this->file, $this->numero_national_emetteur); // Numéro National d'emmetteur B3

        // Réservé C1

        fputs ($this->file, substr("                           ",0,12));


        // Raison Sociale C2

        fputs ($this->file, substr("                           ",0,24));

        // D1

        fputs ($this->file, substr("                                    ",0,24));

        // Zone Réservée D2

        fputs ($this->file, substr("                             ",0,8));

        // Code Guichet  D3

        fputs ($this->file, substr("                             ",0,5));

        // Numero de compte D4

        fputs ($this->file, substr("                             ",0,11));

        // Zone E Montant

        $montant = ($total * 100);

        fputs ($this->file, substr("000000000000000".$montant, -16));

        // Zone Réservée F

        fputs ($this->file, substr("                                        ",0,31));

        // Code établissement

        fputs ($this->file, substr("                                        ",0,5));

        // Zone Réservée F

        fputs ($this->file, substr("                                        ",0,5));

        fputs ($this->file, "\n");
    }
}

?>
