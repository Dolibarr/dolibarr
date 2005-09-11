<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Destailleur Laurent  <eldy@users.sourceforge.net>
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
	    \file       htdocs/contrat/contrat.class.php
        \ingroup    contrat
		\brief      Fichier de la classe des contrats
		\version    $Revision$
*/

require_once(DOL_DOCUMENT_ROOT."/product.class.php");


/**
        \class      Contrat
		\brief      Classe permettant la gestion des contrats
*/

class Contrat
{
    var $db;
    
    var $id;
    var $ref;
    var $product;
    var $societe;

    var $user_author;
    var $user_service;
    var $user_cloture;
    var $date_creation;
    var $date_validation;
    var $date_cloture;

    var $commercial_signature_id;
    var $commercial_suivi_id;

    var $fk_projet;
        
    var $statuts=array();
    
        
    /**
     *    \brief      Constructeur de la classe
     *    \param      DB          handler accès base de données
     */
    function Contrat($DB)
    {
        global $langs;
        
        $this->db = $DB ;
        $this->product = new Product($DB);
        $this->societe = new Societe($DB);
        $this->user_service = new User($DB);
        $this->user_cloture = new User($DB);
        
        // Statut 0=ouvert, 1=actif, 2=cloturé
        $this->statuts[0]=$langs->trans("Draft");
        $this->statuts[1]=$langs->trans("Validated");
        $this->statuts[2]=$langs->trans("Closed");
    }

    /**
     *      \brief      Active une ligne detail d'un contrat
     *      \param      user        Objet User qui avtice le contrat
     *      \param      line_id     Id de la ligne de detail à activer
     *      \param      date        Date d'ouverture
     *      \param      dateend     Date fin prévue
     *      \return     int         < 0 si erreur, > 0 si ok
     */
    function active_line($user, $line_id, $date, $dateend='')
    {
        // statut actif : 4
    
        $sql = "UPDATE ".MAIN_DB_PREFIX."contratdet SET statut = 4,";
        $sql.= " date_ouverture = '".$this->db->idate($date)."',";
        if ($dateend) $sql.= " date_fin_validite = '".$this->db->idate($dateend)."',";
        $sql.= " fk_user_ouverture = ".$user->id;
        $sql.= " WHERE rowid = ".$line_id . " AND (statut = 0 OR statut = 3) ";
    
        $result = $this->db->query($sql) ;
    
        if ($result)
        {
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $interface->run_triggers('CONTRACT_SERVICE_ACTIVATE',$this,$user,$lang,$conf);
            // Fin appel triggers
    
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }
    
    
    /**
     *      \brief      Active une ligne detail d'un contrat
     *      \param      user        Objet User qui avtice le contrat
     *      \param      line_id     Id de la ligne de detail à activer
     *      \param      dateend     Date fin
     *      \return     int         <0 si erreur, >0 si ok
     */
    function close_line($user, $line_id, $dateend)
    {
        // statut actif : 4
    
        $sql = "UPDATE ".MAIN_DB_PREFIX."contratdet SET statut = 5,";
        $sql.= " date_cloture = '".$this->db->idate($dateend)."',";
        $sql.= " fk_user_cloture = ".$user->id;
        $sql.= " WHERE rowid = ".$line_id . " AND statut = 4";
    
        $result = $this->db->query($sql) ;
    
        if ($result)
        {
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $interface->run_triggers('CONTRACT_SERVICE_CLOSE',$this,$user,$lang,$conf);
            // Fin appel triggers
    
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }
    

    /**
     *    \brief      Cloture un contrat
     *    \param      user      Objet User qui cloture
     *    \param      lang      Environnement langue de l'utilisateur
     *    \param      conf      Environnement de configuration lors de l'opération
     *
     */
    function cloture($user,$lang='',$conf='')
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."contrat SET statut = 2";
        $sql .= " , date_cloture = now(), fk_user_cloture = ".$user->id;
        $sql .= " WHERE rowid = ".$this->id . " AND statut = 1";
    
        $result = $this->db->query($sql) ;
    
        // Appel des triggers
        include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
        $interface=new Interfaces($this->db);
        $interface->run_triggers('CONTRACT_CLOSE',$this,$user,$lang,$conf);
        // Fin appel triggers

        return 1;
    }
    
    /**
     *    \brief      Valide un contrat
     *    \param      user      Objet User qui valide
     *    \param      lang      Environnement langue de l'utilisateur
     *    \param      conf      Environnement de configuration lors de l'opération
     */
    function validate($user,$lang='',$conf='')
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."contrat SET statut = 1";
        $sql .= " WHERE rowid = ".$this->id . " AND statut = 0";
    
        $result = $this->db->query($sql) ;
    
        // Appel des triggers
        include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
        $interface=new Interfaces($this->db);
        $interface->run_triggers('CONTRACT_VALIDATE',$this,$user,$lang,$conf);
        // Fin appel triggers
    }

    /**
     *    \brief      Annule un contrat
     *    \param      user      Objet User qui annule
     *    \param      lang      Environnement langue de l'utilisateur
     *    \param      conf      Environnement de configuration lors de l'opération
     */
    function annule($user,$lang='',$conf='')
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."contrat SET statut = 0";
        $sql .= " , date_cloture = now(), fk_user_cloture = ".$user->id;
        $sql .= " WHERE rowid = ".$this->id . " AND statut = 1";
    
        $result = $this->db->query($sql) ;
    
        // Appel des triggers
        include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
        $interface=new Interfaces($this->db);
        $interface->run_triggers('CONTRACT_CANCEL',$this,$user,$lang,$conf);
        // Fin appel triggers
    }
    
    /**
     *    \brief      Chargement depuis la base des données du contrat
     *    \param      id      Id du contrat à charger
     *    \return     int     <0 si ko, id du contrat chargé si ok
     */
    function fetch($id)
    {
        $sql = "SELECT rowid, statut, fk_soc, ".$this->db->pdate("mise_en_service")." as datemise,";
        $sql.= " fk_user_mise_en_service, ".$this->db->pdate("date_contrat")." as datecontrat,";
        $sql.= " fk_user_author,";
        $sql.= " fk_projet,";
        $sql.= " fk_commercial_signature, fk_commercial_suivi ";
        $sql.= " FROM ".MAIN_DB_PREFIX."contrat WHERE rowid = $id";
    
        $resql = $this->db->query($sql) ;
 
        if ($resql)
        {
            $result = $this->db->fetch_array($resql);
    
            if ($result)
            {
                $this->id                = $result["rowid"];
                $this->ref               = $result["rowid"];
                $this->statut            = $result["statut"];
                $this->factureid         = $result["fk_facture"];
                $this->facturedetid      = $result["fk_facturedet"];
                $this->mise_en_service   = $result["datemise"];
                $this->date_fin_validite = $result["datefin"];
                $this->date_contrat      = $result["datecontrat"];
        
                $this->user_author_id    = $result["fk_user_author"];
        
                $this->commercial_signature_id = $result["fk_commercial_signature"];
                $this->commercial_suivi_id = $result["fk_commercial_suivi"];
        
                $this->user_service->id = $result["fk_user_mise_en_service"];
                $this->user_cloture->id = $result["fk_user_cloture"];
    
                $this->fk_projet        = $result["fk_projet"];
        
                $this->societe->fetch($result["fk_soc"]);
        
                $this->db->free($resql);
    
                return $this->id;
            }
            else
            {
                dolibarr_syslog("Contrat::Fetch Erreur contrat non trouve");
                $this->error="Contrat non trouve";
                return -2;
            }
        }
        else
        {
            dolibarr_syslog("Contrat::Fetch Erreur lecture contrat");
            $this->error=$this->db->error();
            return -1;
        }
    
    }
    
    /**
     *      \brief      Reinitialise le tableau lignes
     */
    function fetch_lignes()
    {
        $this->lignes = array();
    
        /*
         * Lignes contrats liées à un produit
         */
        $sql = "SELECT p.rowid, p.label, p.description as product_desc, p.ref,";
        $sql.= " d.description, d.price_ht, d.tva_tx, d.qty, d.remise_percent, d.subprice,";
        $sql.= " d.date_ouverture_prevue, d.date_ouverture,";
        $sql.= " d.date_fin_validite, d.date_cloture";
        $sql.= " FROM ".MAIN_DB_PREFIX."contratdet as d, ".MAIN_DB_PREFIX."product as p";
        $sql.= " WHERE d.fk_contrat = ".$this->id ." AND d.fk_product = p.rowid";
        $sql.= " ORDER by d.rowid ASC";
        
        $result = $this->db->query($sql);
        if ($result)
        {
            $num = $this->db->num_rows($result);
            $i = 0;
        
            while ($i < $num)
            {
                $objp                  = $this->db->fetch_object($result);
        
                $ligne                 = new ContratLigne();
                $ligne->id             = $objp->rowid;
                $ligne->desc           = stripslashes($objp->description);  // Description ligne
                $ligne->libelle        = stripslashes($objp->label);        // Label produit
                $ligne->product_desc   = stripslashes($objp->product_desc); // Description produit
                $ligne->qty            = $objp->qty;
                $ligne->ref            = $objp->ref;
                $ligne->tva_tx         = $objp->tva_tx;
                $ligne->subprice       = $objp->subprice;
                $ligne->remise_percent = $objp->remise_percent;
                $ligne->price          = $objp->price;
                $ligne->product_id     = $objp->rowid;
                    
                $ligne->date_debut_prevue = $objp->date_ouverture_prevue;
                $ligne->date_debut_reel   = $objp->date_ouverture;
                $ligne->date_fin_prevue   = $objp->date_fin_validite;
                $ligne->date_fin_reel     = $objp->date_cloture;
        
                $this->lignes[$i]      = $ligne;
                //dolibarr_syslog("1 ".$ligne->desc);
                //dolibarr_syslog("2 ".$ligne->product_desc);
                $i++;
            }
            $this->db->free($result);
        }
        else
        {
            dolibarr_syslog("Contrat::Fetch Erreur lecture des lignes de contrats liées aux produits");
            return -3;
        }
        
        /*
         * Lignes contrat liées à aucun produit
         */
        $sql = "SELECT d.qty, d.description, d.price_ht, d.subprice, d.tva_tx, d.rowid, d.remise_percent,";
        $sql.= " d.date_ouverture_prevue, d.date_ouverture,";
        $sql.= " d.date_fin_validite, d.date_cloture";
        $sql.= " FROM ".MAIN_DB_PREFIX."contratdet as d";
        $sql.= " WHERE d.fk_contrat = ".$this->id ." AND d.fk_product = 0";
        
        $result = $this->db->query($sql);
        if ($result)
        {
            $num = $this->db->num_rows($result);
            $j = 0;
        
            while ($j < $num)
            {
                $objp                  = $this->db->fetch_object($result);
                $ligne                 = new PropaleLigne();
                $ligne->libelle        = stripslashes($objp->description);
                $ligne->desc           = stripslashes($objp->description);
                $ligne->qty            = $objp->qty;
                $ligne->ref            = $objp->ref;
                $ligne->tva_tx         = $objp->tva_tx;
                $ligne->subprice       = $objp->subprice;
                $ligne->remise_percent = $objp->remise_percent;
                $ligne->price          = $objp->price;
                $ligne->product_id     = 0;
        
                $ligne->date_debut_prevue = $objp->date_ouverture_prevue;
                $ligne->date_debut_reel   = $objp->date_ouverture;
                $ligne->date_fin_prevue   = $objp->date_fin_validite;
                $ligne->date_fin_reel     = $objp->date_cloture;
        
                $this->lignes[$i]      = $ligne;
                $i++;
                $j++;
            }
        
            $this->db->free($result);
        }
        else
        {
            dolibarr_syslog("Contrat::Fetch Erreur lecture des lignes de contrat non liées aux produits");
            $this->error=$this->db->error();
            return -2;
        }
    
        return $this->lignes;
    }
  
    /**
     *      \brief      Crée un contrat vierge en base
     *      \param      user        Utilisateur qui crée
     *      \param      langs       Environnement langue de l'utilisateur
     *      \param      conf        Environnement de configuration lors de l'opération
     *      \return     int         <0 si erreur, id contrat créé sinon
     */
    function create($user,$langs='',$conf='')
    {
        // Controle validité des paramètres
        $paramsok=1;
        if ($this->commercial_signature_id <= 0)
        {
            $langs->load("commercial");
            $this->error.=$langs->trans("ErrorFieldRequired",$langs->trans("SalesRepresentativeSignature"));
            $paramsok=0;
        }
        if ($this->commercial_suivi_id <= 0)
        {
            $langs->load("commercial");
            $this->error.=($this->error?"<br>":'');
            $this->error.=$langs->trans("ErrorFieldRequired",$langs->trans("SalesRepresentativeFollowUp"));
            $paramsok=0;
        }
        if (! $paramsok) return -1;
        
        $this->db->begin();

        // Insère contrat
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."contrat (datec, fk_soc, fk_user_author, fk_commercial_signature, fk_commercial_suivi, date_contrat)";
        $sql.= " VALUES (now(),".$this->soc_id.",".$user->id;
        $sql.= ",".($this->commercial_signature_id>=0?$this->commercial_signature_id:"null");
        $sql.= ",".($this->commercial_suivi_id>=0?$this->commercial_suivi_id:"null");
        $sql.= ",".$this->db->idate($this->date_contrat) .")";
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."contrat");
    
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $interface->run_triggers('CONTRACT_CREATE',$this,$user,$lang,$conf);
            // Fin appel triggers
    
            $this->db->commit();

            return $this->id;
        }
        else
        {
            $this->db->rollback();

            dolibarr_syslog("Contrat::create - 10");
            $this->error=$lang->trans("UnknownError: ".$this->db->error()." - sql=".$sql);
            return -1;
        }
    }
    

    /**
     *      \brief      Supprime un contrat de la base
     *      \param      user        Utilisateur qui supprime
     *      \param      lang        Environnement langue de l'utilisateur
     *      \param      conf        Environnement de configuration lors de l'opération
     *      \return     int         < 0 si erreur, > 0 si ok
     */
    function delete($user,$lang='',$conf='')
    {
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."contrat";
        $sql.= " WHERE rowid=".$this->id;
        if ($this->db->query($sql))
        {
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $interface->run_triggers('CONTRACT_DELETE',$this,$user,$lang,$conf);
            // Fin appel triggers
    
            return 1;
        }
        else
        {
            return -1;
        }
    }

    
    /**
     *      \brief      Ajoute une ligne de contrat en base
     *      \param      desc            Description de la ligne
     *      \param      pu              Prix unitaire
     *      \param      qty             Quantité
     *      \param      txtva           Taux tva
     *      \param      fk_product      Id produit
     *      \param      remise_percent  Pourcentage de remise de la ligne
     *      \param      datestart       Date de debut prévue
     *      \param      dateend         Date de fin prévue
     *      \return     int             <0 si erreur, >0 si ok
     */
    function addline($desc, $pu, $qty, $txtva, $fk_product=0, $remise_percent=0, $datestart, $dateend)
    {
        global $langs;
        
        dolibarr_syslog("contrat.class.php::addline $desc, $pu, $qty, $txtva, $fk_product, $remise_percent, $datestart, $dateend");

        if ($this->statut == 0)
        {
            $qty = ereg_replace(",",".",$qty);
            $pu = ereg_replace(",",".",$pu);
            
            if ($fk_product > 0)
            {
                $prod = new Product($this->db, $fk_product);
                if ($prod->fetch($fk_product) > 0)
                {
                    $label = $prod->libelle;
                    $pu    = $prod->price;
                    $txtva = $prod->tva_tx;
                }
            }
            
            $remise = 0;
            $price = ereg_replace(",",".",round($pu, 2));
            $subprice = $price;
            if (strlen($remise_percent) > 0)
            {
                $remise = round(($pu * $remise_percent / 100), 2);
                $price = $pu - $remise;
            }
            
            // Insertion dans la base
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."contratdet";
            $sql.= " (fk_contrat, label, description, fk_product, price_ht, qty, tva_tx,";
            $sql.= " remise_percent, subprice, remise";
            if ($datestart > 0) { $sql.= ",date_ouverture_prevue"; }
            if ($dateend > 0)  { $sql.= ",date_fin_validite"; }
            $sql.= ") VALUES ($this->id, '" . addslashes($label) . "','" . addslashes($desc) . "',";
            $sql.= ($fk_product>0 ? $fk_product : "null");
            $sql.= ",".ereg_replace(",",".",$price).", '$qty', $txtva, $remise_percent,'".ereg_replace(",",".",$subprice)."','".ereg_replace(",",".", $remise)."'";
            if ($datestart > 0) { $sql.= ",".$this->db->idate($datestart); }
            if ($dateend > 0) { $sql.= ",".$this->db->idate($dateend); }
            $sql.= ");";
            
            if ( $this->db->query($sql) )
            {
                $this->update_price();
                return 1;
            }
            else
            {
                $this->error=$this->db->error();
                return -1;
            }
        }
        else
        {
            return -2;
        }
    }

    /**
     *      \brief     Mets à jour une ligne de contrat
     *      \param     rowid            Id de la ligne de facture
     *      \param     desc             Description de la ligne
     *      \param     pu               Prix unitaire
     *      \param     qty              Quantité
     *      \param     remise_percent   Pourcentage de remise de la ligne
     *      \param     datestart        Date de debut prévue
     *      \param     dateend          Date de fin prévue
     *      \param     tvatx            Taux TVA
     *      \return    int              < 0 si erreur, > 0 si ok
     */
    function updateline($rowid, $desc, $pu, $qty, $remise_percent=0, $datestart='', $dateend='', $tvatx)
    {
// On doit pouvoir modifier datestart et dateend meme si non brouillon
//        if ($this->statut == 0)
//        {
            // Nettoyage parametres
            $qty=trim($qty);
            $desc=trim($desc);
            $desc=trim($desc);
            $price = ereg_replace(",",".",$pu);
            $tvatx = ereg_replace(",",".",$tvatx);
            $subprice = $price;
            $remise = 0;
            if (strlen($remise_percent) > 0)
            {
                $remise = round(($pu * $remise_percent / 100), 2);
                $price = $pu - $remise;
            }
            else
            {
                $remise_percent=0;
            }
    
            dolibarr_syslog("Contrat::UpdateLine $rowid, $desc, $pu, $qty, $remise_percent, $datestart, $dateend, $tvatx");
        
            $this->db->begin();
    
            $sql = "UPDATE ".MAIN_DB_PREFIX."contratdet set description='".addslashes($desc)."'";
            $sql .= ",price_ht='" .     ereg_replace(",",".",$price)."'";
            $sql .= ",subprice='" .     ereg_replace(",",".",$subprice)."'";
            $sql .= ",remise='" .       ereg_replace(",",".",$remise)."'";
            $sql .= ",remise_percent='".ereg_replace(",",".",$remise_percent)."'";
            $sql .= ",qty='$qty'";
            $sql .= ",tva_tx='".        ereg_replace(",",".",$tvatx)."'";
    
            if ($datestart > 0) { $sql.= ",date_ouverture_prevue=".$this->db->idate($datestart); }
            else { $sql.=",date_ouverture_prevue=null"; }
            if ($dateend > 0) { $sql.= ",date_fin_validite=".$this->db->idate($dateend); }
            else { $sql.=",date_fin_validite=null"; }
    
            $sql .= " WHERE rowid = $rowid ;";
    
            $result = $this->db->query($sql);
            if ($result)
            {
                $this->update_price();
    
                $this->db->commit();
    
                return 1;
            }
            else
            {
                $this->db->rollback();
                $this->error=$this->db->error();
                dolibarr_syslog("Contrat::UpdateLigne Erreur -1");
    
                return -1;
            }
//        }
//        else
//        {
//            dolibarr_syslog("Contrat::UpdateLigne Erreur -2 Contrat en mode incompatible pour cette action");
//            return -2;
//        }
    }
    
    /**
     *      \brief      Supprime une ligne de detail
     *      \param      idligne     Id de la ligne detail à supprimer
     *      \return     int         >0 si ok, <0 si ko
     */
    function delete_line($idligne)
    {
        if ($this->statut == 0)
        {
            $sql = "DELETE FROM ".MAIN_DB_PREFIX."contratdet WHERE rowid =".$idligne;
        
            if ($this->db->query($sql) )
            {
                $this->update_price();
        
                return 1;
            }
            else
            {
                return -1;
            }
        }
        else
        {
            return -2;
        }
    }


    /**
     *      \brief      Mets à jour le prix total du contrat
     */
    
    function update_price()
    {
        include_once DOL_DOCUMENT_ROOT . "/lib/price.lib.php";
    
        /*
         *  Liste des produits a ajouter
         */
        $sql = "SELECT price_ht, qty, tva_tx";
        $sql.= " FROM ".MAIN_DB_PREFIX."contratdet";
        $sql.= " WHERE fk_contrat = ".$this->id;
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;
    
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($resql);
                $products[$i][0] = $obj->price_ht;
                $products[$i][1] = $obj->qty;
                $products[$i][2] = $obj->tva_tx;
                $i++;
            }
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
        $calculs = calcul_price($products, $this->remise_percent);
    
        $this->remise         = $calculs[3];
        $this->total_ht       = $calculs[0];
        $this->total_tva      = $calculs[1];
        $this->total_ttc      = $calculs[2];

        /*
        // Met a jour en base
        $sql = "UPDATE ".MAIN_DB_PREFIX."propal SET";
        $sql .= " price='".  ereg_replace(",",".",$this->total_ht)."'";
        $sql .= ", tva='".   ereg_replace(",",".",$this->total_tva)."'";
        $sql .= ", total='". ereg_replace(",",".",$this->total_ttc)."'";
        $sql .= ", remise='".ereg_replace(",",".",$this->remise)."'";
        $sql .=" WHERE rowid = $this->id";
    
        if ( $this->db->query($sql) )
        {
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
        */
    }
    

    /**
     *      \brief     Classe le contrat dans un projet
     *      \param     projid       Id du projet dans lequel classer le contrat
     */
    function classin($projid)
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."contrat";
        if ($projid) $sql.= " SET fk_projet = $projid";
        else $sql.= " SET fk_projet = NULL";
        $sql.= " WHERE rowid = ".$this->id;

        if ($this->db->query($sql))
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
   *    \brief      Retourne le libellé du statut du contrat
   *    \return     string      Libellé
   */
    function getLibStatut()
    {
		return $this->LibStatut($this->statut);
    }

  /**
   *    \brief      Renvoi le libellé d'un statut donné
   *    \param      statut      id statut
   *    \return     string      Libellé
   */
    function LibStatut($statut)
    {
        global $langs;
        $langs->load("contracts");

        if ($statut == 0) { return $langs->trans("ContractStatusDraft"); }
        if ($statut == 1) { return $langs->trans("ContractStatusValidated"); }
        if ($statut == 2) { return $langs->trans("ContractStatusClosed"); }
    }


   /*
    *       \brief     Charge les informations d'ordre info dans l'objet contrat
    *       \param     id     id du contrat a charger
    */
    function info($id)
    {
        $sql = "SELECT c.rowid, ".$this->db->pdate("datec")." as datec, ".$this->db->pdate("date_cloture")." as date_cloture,";
        $sql.= $this->db->pdate("c.tms")." as date_modification,";
        $sql.= " fk_user_author, fk_user_cloture";
        $sql.= " FROM ".MAIN_DB_PREFIX."contrat as c";
        $sql.= " WHERE c.rowid = ".$id;

        $result=$this->db->query($sql);
        if ($result)
        {
            if ($this->db->num_rows($result))
            {
                $obj = $this->db->fetch_object($result);

                $this->id = $obj->rowid;

                if ($obj->fk_user_author) {
                    $cuser = new User($this->db, $obj->fk_user_author);
                    $cuser->fetch();
                    $this->user_creation     = $cuser;
                }

                if ($obj->fk_user_cloture) {
                    $cuser = new User($this->db, $obj->fk_user_cloture);
                    $cuser->fetch();
                    $this->user_cloture = $cuser;
                }

                $this->date_creation     = $obj->datec;
                $this->date_modification = $obj->date_modification;
                $this->date_cloture      = $obj->date_cloture;
            }

            $this->db->free($result);

        }
        else
        {
            dolibarr_print_error($this->db);
        }
    }
 
 
    /** 
     *    \brief      Récupère les lignes de detail du contrat
     *    \param      statut      Statut des lignes detail à récupérer
     *    \return     array       Tableau des lignes de details
     */
    function array_detail($statut=-1)
    {
        $tab=array();
        
        $sql = "SELECT cd.rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX."contratdet as cd";
        $sql.= " WHERE fk_contrat =".$this->id;
        if ($statut >= 0) $sql.= " AND statut = '$statut'";
   
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $num=$this->db->num_rows($result);
            $i=0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($result);
                $tab[$i]=$obj->rowid;
                $i++;
            }
            return $tab;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }


    /**
     *      \brief      Charge indicateurs this->nbtodo et this->nbtodolate de tableau de bord
     *      \param      user        Objet user
     *      \param      mode        "inactive" pour services à activer, "expired" pour services expirés
     *      \return     int         <0 si ko, >0 si ok
     */
    function load_board($user,$mode)
    {
        global $conf;
        
        $this->nbtodo=$this->nbtodolate=0;
        if ($mode == 'inactives')
        {
            $sql = "SELECT cd.rowid,".$this->db->pdate("cd.date_ouverture_prevue")." as datefin";
            $sql.= " FROM ".MAIN_DB_PREFIX."contrat as c, ".MAIN_DB_PREFIX."contratdet as cd";
            $sql.= " WHERE c.statut = 1 AND c.rowid = cd.fk_contrat";
            $sql.= " AND cd.statut = 0";
        }
        if ($mode == 'expired')
        {
            $sql = "SELECT cd.rowid,".$this->db->pdate("cd.date_fin_validite")." as datefin";
            $sql.= " FROM ".MAIN_DB_PREFIX."contrat as c, ".MAIN_DB_PREFIX."contratdet as cd";
            $sql.= " WHERE c.statut = 1 AND c.rowid = cd.fk_contrat";
            $sql.= " AND cd.statut = 4";
            $sql.= " AND cd.date_fin_validite < '".$this->db->idate(time())."'";
        }
        if ($user->societe_id) $sql.=" AND fk_soc = ".$user->societe_id;
        $resql=$this->db->query($sql);
        if ($resql)
        {
            while ($obj=$this->db->fetch_object($resql))
            {
                $this->nbtodo++;
                if ($mode == 'inactives')
                    if ($obj->datefin && $obj->datefin < (time() - $conf->contrat->services->inactifs->warning_delay)) $this->nbtodolate++;
                if ($mode == 'expired')
                    if ($obj->datefin && $obj->datefin < (time() - $conf->contrat->services->expires->warning_delay)) $this->nbtodolate++;
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

    /* gestion des contacts d'un contrat */

	/**
	 * 
	 *      \brief      Ajoute un contact associé au contrat
     *      \param      fk_socpeople    Id du contact a ajouter.
     *      \param      nature          description du contact
     *      \return     int             <0 si erreur, >0 si ok
     */
	 function add_contact($fk_socpeople, $nature)
	 {
        
        if ($fk_socpeople <= 0 
        	|| $this->societe->contact_get_email($fk_socpeople) == "" )
        {
        		// le contact n'existe pas ou est invalide
        		return -1;
        }
        
        $lNature = addslashes(trim($nature));
        $datecreate = mktime();
        
        // Insertion dans la base
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."contrat_contact";
        $sql.= " (fk_contrat, fk_socpeople, datecreate, statut, nature) ";
        $sql.= " VALUES ($this->id, $fk_socpeople , " ;
		$sql.= $this->db->pdate(time());
		$sql.= ", 5, '". $lNature . "' ";
        $sql.= ");";
        
        // Retour
        if ( $this->db->query($sql) )
        {
            return 0;
        }
        else
        {
            dolibarr_print_error($this->db);
            return -1;
        }
	 }    
	 
	 /**
	 * 
	 *      \brief      Misea jour du contact associé au contrat
     *      \param      rowid    La reference du lien contant contact.
     * 		\param		statut	Le nouveau statut
     *      \param      nature          description du contact
     *      \return     int             <0 si erreur, >0 si ok
     */
	 function update_contact($rowid, $statut,  $nature)
	 {
           
        $lNature = addslashes(trim($nature));
        
        // Insertion dans la base
        $sql = "UPDATE ".MAIN_DB_PREFIX."contrat_contact set ";
        $sql.= " statut = $statut ,";
        $sql.= " nature = '" . $lNature ."'";
        $sql.= " where rowid = $rowid ;";
        // Retour
        if (  $this->db->query($sql) )
        {
            return 0;
        }
        else
        {
            dolibarr_print_error($this->db);
            return -1;
        }
	 }    
	 
	/** 
     *    \brief      Supprime une ligne de contact de contrat
     *    \param      idligne		La reference du contact
     *    \return     statur     0 OK, -1 erreur
     */
	function delete_contact($idligne)
	{

      $sql = "DELETE FROM ".MAIN_DB_PREFIX."contrat_contact WHERE rowid =".$idligne;
      
      if ($this->db->query($sql) ) {
	
	  return 0;
	}
      else
	{
	  return 1;
	}
    }
	 		
    /** 
     *    \brief      Récupère les lignes de contact du contrat
     *    \param      statut      Statut des lignes detail à récupérer
     *    \return     array       Tableau des rowid des contacts
     */
    function liste_contact($statut=-1)
    {
        $tab=array();
     
        $sql = "SELECT cd.rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX."contrat_contact cd , ".MAIN_DB_PREFIX."socpeople sp";
        $sql.= " WHERE fk_contrat =".$this->id;
        $sql.= " and cd.fk_socpeople = sp.idp";
        if ($statut >= 0) $sql.= " AND statut = '$statut'";
        $sql.=" order by sp.name asc ;";
        
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $num=$this->db->num_rows($resql);
            $i=0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($resql);
                $tab[$i]=$obj->rowid;
                $i++;
            }
            return $tab;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }

	 /** 
     *    \brief      Le détail d'un contact
     *    \param      rowid      L'identifiant du contant de contrat
     *    \return     object     L'objet de construit par DoliDb.fetch_object
     */
 	function detail_contact($rowid)
    {
  
        $sql = "SELECT datecreate, statut, nature, fk_socpeople";
        $sql.= " FROM ".MAIN_DB_PREFIX."contrat_contact";
        $sql.= " WHERE rowid =".$rowid.";";

        $resql=$this->db->query($sql);
        if ($resql)
        {
             $obj = $this->db->fetch_object($result);
  
            return $obj;
        }
        else
        {
            $this->error=$this->db->error();
            return null;
        }
    }	

	 /** 
     *    \brief      La liste des valeurs possibles de nature de contats
     *    
     *    \return     array   La liste des natures
     */
 	function liste_nature_contact()
    {
  		$tab = array();
  		
        $sql = "SELECT distinct nature";
        $sql.= " FROM ".MAIN_DB_PREFIX."contrat_contact";
        $sql.= " order by nature;";

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $num=$this->db->num_rows($result);
            $i=0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($result);
                $tab[$i]=$obj->nature;
                $i++;
            }
            return $tab;
        }
        else
        {
            $this->error=$this->db->error();
            return null;
        }
    }		 			
    
}


/**
        \class      ContratLigne
		\brief      Classe permettant la gestion des lignes de contrats
*/

class ContratLigne  
{
    var $id;
    var $desc;
    var $libelle;
    var $product_desc;
    var $qty;
    var $ref;
    var $tva_tx;
    var $subprice;
    var $remise_percent;
    var $price;
    var $product_id;
                  
    var $date_debut_prevue;
    var $date_debut_reel;
    var $date_fin_prevue;
    var $date_fin_reel;

    function ContratLigne()
    {
    }
}


?>
