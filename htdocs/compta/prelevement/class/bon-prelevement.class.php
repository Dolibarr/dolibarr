<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2010	   Juanjo Menent        <jmenent@2byte.es>
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
 *      \file       htdocs/compta/prelevement/class/bon-prelevement.class.php
 *      \ingroup    prelevement
 *      \brief      Fichier de la classe des bons de prelevements
 *      \version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");


/**
 *	\class      BonPrelevement
 *	\brief      Classe permettant la gestion des bons de prelevements
 */
class BonPrelevement extends CommonObject
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

    var $statut;    // 0-Wait, 1-Trans, 2-Done

  /**
   *	Class constructor
   *    @param  DB          data base handler access
   *    @param  filename    filename
   */
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
   *	Add facture to withdrawal
   *    @param	facture_id id invoice to add
   *    @param	client_id  id invoice customer
   *    @param	client_nom name of cliente
   *    @param	amount amount of invoice
   *    @param	code_banque code of bank withdrawal
   *    @param	code_guichet code of bank's office
   *    @param	number bank account number 
   *	@return	int	>0 if OK, <0 if KO
   */
    function AddFacture($facture_id, $client_id, $client_nom, $amount, $code_banque, $code_guichet, $number)
    {
        $result = 0;
        $line_id = 0;

        $result = $this->AddLigne($line_id, $client_id, $client_nom, $amount, $code_banque, $code_guichet, $number);

        if ($result == 0)
        {
            if ($line_id > 0)
            {
                $sql = "INSERT INTO ".MAIN_DB_PREFIX."prelevement_facture (";
                $sql.= "fk_facture";
                $sql.= ",fk_prelevement_lignes";
                $sql.= ") VALUES (";
                $sql.= $facture_id;
                $sql.= ", ".$line_id;
                $sql.= ")";

                if ($this->db->query($sql))
                {
                    $result = 0;
                }
                else
                {
                    $result = -1;
                    dol_syslog("BonPrelevement::AddFacture Erreur $result");
                }
            }
            else
            {
                $result = -2;
                dol_syslog("BonPrelevement::AddFacture Erreur $result");
            }
        }
        else
        {
            $result = -3;
            dol_syslog("BonPrelevement::AddFacture Erreur $result");
        }

        return $result;

    }

    /**
   	*	Add line to withdrawal
   	*	@param	ligne_id id line to add
   	*	@param	client_id  id invoice customer
   	*	@param	client_nom name of cliente
   	*	@param	amount amount of invoice
   	*	@param	code_banque code of bank withdrawal
   	*	@param	code_guichet code of bank's office
   	*	@param	number bank account number 
   	*	@return	int	>0 if OK, <0 if KO
   	*/
    function AddLigne(&$line_id, $client_id, $client_nom, $amount, $code_banque, $code_guichet, $number)
    {
        $result = -1;
        $concat = 0;

        if ($concat == 1)
        {
            /*
             * On aggrege les lignes
             */
            $sql = "SELECT rowid";
            $sql.= " FROM  ".MAIN_DB_PREFIX."prelevement_lignes";
            $sql.= " WHERE fk_prelevement_bons = ".$this->id;
            $sql.= " AND fk_soc =".$client_id;
            $sql.= " AND code_banque ='".$code_banque."'";
            $sql.= " AND code_guichet ='".$code_guichet."'";
            $sql.= " AND number ='".$number."'";

            $resql=$this->db->query($sql);
            if ($resql)
            {
                $num = $this->db->num_rows($resql);
            }
            else
            {
                $result = -1;
            }
        }
        else
        {
            /*
             * Pas de d'agregation
             */
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."prelevement_lignes (";
            $sql.= "fk_prelevement_bons";
            $sql.= ", fk_soc";
            $sql.= ", client_nom";
            $sql.= ", amount";
            $sql.= ", code_banque";
            $sql.= ", code_guichet";
            $sql.= ", number";
            $sql.= ") VALUES (";
            $sql.= $this->id;
            $sql.= ", ".$client_id;
            $sql.= ", '".addslashes($client_nom)."'";
            $sql.= ", '".price2num($amount)."'";
            $sql.= ", '".$code_banque."'";
            $sql.= ", '".$code_guichet."'";
            $sql.= ", '".$number."'";
            $sql.= ")";

            if ($this->db->query($sql))
            {
                $line_id = $this->db->last_insert_id(MAIN_DB_PREFIX."prelevement_lignes");
                $result = 0;
            }
            else
            {
                dol_syslog("BonPrelevement::AddLigne Error -2");
                $result = -2;
            }

        }

        return $result;
    }

    /**
   	*	Read errors
   	*   @param	error id of error
   	*	@return	array of errors 
   	*/
    function ReadError($error)
    {
        $errors = array();

        $errors[1027] = "Date invalide";

        return $errors[abs($error)];
    }

 	/**
	*	Get object and lines from database
	*	@param	rowid	id of object to load
	*	@return	int		>0 if OK, <0 if KO
	*/
    function fetch($rowid)
    {
    	global $conf;

        $sql = "SELECT p.rowid, p.ref, p.amount, p.note";
        $sql.= ", p.datec as dc";
        $sql.= ", p.date_trans as date_trans";
        $sql.= ", p.method_trans, p.fk_user_trans";
        $sql.= ", p.date_credit as date_credit";
        $sql.= ", p.fk_user_credit";
        $sql.= ", p.statut";
        $sql.= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
        $sql.= " WHERE p.rowid = ".$rowid;
        $sql.= " AND p.entity = ".$conf->entity;

        dol_syslog("Bon-prelevement::fetch sql=".$sql, LOG_DEBUG);
        $result=$this->db->query($sql);
        if ($result)
        {
            if ($this->db->num_rows($result))
            {
                $obj = $this->db->fetch_object($result);

                $this->id                 = $obj->rowid;
                $this->ref                = $obj->ref;
                $this->amount             = $obj->amount;
                $this->note               = $obj->note;
                $this->datec              = $this->db->jdate($obj->dc);

                $this->date_trans         = $this->db->jdate($obj->date_trans);
                $this->method_trans       = $obj->method_trans;
                $this->user_trans         = $obj->fk_user_trans;

                $this->date_credit        = $this->db->jdate($obj->date_credit);
                $this->user_credit        = $obj->fk_user_credit;

                $this->statut             = $obj->statut;

                $this->_fetched = 1;

                return 0;
            }
            else
            {
                dol_syslog("BonPrelevement::Fetch Erreur aucune ligne retournee");
                return -1;
            }
        }
        else
        {
            dol_syslog("BonPrelevement::Fetch Erreur ");
            dol_syslog($sql);
            return -2;
        }
    }

    /**
     *     @deprecated
     */
    function set_credite()
    {
        global $user,$conf;

        $error == 0;

        if ($this->db->begin())
        {
            $sql = " UPDATE ".MAIN_DB_PREFIX."prelevement_bons";
            $sql.= " SET statut = 1";
            $sql.= " WHERE rowid = ".$this->id;
            $sql.= " AND entity = ".$conf->entity;

            $result=$this->db->query($sql);
            if (! $result)
            {
                dol_syslog("bon-prelevement::set_credite Erreur 1");
                $error++;
            }

            if ($error == 0)
            {
                $facs = array();
                $facs = $this->_get_list_factures();

                for ($i = 0 ; $i < sizeof($facs) ; $i++)
                {
                    /* Tag invoice as payed */
                    dol_syslog("BonPrelevement::set_credite set_paid fac ".$facs[$i]);
                    $fac = new Facture($this->db);
                    $fac->fetch($facs[$i]);
                    $result = $fac->set_paid($user);
                }
            }

            if ($error == 0)
            {

                $sql = " UPDATE ".MAIN_DB_PREFIX."prelevement_lignes";
                $sql.= " SET statut = 2";
                $sql.= " WHERE fk_prelevement_bons = ".$this->id;

                if (! $this->db->query($sql))
                {
                    dol_syslog("BonPrelevement::set_credite Erreur 1");
                    $error++;
                }
            }

            /*
             * Fin de la procedure
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
                dol_syslog("BonPrelevement::set_credite ROLLBACK ");

                return -1;
            }
        }
        else
        {
            dol_syslog("BonPrelevement::set_credite Ouverture transaction SQL impossible ");
            return -2;
        }
    }

	/**
	*	Set withdrawal to creditet status
	*	@param	user	id of user
	*	@param 	date	date of action 
	*	@return	int		>0 if OK, <0 if KO
	*/
    function set_infocredit($user, $date)
    {
    	global $conf;

        $error == 0;

        if ($this->_fetched == 1)
        {
            if ($date >= $this->date_trans)
            {
                if ($this->db->begin())
                {
                    $sql = " UPDATE ".MAIN_DB_PREFIX."prelevement_bons ";
                    $sql.= " SET fk_user_credit = ".$user->id;
                    $sql.= ", statut = 2";
                    $sql.= ", date_credit = '".$this->db->idate($date)."'";
                    $sql.= " WHERE rowid=".$this->id;
                    $sql.= " AND entity = ".$conf->entity;
                    $sql.= " AND statut = 1";

                    if ($this->db->query($sql))
                    {
                        $subject = "Credit prelevement ".$this->ref." a la banque";
                        $message = "Le bon de prelevement ".$this->ref;
                        $message.= " a ete credite par la banque.\n";
                        $message.= "Date credit : ".dol_print_date($date,'dayhour');

                        $this->Notify($user, "cr", $subject, $message);
                   
                        // Update prelevement line 
                        // TODO: Translate to ligne-prelevement.class.php
                		$sql = " UPDATE ".MAIN_DB_PREFIX."prelevement_lignes";
                		$sql.= " SET statut = 2";
                		$sql.= " WHERE fk_prelevement_bons = ".$this->id;

                		if (! $this->db->query($sql))
                		{
                    		dol_syslog("BonPrelevement::set_credite Erreur 1");
                    		$error++;
                		}
            
                    }
                    else
                    {
                        dol_syslog("BonPrelevement::set_infocredit Erreur 1");
                        $error++;
                    }

                    /*
                     * Fin de la procedure
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
                        dol_syslog("bon-prelevment::set_infocredit ROLLBACK ");
                        return -1;
                    }
                }
                else
                {
                    dol_syslog("bon-prelevement::set_infocredit Ouverture transaction SQL impossible ");
                    return -1025;
                }
            }
            else
            {
                dol_syslog("bon-prelevment::set_infocredit 1027 Date de credit < Date de trans ");
                return -1027;
            }
        }
        else
        {
            return -1026;
        }
    }

    /**
	*	Set withdrawal to transmited status
	*	@param	user	id of user
	*	@param 	date	date of action 
	*	@param	method	method of transmision to bank
	*	@return	int		>0 if OK, <0 if KO
	*/
    function set_infotrans($user, $date, $method)
    {
    	global $conf;

        $error == 0;
        dol_syslog("bon-prelevement::set_infotrans Start",LOG_INFO);
        if ($this->db->begin())
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX."prelevement_bons ";
            $sql.= " SET fk_user_trans = ".$user->id;
            $sql.= " , date_trans = '".$this->db->idate($date)."'";
            $sql.= " , method_trans = ".$method;
            $sql.= " , statut = 1";
            $sql.= " WHERE rowid = ".$this->id;
            $sql.= " AND entity = ".$conf->entity;
            $sql.= " AND statut = 0";

            if ($this->db->query($sql))
            {
                $this->method_trans = $method;

                $subject = "Transmission du prelevement ".$this->ref." a la banque";
                $message = "Le bon de prelevement ".$this->ref;
                $message .= " a ete transmis a la banque par ".$user->prenom. " ".$user->nom;
                $message .= "\n\n";
                $message .= "\nMontant : ".price($this->amount);
                $message .= "\nMethode : ".$this->methodes_trans[$this->method_trans];
                $message .= "\nDate  : ".dol_print_date($date,'day');

                $this->Notify($user,"tr", $subject, $message, 1);
            }
            else
            {
                dol_syslog("bon-prelevement::set_infotrans Erreur 1", LOG_ERR);
                dol_syslog($this->db->error());
                $error++;
            }

            /*
             * Fin de la procedure
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
                dol_syslog("BonPrelevement::set_infotrans ROLLBACK", LOG_ERR);

                return -1;
            }
        }
        else
        {

            dol_syslog("BonPrelevement::set_infotrans Ouverture transaction SQL impossible", LOG_CRIT);
            return -2;
        }
    }

    /**
	*	Notify withdrawal actions
	*	@param	user		id of user
	*	@param	action		notify action
	*	@param	subject		message subject
	*	@param	message		message
	*	@param	joinfile	files joineds 
	*	@return	int			>0 if OK, <0 if KO
	*/
    function Notify($user, $action, $subject, $message, $joinfile=0)
    {
    	global $conf;

        $message .= "\n\n--\n";
        $message .= "Ceci est un message automatique envoye par Dolibarr";

        $sql = "SELECT u.name, u.firstname, u.email";
        $sql.= " FROM ".MAIN_DB_PREFIX."user as u";
        $sql.= " , ".MAIN_DB_PREFIX."prelevement_notifications as pn";
        $sql.= " WHERE pn.action = '".$action."'";
        $sql.= " AND u.rowid = pn.fk_user";
        $sql.= " AND u.entity IN (0,".$conf->entity.")";
        dol_syslog("BonPrelevement::Notify: ".$sql, LOG_CRIT);
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
                    $arr_file = array($conf->prelevement->dir_output.'/receipts/'.$this->ref.'.ps');
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
	*	Get invoice list 
	*	@return	array id of invoices
	*/
    function _get_list_factures()
    {
    	global $conf;

        $arr = array();

        /*
         * Renvoie toutes les factures presente
         * dans un bon de prelevement
         */
        $sql = "SELECT fk_facture";
        $sql.= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
        $sql.= " , ".MAIN_DB_PREFIX."prelevement_lignes as pl";
        $sql.= " , ".MAIN_DB_PREFIX."prelevement_facture as pf";
        $sql.= " WHERE pf.fk_prelevement_lignes = pl.rowid";
        $sql.= " AND pl.fk_prelevement_bons = p.rowid";
        $sql.= " AND p.rowid = ".$this->id;
        $sql.= " AND p.entity = ".$conf->entity;

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
            dol_syslog("Bon-Prelevement::_get_list_factures Erreur");
        }

        return $arr;
    }

    /**
     *
     *
     */
    function SommeAPrelever()
    {
    	global $conf;

        $sql = "SELECT sum(f.total_ttc)";
        $sql.= " FROM ".MAIN_DB_PREFIX."facture as f,";
        $sql.= " ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
        //$sql.= " ,".MAIN_DB_PREFIX."c_paiement as cp";
        $sql.= " WHERE f.fk_statut = 1";
        $sql.= " AND f.entity = ".$conf->entity;
        $sql.= " AND f.rowid = pfd.fk_facture";
        $sql.= " AND f.paye = 0";
        $sql.= " AND pfd.traite = 0";
        $sql.= " AND f.total_ttc > 0";

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
            dol_syslog("BonPrelevement::SommeAPrelever Erreur -1");
            dol_syslog($this->db->error());
        }
    }

    /**
     *	Get number of invoices to withdrawal
     *	@param	banque	bank
     *	@param	agence	agence
     *	@return	int		<O if KO, number of invoices if OK
     */
    function NbFactureAPrelever($banque=0,$agence=0)
    {
    	global $conf;

        $sql = "SELECT count(f.rowid)";
        $sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
        $sql.= ", ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
        if ($banque == 1 || $agence == 1) $sql.=", ".MAIN_DB_PREFIX."societe_rib as sr";
        $sql.= " WHERE f.fk_statut = 1";
        $sql.= " AND f.entity = ".$conf->entity;
        $sql.= " AND f.rowid = pfd.fk_facture";
        $sql.= " AND f.paye = 0";
        $sql.= " AND pfd.traite = 0";
        $sql.= " AND f.total_ttc > 0";
        if ($banque == 1 || $agence == 1) $sql.= " AND f.fk_soc = sr.rowid";
        if ($banque == 1) $sql.= " AND sr.code_banque = '".$conf->global->PRELEVEMENT_CODE_BANQUE."'";
        if ($agence == 1) $sql.= " AND sr.code_guichet = '".$conf->global->PRELEVEMENT_CODE_GUICHET."'";

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
            dol_syslog($this->error, LOG_ERR);
            return -1;
        }
    }


    /**
     *	Create a withdraw
     *	@param 	banque	code of bank
     *	@param	guichet	code of banck office
     *	@param	mode	real=do action, simu=test only
     *	@return	int		<0 if KO, nbre of invoice withdrawed if OK
     */
    function Create($banque=0, $guichet=0, $mode='real')
    {
        global $conf,$langs;

        dol_syslog("BonPrelevement::Create banque=$banque guichet=$guichet");

        //require_once (DOL_DOCUMENT_ROOT."/compta/prelevement/bon-prelevement.class.php");
        require_once (DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
        require_once (DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
        require_once (DOL_DOCUMENT_ROOT."/compta/paiement/class/paiement.class.php");

        $error = 0;

        $datetimeprev = time();

        $month = strftime("%m", $datetimeprev);
        $year = strftime("%Y", $datetimeprev);

        $puser = new User($this->db, $conf->global->PRELEVEMENT_USER);

        /**
         * Lectures des factures
         */
        $factures = array();
        $factures_prev = array();
        $factures_result = array();

        if (! $error)
        {
            $sql = "SELECT f.rowid, pfd.rowid as pfdrowid, f.fk_soc";
            $sql.= ", pfd.code_banque, pfd.code_guichet, pfd.number, pfd.cle_rib";
            $sql.= ", pfd.amount";
            $sql.= ", s.nom";
            $sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
            $sql.= ", ".MAIN_DB_PREFIX."societe as s";
            $sql.= ", ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
            if ($banque == 1 || $agence ==1) $sql.= ", ".MAIN_DB_PREFIX."societe_rib as sr";
            $sql.= " WHERE f.rowid = pfd.fk_facture";
            $sql.= " AND f.entity = ".$conf->entity;
            $sql.= " AND s.rowid = f.fk_soc";
            if ($banque == 1 || $agence ==1) $sql.= " AND s.rowid = sr.fk_soc";
            $sql.= " AND f.fk_statut = 1";
            $sql.= " AND f.paye = 0";
            $sql.= " AND pfd.traite = 0";
            $sql.= " AND f.total_ttc > 0";
            if ($banque == 1) $sql.= " AND sr.code_banque = '".$conf->global->PRELEVEMENT_CODE_BANQUE."'";
            if ($agence == 1) $sql.= " AND sr.code_guichet = '".$conf->global->PRELEVEMENT_CODE_GUICHET."'";

            dol_syslog("Bon-Prelevement::Create sql=".$sql, LOG_DEBUG);
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
                dol_syslog($i." invoices to withdraw");
            }
            else
            {
                $error = 1;
                dol_syslog("Erreur -1");
                dol_syslog($this->db->error());
            }
        }

        if (! $error)
        {
            // Check RIB
        	$i = 0;
            dol_syslog("Start RIB check");

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
                                dol_syslog("Error on third party bank number RIB/IBAN $fact->socid $soc->nom", LOG_ERR);
                                $facture_errors[$fac[0]]="Error on third party bank number RIB/IBAN $fact->socid $soc->nom";
                            }
                        }
                        else
                        {
                            dol_syslog("Failed to read company", LOG_ERR);
                        }
                    }
                    else
                    {
                        dol_syslog("Failed to read invoice", LOG_ERR);
                    }
                }
            }
            else
            {
                dol_syslog("No invoice to process");
            }
        }

		$ok=0;

        // Withdraw invoices in factures_prev array
        $out=sizeof($factures_prev)." invoices will be withdrawn.";
		//print $out."\n";
		dol_syslog($out);


		if (sizeof($factures_prev) > 0)
		{
			if ($mode=='real')
			{
				$ok=1;
			}
			else
			{
				print "Option for real mode was not set, we stop after this simulation\n";
			}
		}


        if ($ok)
        {
			/*
			 * We are in real mode.
			 * We create withdraw receipt, payments and build withdraw into disk
			 */
            $this->db->begin();

            /*
             * Traitements
             *
             */
            if (!$error)
            {
                $ref = "T".substr($year,-2).$month;

                $sql = "SELECT count(*)";
                $sql.= " FROM ".MAIN_DB_PREFIX."prelevement_bons";
                $sql.= " WHERE ref LIKE '".$ref."%'";
                $sql.= " AND entity = ".$conf->entity;

            	dol_syslog("Bon-Prelevement::Create sql=".$sql, LOG_DEBUG);
                $resql = $this->db->query($sql);

                if ($resql)
                {
                    $row = $this->db->fetch_row($resql);
                }
                else
                {
                    $error++;
                    dol_syslog("Erreur recherche reference");
                }

                $ref = $ref . substr("00".($row[0]+1), -2);

                $filebonprev = $ref;

                // Create withdraw receipt in database
                $sql = "INSERT INTO ".MAIN_DB_PREFIX."prelevement_bons (";
                $sql.= " ref, entity, datec";
                $sql.= ") VALUES (";
                $sql.= "'".$ref."'";
                $sql.= ", ".$conf->entity;
                $sql.= ", '".$this->db->idate(mktime())."'";
                $sql.= ")";

            	dol_syslog("Bon-Prelevement::Create sql=".$sql, LOG_DEBUG);
                $resql = $this->db->query($sql);

                if ($resql)
                {
                    $prev_id = $this->db->last_insert_id(MAIN_DB_PREFIX."prelevement_bons");

                    $dir=$conf->prelevement->dir_output.'/receipts';
                    $file=$filebonprev;
                    if (! is_dir($dir)) create_exdir($dir);

                    $bonprev = new BonPrelevement($this->db, $dir."/".$file);
                    $bonprev->id = $prev_id;
                }
                else
                {
                    $error++;
                    dol_syslog("Erreur creation du bon de prelevement");
                }
            }

            /*
             *
             *
             */
            if (!$error)
            {
                dol_syslog("Start generation payments for the ".sizeof($factures_prev)." invoices");

                if (sizeof($factures_prev) > 0)
                {
                    foreach ($factures_prev as $fac)
                    {
                    	// Fetch invoice
                        $fact = new Facture($this->db);
                        $fact->fetch($fac[0]);

                        // Create payment
                        $pai = new Paiement($this->db);

                        $pai->amounts = array();
                        $pai->amounts[$fac[0]] = $fact->total_ttc;
                        $pai->datepaye = $datetimeprev;
                        $pai->paiementid = 3; // prelevement
                        $pai->num_paiement = $ref;

                        if ($pai->create($puser, 1) < 0)  // on appelle en no_commit
                        {
                            $error++;
                            dol_syslog("Erreur creation paiement facture ".$fac[0]);
                        }
                        else
                        {
                            /*
                            * Validation du paiement
                            */
                            $pai->valide();

                            /*
                            * Ajout d'une ligne de prelevement
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
                             * Mise a jour des demandes
                             *
                             */
                            $sql = "UPDATE ".MAIN_DB_PREFIX."prelevement_facture_demande";
                            $sql.= " SET traite = 1";
                            $sql.= ", date_traite = ".$this->db->idate(mktime());
                            $sql.= ", fk_prelevement_bons = ".$prev_id;
                            $sql.= " WHERE rowid = ".$fac[1];

            				dol_syslog("Bon-Prelevement::Create sql=".$sql, LOG_DEBUG);
                            if ($this->db->query($sql))
                            {

                            }
                            else
                            {
                                $error++;
                                dol_syslog("Erreur mise a jour des demandes");
                                dol_syslog($this->db->error());
                            }

                        }
                    }
                }

                dol_syslog("Fin des paiements");
            }

            if (!$error)
            {
                /*
                 * Withdraw receipt
                 */

                dol_syslog("Debut prelevement - Nombre de factures ".sizeof($factures_prev));

                if (sizeof($factures_prev) > 0)
                {
                    $bonprev->date_echeance = $datetimeprev;
                    $bonprev->reference_remise = $ref;

                    $bonprev->numero_national_emetteur    = $conf->global->PRELEVEMENT_NUMERO_NATIONAL_EMETTEUR;
                    $bonprev->raison_sociale              = $conf->global->PRELEVEMENT_RAISON_SOCIALE;

                    $bonprev->emetteur_code_etablissement = $conf->global->PRELEVEMENT_CODE_BANQUE;
                    $bonprev->emetteur_code_guichet       = $conf->global->PRELEVEMENT_CODE_GUICHET;
                    $bonprev->emetteur_numero_compte      = $conf->global->PRELEVEMENT_NUMERO_COMPTE;


                    $bonprev->factures = $factures_prev_id;

                    // Build file
                    $bonprev->generate();
                }
                dol_syslog( $filebonprev ) ;
                dol_syslog("Fin prelevement");
            }

            /*
             * Mise a jour du total
             *
             */

            $sql = "UPDATE ".MAIN_DB_PREFIX."prelevement_bons";
            $sql.= " SET amount = ".price2num($bonprev->total);
            $sql.= " WHERE rowid = ".$prev_id;
            $sql.= " AND entity = ".$conf->entity;

            dol_syslog("Bon-Prelevement::Create sql=".$sql, LOG_DEBUG);
            $resql=$this->db->query($sql);
            if (! $resql)
            {
                $error++;
                dol_syslog("Erreur mise a jour du total - $sql");
            }

            /*
             * Rollback ou Commit
             *
             */
            if (!$error)
            {
                $this->db->commit();
            }
            else
            {
                $this->db->rollback();
                dol_syslog("Error",LOG_ERROR);
            }

            return sizeof($factures_prev);
        }
        else
        {
            return 0;
        }
    }


	/**
	*	Returns clickable name (with picto)
	*	@param		withpicto	link with picto
	*	@param		option		link target
	*	@return		string		URL of target
	*/
	function getNomUrl($withpicto=0,$option='')
	{
		global $langs;

		$result='';

		$lien = '<a href="'.DOL_URL_ROOT.'/compta/prelevement/fiche.php?id='.$this->id.'">';
		$lienfin='</a>';

		if ($option == 'xxx')
		{
			$lien = '<a href="'.DOL_URL_ROOT.'/compta/prelevement/fiche.php?id='.$this->id.'">';
			$lienfin='</a>';
		}

		if ($withpicto) $result.=($lien.img_object($langs->trans("ShowWithdraw"),'payment').$lienfin.' ');
		$result.=$lien.$this->ref.$lienfin;
		return $result;
	}


    /**
	*	Delete a notification def by id
	*	@param	rowid	id of notification
	*	@return	int		0 if OK, <0 if KO
	*/
    function DeleteNotificationById($rowid)
    {
        $result = 0;

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."notify_def";
        $sql.= " WHERE rowid = '".$rowid."'";

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
	*	Delete a notification
	*	@param	user	notification user
	*	@param	action	notification action
	*	@return	int		>0 if OK, <0 if KO
	*/
    function DeleteNotification($user, $action)
    {
        $result = 0;

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."notify_def";
		$sql .= " WHERE fk_user=".$user." AND fk_action=".$action;

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
	*	Add a notification
	*	@param	user	notification user
	*	@param	action	notification action
	*	@return	int		0 if OK, <0 if KO
	*/
    function AddNotification($db, $user, $action)
    {
        $result = 0;

        if ($this->DeleteNotification($user, $action) == 0)
        {
        	$sql = "INSERT INTO ".MAIN_DB_PREFIX."notify_def (datec,fk_user, fk_soc, fk_contact, fk_action)";
			$sql .= " VALUES (".$db->idate(mktime()).",".$user.", 'NULL', 'NULL', ".$action.")";
			
            dol_syslog("adnotiff: ".$sql);
            if ($this->db->query($sql))
            {
                $result = 0;
            }
            else
            {
                $result = -1;
                dol_syslog("BonPrelevement::AddNotification Error $result");
            }
        }

        return $result;
    }


    /**
    *	Generate a withdrawal file (format CFONB ?)
    *	File is generated with name this->filename
    *	@return	int	0 if OK, <0 if KO
    */
    function Generate()
    {
    	global $conf;

        $result = -1;

        dol_syslog("BonPrelevement::Generate build file ".$this->filename);

        $this->file = fopen ($this->filename,"w");

        /*
         * En-tete Emetteur
         */
        $this->EnregEmetteur();

        /*
         * Lines
         */
        $this->total = 0;

        $sql = "SELECT pl.rowid, pl.client_nom, pl.code_banque, pl.code_guichet, pl.number, pl.amount,";
        $sql.= " f.facnumber, pf.fk_facture";
        $sql.= " FROM";
        $sql.= " ".MAIN_DB_PREFIX."prelevement_lignes as pl,";
        $sql.= " ".MAIN_DB_PREFIX."facture as f,";
        $sql.= " ".MAIN_DB_PREFIX."prelevement_facture as pf";
        $sql.= " WHERE pl.fk_prelevement_bons = ".$this->id;
        $sql.= " AND pl.rowid = pf.fk_prelevement_lignes";
        $sql.= " AND pf.fk_facture = f.rowid";

        $i = 0;

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);

            while ($i < $num)
            {
                $row = $this->db->fetch_row($resql);

                $this->EnregDestinataire($row[0],
                $row[1],
                $row[2],
                $row[3],
                $row[4],
                $row[5],
                $row[6],
                $row[7]);

                $this->total = $this->total + $row[5];

                $i++;
            }
        }
        else
        {
            $result = -2;
        }

        /*
        * Pied de page total
        */

        $this->EnregTotal($this->total);

        fclose($this->file);
		if (! empty($conf->global->MAIN_UMASK))
			@chmod($this->file, octdec($conf->global->MAIN_UMASK));

        return $result;
    }


    /**
    *	Write recipient of request (customer)
    *	@param	rowid		id of line
    *	@param	client_nom	name of customer
    *	@param	rib_banque	code of bank
    *	@param	rib_guichet code of bank office
    *	@param	rib_number	bank account
    *	@param	amount		amount
    *	@param	facnumber	ref of invoice
    *	@param	facid		id of invoice
    */
    function EnregDestinataire($rowid, $client_nom, $rib_banque, $rib_guichet, $rib_number, $amount, $facnumber, $facid)
    {
        fputs ($this->file, "06");
        fputs ($this->file, "08"); // Prelevement ordinaire

        fputs ($this->file, "        "); // Zone Reservee B2

        fputs ($this->file, $this->numero_national_emetteur); // Numero National d'emmetteur B3

        // Date d'echeance C1

        fputs ($this->file, "       ");
        fputs ($this->file, strftime("%d%m", $this->date_echeance));
        fputs ($this->file, substr(strftime("%y", $this->date_echeance),1));

        // Raison Sociale Destinataire C2

        fputs ($this->file, substr($client->nom. "                           ",0,24));

        // Domiciliation facultative D1

        fputs ($this->file, substr("                                    ",0,24));

        // Zone Reservee D2

        fputs ($this->file, substr("                             ",0,8));

        // Code Guichet  D3

        fputs ($this->file, $rib_guichet);

        // Numero de compte D4

        fputs ($this->file, substr("000000000000000".$rib_number, -11));

        // Zone E Montant

        $montant = (round($amount,2) * 100);

        fputs ($this->file, substr("000000000000000".$montant, -16));

        // Libelle F

        fputs ($this->file, substr("*".$this->ref.$rowid."                                   ",0,13));
        fputs ($this->file, substr("                                        ",0,18));

        // Code etablissement G1

        fputs ($this->file, $rib_banque);

        // Zone Reservee G2

        fputs ($this->file, substr("                                        ",0,5));

        fputs ($this->file, "\n");
    }


    /**
    *	Write sender of request (me)
    */
    function EnregEmetteur()
    {
        fputs ($this->file, "03");
        fputs ($this->file, "08"); // Prelevement ordinaire

        fputs ($this->file, "        "); // Zone Reservee B2

        fputs ($this->file, $this->numero_national_emetteur); // Numero National d'emmetteur B3

        // Date d'echeance C1

        fputs ($this->file, "       ");
        fputs ($this->file, strftime("%d%m", $this->date_echeance));
        fputs ($this->file, substr(strftime("%y", $this->date_echeance),1));

        // Raison Sociale C2

        fputs ($this->file, substr($this->raison_sociale. "                           ",0,24));

        // Reference de la remise creancier D1 sur 7 caracteres

        fputs ($this->file, substr($this->reference_remise. "                           ",0,7));

        // Zone Reservee D1-2

        fputs ($this->file, substr("                                    ",0,17));

        // Zone Reservee D2

        fputs ($this->file, substr("                             ",0,2));
        fputs ($this->file, "E");
        fputs ($this->file, substr("                             ",0,5));

        // Code Guichet  D3

        fputs ($this->file, $this->emetteur_code_guichet);

        // Numero de compte D4

        fputs ($this->file, substr("000000000000000".$this->emetteur_numero_compte, -11));

        // Zone Reservee E

        fputs ($this->file, substr("                                        ",0,16));

        // Zone Reservee F

        fputs ($this->file, substr("                                        ",0,31));

        // Code etablissement

        fputs ($this->file, $this->emetteur_code_etablissement);

        // Zone Reservee G

        fputs ($this->file, substr("                                        ",0,5));

        fputs ($this->file, "\n");

    }

    /**
    *	Write end
    *	@param	total	total amount
    */
    function EnregTotal($total)
    {
        fputs ($this->file, "08");
        fputs ($this->file, "08"); // Prelevement ordinaire

        fputs ($this->file, "        "); // Zone Reservee B2

        fputs ($this->file, $this->numero_national_emetteur); // Numero National d'emmetteur B3

        // Reserve C1

        fputs ($this->file, substr("                           ",0,12));


        // Raison Sociale C2

        fputs ($this->file, substr("                           ",0,24));

        // D1

        fputs ($this->file, substr("                                    ",0,24));

        // Zone Reservee D2

        fputs ($this->file, substr("                             ",0,8));

        // Code Guichet  D3

        fputs ($this->file, substr("                             ",0,5));

        // Numero de compte D4

        fputs ($this->file, substr("                             ",0,11));

        // Zone E Montant

        $montant = ($total * 100);

        fputs ($this->file, substr("000000000000000".$montant, -16));

        // Zone Reservee F

        fputs ($this->file, substr("                                        ",0,31));

        // Code etablissement

        fputs ($this->file, substr("                                        ",0,5));

        // Zone Reservee F

        fputs ($this->file, substr("                                        ",0,5));

        fputs ($this->file, "\n");
    }
}

?>