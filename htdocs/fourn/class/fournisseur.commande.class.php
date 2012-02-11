<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin         <regis@dolibarr.fr>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerke@telenet.be>
 * Copyright (C) 2010-2011 Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2010-2011 Philippe Grand        <philippe.grand@atoo-net.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/fourn/class/fournisseur.commande.class.php
 *	\ingroup    fournisseur,commande
 *	\brief      File of class to manage suppliers orders
 */

require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");


/**
 *	\class      CommandeFournisseur
 *	\brief      Class to manage predefined suppliers products
 */
class CommandeFournisseur extends Commande
{
    public $element='order_supplier';
    public $table_element='commande_fournisseur';
    public $table_element_line = 'commande_fournisseurdet';
    public $fk_element = 'fk_commande';
    protected $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

    var $ref;		 // TODO deprecated
    var $product_ref;
    var $ref_supplier;
    var $brouillon;
    var $statut;			// 0=Draft -> 1=Validated -> 2=Approved -> 3=Process runing -> 4=Received partially -> 5=Received totally -> (reopen) 4=Received partially
    //                                                          -> 7=Canceled/Never received -> (reopen) 3=Process runing
    //										-> 6=Canceled -> (reopen) 2=Approved
    //  		              -> 9=Refused  -> (reopen) 1=Validated
    var $socid;
    var $fourn_id;
    var $date;
    var $date_commande;
    var $total_ht;
    var $total_tva;
    var $total_localtax1;   // Total Local tax 1
    var $total_localtax2;   // Total Local tax 2
    var $total_ttc;
    var $source;
    var $note;
    var $note_public;
    var $model_pdf;
    var $fk_project;
    var $cond_reglement_id;
    var $cond_reglement_code;
    var $mode_reglement_id;
    var $mode_reglement_code;


    /**
     * 	Constructor
     *
     *  @param      DoliDB		$db      Handler d'acces aux bases de donnees
     */
    function CommandeFournisseur($db)
    {
        $this->db = $db;
        $this->products = array();
        $this->lines = array();

        // List of language codes for status
        $this->statuts[0] = 'StatusOrderDraft';
        $this->statuts[1] = 'StatusOrderValidated';
        $this->statuts[2] = 'StatusOrderApproved';
        $this->statuts[3] = 'StatusOrderOnProcess';
        $this->statuts[4] = 'StatusOrderReceivedPartially';
        $this->statuts[5] = 'StatusOrderReceivedAll';
        $this->statuts[6] = 'StatusOrderCanceled';
        $this->statuts[7] = 'StatusOrderCanceled';
        $this->statuts[9] = 'StatusOrderRefused';
    }


    /**
     *	Get object and lines from database
     *
     * 	@param	int		$id			Id of order to load
     * 	@param	string	$ref		Ref of object
     *	@return int 		        >0 if OK, <0 if KO, 0 if not found
     */
    function fetch($id,$ref='')
    {
        global $conf;

        // Check parameters
        if (empty($id) && empty($ref)) return -1;

        $sql = "SELECT c.rowid, c.ref, c.date_creation, c.fk_soc, c.fk_user_author, c.fk_statut, c.amount_ht, c.total_ht, c.total_ttc, c.tva,";
        $sql.= " c.localtax1, c.localtax2, ";
        $sql.= " c.date_commande as date_commande, c.fk_cond_reglement, c.fk_mode_reglement, c.fk_projet as fk_project, c.remise_percent, c.source, c.fk_methode_commande,";
        $sql.= " c.note, c.note_public, c.model_pdf,";
        $sql.= " cm.libelle as methode_commande,";
        $sql.= " cr.code as cond_reglement_code, cr.libelle as cond_reglement_libelle,";
        $sql.= " p.code as mode_reglement_code, p.libelle as mode_reglement_libelle";
        $sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as c";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_payment_term as cr ON (c.fk_cond_reglement = cr.rowid)";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as p ON (c.fk_mode_reglement = p.id)";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_input_method as cm ON cm.rowid = c.fk_methode_commande";
        $sql.= " WHERE c.entity = ".$conf->entity;
        if ($ref) $sql.= " AND c.ref='".$ref."'";
        else $sql.= " AND c.rowid=".$id;

        dol_syslog(get_class($this)."::fetch sql=".$sql,LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $obj = $this->db->fetch_object($resql);
            if (! $obj)
            {
                $this->error='Bill with id '.$id.' not found sql='.$sql;
                dol_syslog(get_class($this).'::fetch '.$this->error);
                return 0;
            }

            $this->id                  = $obj->rowid;
            $this->ref                 = $obj->ref;
            $this->socid               = $obj->fk_soc;
            $this->fourn_id            = $obj->fk_soc;
            $this->statut              = $obj->fk_statut;
            $this->user_author_id      = $obj->fk_user_author;
            $this->total_ht            = $obj->total_ht;
            $this->total_tva           = $obj->tva;
            $this->total_localtax1	   = $obj->localtax1;
            $this->total_localtax2	   = $obj->localtax2;
            $this->total_ttc           = $obj->total_ttc;
            $this->date_commande       = $this->db->jdate($obj->date_commande); // date a laquelle la commande a ete transmise
            $this->date                = $this->db->jdate($obj->date_creation);
            $this->remise_percent      = $obj->remise_percent;
            $this->methode_commande_id = $obj->fk_methode_commande;
            $this->methode_commande    = $obj->methode_commande;

            $this->source              = $obj->source;
            //$this->facturee            = $obj->facture;
            $this->fk_project          = $obj->fk_project;
            $this->cond_reglement_id   = $obj->fk_cond_reglement;
            $this->cond_reglement_code = $obj->cond_reglement_code;
            $this->cond_reglement      = $obj->cond_reglement_libelle;
            $this->cond_reglement_doc  = $obj->cond_reglement_libelle;
            $this->mode_reglement_id   = $obj->fk_mode_reglement;
            $this->mode_reglement_code = $obj->mode_reglement_code;
            $this->mode_reglement      = $obj->mode_reglement_libelle;
            $this->note                = $obj->note;
            $this->note_public         = $obj->note_public;
            $this->modelpdf            = $obj->model_pdf;

            $this->db->free($resql);

            if ($this->statut == 0) $this->brouillon = 1;


            $sql = "SELECT l.rowid, l.ref as ref_supplier, l.fk_product, l.product_type, l.label, l.description,";
            $sql.= " l.qty,";
            $sql.= " l.tva_tx, l.remise_percent, l.subprice,";
            $sql.= " l.localtax1_tx, l. localtax2_tx, l.total_localtax1, l.total_localtax2,";
            $sql.= " l.total_ht, l.total_tva, l.total_ttc,";
            $sql.= " p.rowid as product_id, p.ref as product_ref, p.label as label, p.description as product_desc";
            $sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet	as l";
            $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON l.fk_product = p.rowid';
            $sql.= " WHERE l.fk_commande = ".$this->id;
            $sql.= " ORDER BY l.rowid";
            //print $sql;

            dol_syslog(get_class($this)."::fetch get lines sql=".$sql,LOG_DEBUG);
            $result = $this->db->query($sql);
            if ($result)
            {
                $num = $this->db->num_rows($result);
                $i = 0;

                while ($i < $num)
                {
                    $objp                  = $this->db->fetch_object($result);

                    $line                 = new CommandeFournisseurLigne($this->db);

                    $line->id                  = $objp->rowid;
                    $line->desc                = $objp->description;  // Description ligne
                    $line->description         = $objp->description;  // Description ligne
                    $line->qty                 = $objp->qty;
                    $line->tva_tx              = $objp->tva_tx;
                    $line->localtax1_tx		   = $objp->localtax1_tx;
                    $line->localtax2_tx		   = $objp->localtax2_tx;
                    $line->subprice            = $objp->subprice;
                    $line->remise_percent      = $objp->remise_percent;
                    $line->total_ht            = $objp->total_ht;
                    $line->total_tva           = $objp->total_tva;
                    $line->total_localtax1	   = $objp->total_localtax1;
                    $line->total_localtax2	   = $objp->total_localtax2;
                    $line->total_ttc           = $objp->total_ttc;
                    $line->product_type        = $objp->product_type;

                    $line->fk_product          = $objp->fk_product;   // Id du produit
                    $line->libelle             = $objp->label;        // Label produit
                    $line->product_desc        = $objp->product_desc; // Description produit

                    $line->ref                 = $objp->product_ref;     // TODO deprecated
                    $line->product_ref         = $objp->product_ref;     // Internal reference
                    $line->ref_fourn           = $objp->ref_supplier;    // TODO deprecated
                    $line->ref_supplier        = $objp->ref_supplier;    // Reference supplier

                    $this->lines[$i]      = $line;

                    $i++;
                }
                $this->db->free($result);

                return 1;
            }
            else
            {
                $this->error=$this->db->error()." sql=".$sql;
                dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
                return -1;
            }
        }
        else
        {
            $this->error=$this->db->error()." sql=".$sql;
            dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     *   Add a line in log table
     *
     *   @param      User	$user       User making action
     *   @param      int	$statut     Status of order
     *   @param      date	$datelog    Date of change
     * 	 @param		 string $comment	Comment
     *   @return     int         		<0 if KO, >0 if OK
     */
    function log($user, $statut, $datelog, $comment='')
    {
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."commande_fournisseur_log (datelog, fk_commande, fk_statut, fk_user, comment)";
        $sql.= " VALUES (".$this->db->idate($datelog).",".$this->id.", ".$statut.", ";
        $sql.= $user->id.", ";
        $sql.= ($comment?"'".$this->db->escape($comment)."'":'null');
        $sql.= ")";

        dol_syslog("FournisseurCommande::log sql=".$sql, LOG_DEBUG);
        if ( $this->db->query($sql) )
        {
            return 1;
        }
        else
        {
            $this->error=$this->db->lasterror();
            dol_syslog("FournisseurCommande::log ".$this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     *	Validate an order
     *
     *	@param	User	$user		Validator User
     *	@return	int					<0 if KO, >0 if OK
     */
    function valid($user)
    {
        global $langs,$conf;
        require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");

        $error=0;

        dol_syslog("CommandeFournisseur::Valid");
        $result = 0;
        if ($user->rights->fournisseur->commande->valider)
        {
            $this->db->begin();

            // Definition du nom de modele de numerotation de commande
            $soc = new Societe($this->db);
            $soc->fetch($this->fourn_id);

            // Check if object has a temporary ref
            if (preg_match('/^[\(]?PROV/i', $this->ref))
            {
                $num = $this->getNextNumRef($soc);
            }
            else
            {
                $num = $this->ref;
            }

            $sql = 'UPDATE '.MAIN_DB_PREFIX."commande_fournisseur";
            $sql.= " SET ref='".$num."'";
            $sql.= ", fk_statut = 1";
            $sql.= ", date_valid=".$this->db->idate(mktime());
            $sql.= ", fk_user_valid = ".$user->id;
            $sql.= " WHERE rowid = ".$this->id;
            $sql.= " AND fk_statut = 0";

            $resql=$this->db->query($sql);
            if (! $resql)
            {
                dol_syslog("CommandeFournisseur::valid() Echec update - 10 - sql=".$sql, LOG_ERR);
                dol_print_error($this->db);
                $error++;
            }

            if (! $error)
            {
                // Rename directory if dir was a temporary ref
                if (preg_match('/^[\(]?PROV/i', $this->ref))
                {
                    // On renomme repertoire ($this->ref = ancienne ref, $num = nouvelle ref)
                    // afin de ne pas perdre les fichiers attaches
                    $oldref = dol_sanitizeFileName($this->ref);
                    $newref = dol_sanitizeFileName($num);
                    $dirsource = $conf->fournisseur->dir_output.'/commande/'.$oldref;
                    $dirdest = $conf->fournisseur->dir_output.'/commande/'.$newref;
                    if (file_exists($dirsource))
                    {
                        dol_syslog("CommandeFournisseur::valid() rename dir ".$dirsource." into ".$dirdest);

                        if (@rename($dirsource, $dirdest))
                        {
                            dol_syslog("Rename ok");
                            // Suppression ancien fichier PDF dans nouveau rep
                            dol_delete_file($dirdest.'/'.$oldref.'.*');
                        }
                    }
                }
            }

            if (! $error)
            {
                $result = 1;
                $this->log($user, 1, time());	// Statut 1
                $this->ref = $num;
            }

            if (! $error)
            {
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface=new Interfaces($this->db);
                $result=$interface->run_triggers('ORDER_SUPPLIER_VALIDATE',$this,$user,$langs,$conf);
                if ($result < 0) { $error++; $this->errors=$interface->errors; }
                // Fin appel triggers
            }

            if (! $error)
            {
                $this->db->commit();
                return 1;
            }
            else
            {
                dol_syslog("CommandeFournisseur::valid ".$this->error, LOG_ERR);
                $this->db->rollback();
                $this->error=$this->db->lasterror();
                return -1;
            }
        }
        else
        {
            $this->error='Not Authorized';
            dol_syslog("CommandeFournisseur::valid ".$this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     *	Set draft status
     *  TODO This method seems to be never called.
     *
     *	@param	User	$user			Object user that modify
     *	@param	int		$idwarehouse	Id warehouse to use for stock change.
     *	@return	int						<0 if KO, >0 if OK
     */
    function set_draft($user, $idwarehouse=-1)
    {
        global $conf,$langs;

        $error=0;

        // Protection
        if ($this->statut == 0)
        {
            dol_syslog("CommandeFournisseur::set_draft already draft status", LOG_WARNING);
            return 0;
        }

        if (! $user->rights->fournisseur->commande->valider)
        {
            $this->error='Permission denied';
            return -1;
        }

        $this->db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur";
        $sql.= " SET fk_statut = 0";
        $sql.= " WHERE rowid = ".$this->id;

        dol_syslog("CommandeFournisseur::set_draft sql=".$sql, LOG_DEBUG);
        if ($this->db->query($sql))
        {
            // If stock is incremented on validate order, we must redecrement it
            if ($conf->stock->enabled && $conf->global->STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER)
            {
                require_once(DOL_DOCUMENT_ROOT."/product/stock/class/mouvementstock.class.php");

                $num=count($this->lines);
                for ($i = 0; $i < $num; $i++)
                {
                    if ($this->lines[$i]->fk_product > 0)
                    {
                        $mouvP = new MouvementStock($this->db);
                        // We increment stock of product (and sub-products)
                        $result=$mouvP->reception($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, $this->lines[$i]->subprice, $langs->trans("OrderBackToDraftInDolibarr",$this->ref));
                        if ($result < 0) { $error++; }
                    }
                }

                if (!$error)
                {
                    $this->statut=0;
                    $this->db->commit();
                    return $result;
                }
                else
                {
                    $this->error=$mouvP->error;
                    $this->db->rollback();
                    return $result;
                }
            }

            $this->statut=0;
            $this->db->commit();
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            $this->db->rollback();
            dol_syslog($this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     *  Return label of the status of object
     *
     *  @param	int		$mode       0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label
     *  @return string        		Label
     */
    function getLibStatut($mode=0)
    {
        return $this->LibStatut($this->statut,$mode);
    }

    /**
     *  Return label of a status
     *
     * 	@param  int		$statut		Id statut
     *  @param  int		$mode       0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto
     *  @return string				Label of status
     */
    function LibStatut($statut,$mode=0)
    {
        global $langs;
        $langs->load('orders');

        // List of language codes for status
        $statutshort[0] = 'StatusOrderDraftShort';
        $statutshort[1] = 'StatusOrderValidatedShort';
        $statutshort[2] = 'StatusOrderApprovedShort';
        $statutshort[3] = 'StatusOrderOnProcessShort';
        $statutshort[4] = 'StatusOrderReceivedPartiallyShort';
        $statutshort[5] = 'StatusOrderReceivedAllShort';
        $statutshort[6] = 'StatusOrderCanceledShort';
        $statutshort[7] = 'StatusOrderCanceledShort';
        $statutshort[9] = 'StatusOrderRefusedShort';

        if ($mode == 0)
        {
            return $langs->trans($this->statuts[$statut]);
        }
        if ($mode == 1)
        {
            return $langs->trans($statutshort[$statut]);
        }
        if ($mode == 2)
        {
            return $langs->trans($this->statuts[$statut]);
        }
        if ($mode == 3)
        {
            if ($statut==0) return img_picto($langs->trans($this->statuts[$statut]),'statut0');
            if ($statut==1) return img_picto($langs->trans($this->statuts[$statut]),'statut1');
            if ($statut==2) return img_picto($langs->trans($this->statuts[$statut]),'statut3');
            if ($statut==3) return img_picto($langs->trans($this->statuts[$statut]),'statut3');
            if ($statut==4) return img_picto($langs->trans($this->statuts[$statut]),'statut3');
            if ($statut==5) return img_picto($langs->trans($this->statuts[$statut]),'statut6');
            if ($statut==6 || $statut==7) return img_picto($langs->trans($this->statuts[$statut]),'statut5');
            if ($statut==9) return img_picto($langs->trans($this->statuts[$statut]),'statut5');
        }
        if ($mode == 4)
        {
            if ($statut==0) return img_picto($langs->trans($this->statuts[$statut]),'statut0').' '.$langs->trans($this->statuts[$statut]);
            if ($statut==1) return img_picto($langs->trans($this->statuts[$statut]),'statut1').' '.$langs->trans($this->statuts[$statut]);
            if ($statut==2) return img_picto($langs->trans($this->statuts[$statut]),'statut3').' '.$langs->trans($this->statuts[$statut]);
            if ($statut==3) return img_picto($langs->trans($this->statuts[$statut]),'statut3').' '.$langs->trans($this->statuts[$statut]);
            if ($statut==4) return img_picto($langs->trans($this->statuts[$statut]),'statut3').' '.$langs->trans($this->statuts[$statut]);
            if ($statut==5) return img_picto($langs->trans($this->statuts[$statut]),'statut6').' '.$langs->trans($this->statuts[$statut]);
            if ($statut==6 || $statut==7) return img_picto($langs->trans($this->statuts[$statut]),'statut5').' '.$langs->trans($this->statuts[$statut]);
            if ($statut==9) return img_picto($langs->trans($this->statuts[$statut]),'statut5').' '.$langs->trans($this->statuts[$statut]);
        }
        if ($mode == 5)
        {
            if ($statut==0) return $langs->trans($this->statuts[$statut]).' '.img_picto($langs->trans($this->statuts[$statut]),'statut0');
            if ($statut==1) return $langs->trans($this->statuts[$statut]).' '.img_picto($langs->trans($this->statuts[$statut]),'statut1');
            if ($statut==2) return $langs->trans($this->statuts[$statut]).' '.img_picto($langs->trans($this->statuts[$statut]),'statut3');
            if ($statut==3) return $langs->trans($this->statuts[$statut]).' '.img_picto($langs->trans($this->statuts[$statut]),'statut3');
            if ($statut==4) return $langs->trans($this->statuts[$statut]).' '.img_picto($langs->trans($this->statuts[$statut]),'statut3');
            if ($statut==5) return $langs->trans($this->statuts[$statut]).' '.img_picto($langs->trans($this->statuts[$statut]),'statut6');
            if ($statut==6 || $statut==7) return $langs->trans($this->statuts[$statut]).' '.img_picto($langs->trans($this->statuts[$statut]),'statut5');
            if ($statut==9) return $langs->trans($this->statuts[$statut]).' '.img_picto($langs->trans($this->statuts[$statut]),'statut5');
        }
    }


    /**
     *	Renvoie nom clicable (avec eventuellement le picto)
     *
     *	@param		int		$withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
     *	@param		string	$option			Sur quoi pointe le lien
     *	@return		string					Chaine avec URL
     */
    function getNomUrl($withpicto=0,$option='')
    {
        global $langs;

        $result='';

        $lien = '<a href="'.DOL_URL_ROOT.'/fourn/commande/fiche.php?id='.$this->id.'">';
        $lienfin='</a>';

        $picto='order';
        $label=$langs->trans("ShowOrder").': '.$this->ref;

        if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
        if ($withpicto && $withpicto != 2) $result.=' ';
        $result.=$lien.$this->ref.$lienfin;
        return $result;
    }


    /**
     *  Renvoie la reference de commande suivante non utilisee en fonction du modele
     *                  de numerotation actif defini dans COMMANDE_SUPPLIER_ADDON
     *
     *  @param	    Societe		$soc  		objet societe
     *  @return     string                  reference libre pour la facture
     */
    function getNextNumRef($soc)
    {
        global $db, $langs, $conf;
        $langs->load("orders");

        $dir = DOL_DOCUMENT_ROOT .'/core/modules/supplier_order/';

        if (! empty($conf->global->COMMANDE_SUPPLIER_ADDON))
        {
            $file = $conf->global->COMMANDE_SUPPLIER_ADDON.'.php';

            if (is_readable($dir.'/'.$file))
            {
                // Definition du nom de modele de numerotation de commande fournisseur
                $modName=$conf->global->COMMANDE_SUPPLIER_ADDON;
                require_once($dir.'/'.$file);

                // Recuperation de la nouvelle reference
                $objMod = new $modName($this->db);

                $numref = "";
                $numref = $objMod->commande_get_num($soc,$this);

                if ( $numref != "")
                {
                    return $numref;
                }
                else
                {
                    dol_print_error($db,"CommandeFournisseur::getNextNumRef ".$obj->error);
                    return -1;
                }
            }
            else
            {
                print $langs->trans("Error")." ".$langs->trans("Error_FailedToLoad_COMMANDE_SUPPLIER_ADDON_File",$conf->global->COMMANDE_SUPPLIER_ADDON);
                return -2;
            }
        }
        else
        {
            print $langs->trans("Error")." ".$langs->trans("Error_COMMANDE_SUPPLIER_ADDON_NotDefined");
            return -3;
        }
    }

    /**
     * 	Accept an order
     *
     *	@param	User	$user			Object user
     *	@param	int		$idwarehouse	Id of warhouse for stock change
     *	@return	int						<0 if KO, >0 if OK
     */
    function approve($user, $idwarehouse=0)
    {
        global $langs,$conf;

        $error=0;

        dol_syslog(get_class($this)."::Approve");

        if ($user->rights->fournisseur->commande->approuver)
        {
            $this->db->begin();

            $sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur SET fk_statut = 2";
            $sql .= " WHERE rowid = ".$this->id." AND fk_statut = 1 ;";

            if ($this->db->query($sql))
            {
                $this->log($user, 2, time());	// Statut 2

                // If stock is incremented on validate order, we must increment it
                if (! $error && $conf->stock->enabled && $conf->global->STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER)
                {
                    require_once(DOL_DOCUMENT_ROOT."/product/stock/class/mouvementstock.class.php");
                    $langs->load("agenda");

                    $cpt=count($this->lines);
                    for ($i = 0; $i < $cpt; $i++)
                    {
                        // Product with reference
                        if ($this->lines[$i]->fk_product > 0)
                        {
                            $mouvP = new MouvementStock($this->db);
                            // We decrement stock of product (and sub-products)
                            $result=$mouvP->reception($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, $this->lines[$i]->subprice, $langs->trans("OrderApprovedInDolibarr",$this->ref));
                            if ($result < 0) { $error++; }
                        }
                    }
                }

                if (! $error)
                {
                    // Appel des triggers
                    include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                    $interface=new Interfaces($this->db);
                    $result=$interface->run_triggers('ORDER_SUPPLIER_APPROVE',$this,$user,$langs,$conf);
                    if ($result < 0) { $error++; $this->errors=$interface->errors; }
                    // Fin appel triggers
                }

                if (! $error)
                {
                    $this->db->commit();
                    return 1;
                }
                else
                {
                    $this->db->rollback();
                    $this->error=$this->db->lasterror();
                    return -1;
                }
            }
            else
            {
                $this->db->rollback();
                $this->error=$this->db->lasterror();
                dol_syslog("CommandeFournisseur::Approve Error ",$this->error, LOG_ERR);
                return -1;
            }
        }
        else
        {
            dol_syslog("CommandeFournisseur::Approve Not Authorized", LOG_ERR);
        }
        return -1;
    }

    /**
     * 	Refuse an order
     *
     * 	@param		User	$user		User making action
     *	@return		int					0 if Ok, <0 if Ko
     */
    function refuse($user)
    {
        global $conf, $langs;

		$error=0;

        dol_syslog("CommandeFournisseur::Refuse");
        $result = 0;
        if ($user->rights->fournisseur->commande->approuver)
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur SET fk_statut = 9";
            $sql .= " WHERE rowid = ".$this->id;

            if ($this->db->query($sql))
            {
                $result = 0;
                $this->log($user, 9, time());

                if ($error == 0)
                {
                    // Appel des triggers
                    include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                    $interface=new Interfaces($this->db);
                    $result=$interface->run_triggers('ORDER_SUPPLIER_REFUSE',$this,$user,$langs,$conf);
                    if ($result < 0) { $error++; $this->errors=$interface->errors; }
                    // Fin appel triggers
                }
            }
            else
            {
                dol_syslog("CommandeFournisseur::Refuse Error -1");
                $result = -1;
            }
        }
        else
        {
            dol_syslog("CommandeFournisseur::Refuse Not Authorized");
        }
        return $result ;
    }

    /**
     * 	Cancel an approved order.
     *	L'annulation se fait apres l'approbation
     *
     * 	@param		User	$user		User making action
     * 	@return		int					>0 if Ok, <0 if Ko
     */
    function Cancel($user)
    {
        global $langs,$conf;

		$error=0;

        //dol_syslog("CommandeFournisseur::Cancel");
        $result = 0;
        if ($user->rights->fournisseur->commande->commander)
        {
            $statut = 6;

            $this->db->begin();

            $sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur SET fk_statut = ".$statut;
            $sql .= " WHERE rowid = ".$this->id;
            dol_syslog("CommandeFournisseur::Cancel sql=".$sql);
            if ($this->db->query($sql))
            {
                $result = 0;
                $this->log($user, $statut, time());

                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface=new Interfaces($this->db);
                $result=$interface->run_triggers('ORDER_SUPPLIER_CANCEL',$this,$user,$langs,$conf);
                if ($result < 0) { $error++; $this->errors=$interface->errors; }
                // Fin appel triggers

                if ($error == 0)
                {
                    $this->db->commit();
                    return 1;
                }
                else
                {
                    $this->db->rollback();
                    $this->error=$this->db->lasterror();
                    return -1;
                }
            }
            else
            {
                $this->db->rollback();
                $this->error=$this->db->lasterror();
                dol_syslog("CommandeFournisseur::Cancel ".$this->error);
                return -1;
            }
        }
        else
        {
            dol_syslog("CommandeFournisseur::Cancel Not Authorized");
            return -1;
        }
    }


    /**
     * 	Send a supplier order to supplier
     *
     * 	@param		User	$user		User making change
     * 	@param		date	$date		Date
     * 	@param		int		$methode	Method
     * 	@param		string	$comment	Comment
     * 	@return		int			<0 if KO, >0 if OK
     */
    function commande($user, $date, $methode, $comment='')
    {
        dol_syslog("CommandeFournisseur::Commande");
        $result = 0;
        if ($user->rights->fournisseur->commande->commander)
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur SET fk_statut = 3, fk_methode_commande=".$methode.",date_commande=".$this->db->idate("$date");
            $sql .= " WHERE rowid = ".$this->id;

            dol_syslog("CommandeFournisseur::Commande sql=".$sql, LOG_DEBUG);
            if ($this->db->query($sql))
            {
                $result = 0;
                $this->log($user, 3, $date, $comment);
            }
            else
            {
                dol_syslog("CommandeFournisseur::Commande Error -1", LOG_ERR);
                $result = -1;
            }
        }
        else
        {
            dol_syslog("CommandeFournisseur::Commande User not Authorized", LOG_ERR);
        }
        return $result ;
    }

    /**
     *      Create order with draft status
     *
     *      @param      User	$user       User making creation
     *      @return     int         		<0 if KO, Id of supplier order if OK
     */
    function create($user)
    {
        global $langs,$conf;

        $this->db->begin();

		$error=0;
        $now=dol_now();

        /* On positionne en mode brouillon la commande */
        $this->brouillon = 1;

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."commande_fournisseur (";
        $sql.= "ref";
        $sql.= ", entity";
        $sql.= ", fk_soc";
        $sql.= ", date_creation";
        $sql.= ", fk_user_author";
        $sql.= ", fk_statut";
        $sql.= ", source";
        $sql.= ", model_pdf";
        //$sql.= ", fk_mode_reglement";
        $sql.= ") ";
        $sql.= " VALUES (";
        $sql.= "''";
        $sql.= ", ".$conf->entity;
        $sql.= ", ".$this->socid;
        $sql.= ", ".$this->db->idate($now);
        $sql.= ", ".$user->id;
        $sql.= ", 0";
        $sql.= ", 0";
        $sql.= ", '".$conf->global->COMMANDE_SUPPLIER_ADDON_PDF."'";
        //$sql.= ", ".$this->mode_reglement_id;
        $sql.= ")";

        dol_syslog(get_class($this)."::create sql=".$sql);
        if ($this->db->query($sql))
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."commande_fournisseur");

            $sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur";
            $sql.= " SET ref='(PROV".$this->id.")'";
            $sql.= " WHERE rowid=".$this->id;
            dol_syslog(get_class($this)."::create sql=".$sql);
            if ($this->db->query($sql))
            {
                // On logue creation pour historique
                $this->log($user, 0, time());

                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface=new Interfaces($this->db);
                $result=$interface->run_triggers('ORDER_SUPPLIER_CREATE',$this,$user,$langs,$conf);
                if ($result < 0) { $error++; $this->errors=$interface->errors; }
                // Fin appel triggers

                $this->db->commit();
                return $this->id;
            }
            else
            {
                $this->error=$this->db->error();
                dol_syslog(get_class($this)."::create: Failed -2 - ".$this->error, LOG_ERR);
                $this->db->rollback();
                return -2;
            }
        }
        else
        {
            $this->error=$this->db->error();
            dol_syslog(get_class($this)."::create: Failed -1 - ".$this->error, LOG_ERR);
            $this->db->rollback();
            return -1;
        }
    }

    /**
     *	Add order line
     *
     *	@param      string	$desc            		Description
     *	@param      double	$pu_ht              	Unit price
     *	@param      double	$qty             		Quantity
     *	@param      double	$txtva           		Taux tva
     *	@param      double	$txlocaltax1        	Localtax1 tax
     *  @param      double	$txlocaltax2        	Localtax2 tax
     *	@param      int		$fk_product      		Id produit
     *  @param      int		$fk_prod_fourn_price	Id supplier price
     *  @param      string	$fourn_ref				Supplier reference
     *	@param      double	$remise_percent  		Remise
     *	@param      string	$price_base_type		HT or TTC
     *	@param		double	$pu_ttc					Unit price TTC
     *	@param		int		$type					Type of line (0=product, 1=service)
     *	@param		int		$info_bits				More information
     *	@return     int             				<=0 if KO, >0 if OK
     */
    function addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1=0, $txlocaltax2=0, $fk_product=0, $fk_prod_fourn_price=0, $fourn_ref='', $remise_percent=0, $price_base_type='HT', $pu_ttc=0, $type=0, $info_bits=0)
    {
        global $langs,$mysoc;

        dol_syslog("FournisseurCommande::addline $desc, $pu_ht, $qty, $txtva, $txlocaltax1, $txlocaltax2. $fk_product, $fk_prod_fourn_price, $fourn_ref, $remise_percent, $price_base_type, $pu_ttc, $type");
        include_once(DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php');

        // Clean parameters
        if (! $qty) $qty=1;
        if (! $info_bits) $info_bits=0;
        if (empty($txtva)) $txtva=0;
        if (empty($txlocaltax1)) $txlocaltax1=0;
        if (empty($txlocaltax2)) $txlocaltax2=0;

        $remise_percent=price2num($remise_percent);
        $qty=price2num($qty);
        $pu_ht=price2num($pu_ht);
        $pu_ttc=price2num($pu_ttc);
        $txtva = price2num($txtva);
        $txlocaltax1 = price2num($txlocaltax1);
        $txlocaltax2 = price2num($txlocaltax2);
        if ($price_base_type=='HT')
        {
            $pu=$pu_ht;
        }
        else
        {
            $pu=$pu_ttc;
        }
        $desc=trim($desc);


        // Check parameters
        if ($qty < 1 && ! $fk_product)
        {
            $this->error=$langs->trans("ErrorFieldRequired",$langs->trans("Product"));
            return -1;
        }
        if ($type < 0) return -1;


        if ($this->statut == 0)
        {
            $this->db->begin();

            if ($fk_product > 0)
            {
                $prod = new Product($this->db, $fk_product);
                if ($prod->fetch($fk_product) > 0)
                {
                    $result=$prod->get_buyprice($fk_prod_fourn_price,$qty,$fk_product,$fourn_ref);
                    if ($result > 0)
                    {
                        $label = $prod->libelle;
                        $pu    = $prod->fourn_pu;
                        $ref   = $prod->ref_fourn;
                        $product_type = $prod->type;
                    }
                    if ($result == 0 || $result == -1)
                    {
                        $this->error="No price found for this quantity. Quantity may be too low ?";
                        $this->db->rollback();
                        dol_syslog("FournisseurCommande::addline result=".$result." - ".$this->error, LOG_DEBUG);
                        return -1;
                    }
                    if ($result < -1)
                    {
                        $this->error=$prod->error;
                        $this->db->rollback();
                        dol_syslog("Fournisseur.commande::addline result=".$result." - ".$this->error, LOG_ERR);
                        return -1;
                    }
                }
                else
                {
                    $this->error=$this->db->error();
                    return -1;
                }
            }
            else
            {
                $product_type = $type;
            }

            // Calcul du total TTC et de la TVA pour la ligne a partir de
            // qty, pu, remise_percent et txtva
            // TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
            // la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.
            $tabprice = calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits);
            $total_ht  = $tabprice[0];
            $total_tva = $tabprice[1];
            $total_ttc = $tabprice[2];
            $total_localtax1 = $tabprice[9];
            $total_localtax2 = $tabprice[10];

            $subprice = price2num($pu,'MU');

            // TODO A virer
            // Anciens indicateurs: $price, $remise (a ne plus utiliser)
            $remise = 0;
            if ($remise_percent > 0)
            {
                $remise = round(($pu * $remise_percent / 100), 2);
            }

            $sql = "INSERT INTO ".MAIN_DB_PREFIX."commande_fournisseurdet";
            $sql.= " (fk_commande,label, description,";
            $sql.= " fk_product, product_type,";
            $sql.= " qty, tva_tx, localtax1_tx, localtax2_tx, remise_percent, subprice, remise, ref,";
            $sql.= " total_ht, total_tva, total_localtax1, total_localtax2, total_ttc";
            $sql.= ")";
            $sql.= " VALUES (".$this->id.", '" . $this->db->escape($label) . "','" . $this->db->escape($desc) . "',";
            if ($fk_product) { $sql.= $fk_product.","; }
            else { $sql.= "null,"; }
            $sql.= "'".$product_type."',";
            $sql.= "'".$qty."', ".$txtva.", ".$txlocaltax1.", ".$txlocaltax2.", ".$remise_percent.",'".price2num($subprice,'MU')."','".price2num($remise)."','".$ref."',";
            $sql.= "'".price2num($total_ht)."',";
            $sql.= "'".price2num($total_tva)."',";
            $sql.= "'".price2num($total_localtax1)."',";
            $sql.= "'".price2num($total_localtax2)."',";
            $sql.= "'".price2num($total_ttc)."'";
            $sql.= ")";

            dol_syslog('FournisseurCommande::addline sql='.$sql);
            $resql=$this->db->query($sql);
            //print $sql;
            if ($resql)
            {
                $this->update_price();

                $this->db->commit();
                return 1;
            }
            else
            {
                $this->error=$this->db->error();
                $this->db->rollback();
                dol_syslog('FournisseurCommande::addline '.$this->error, LOG_ERR);
                return -1;
            }
        }
    }


    /**
     * Add a product into a stock warehouse.
     *
     * @param 	User		$user		User object making change
     * @param 	int			$product	Id of product to dispatch
     * @param 	double		$qty		Qty to dispatch
     * @param 	int			$entrepot	Id of warehouse to add product
     * @param 	double		$price		Price for PMP value calculation
     * @param	string		$comment	Comment for stock movement
     * @return 	int						<0 if KO, >0 if OK
     */
    function DispatchProduct($user, $product, $qty, $entrepot, $price=0, $comment='')
    {
        global $conf;
        $error = 0;
        require_once DOL_DOCUMENT_ROOT ."/product/stock/class/mouvementstock.class.php";

        // Check parameters
        if ($entrepot <= 0 || $qty <= 0)
        {
            $this->error='BadValueForParameter';
            return -1;
        }

        $now=dol_now();

        if (($this->statut == 3 || $this->statut == 4 || $this->statut == 5))
        {
            $this->db->begin();

            $sql = "INSERT INTO ".MAIN_DB_PREFIX."commande_fournisseur_dispatch ";
            $sql.= " (fk_commande,fk_product, qty, fk_entrepot, fk_user, datec) VALUES ";
            $sql.= " ('".$this->id."','".$product."','".$qty."',".($entrepot>0?"'".$entrepot."'":"null").",'".$user->id."','".$this->db->idate($now)."')";

            dol_syslog("CommandeFournisseur::DispatchProduct sql=".$sql);
            $resql = $this->db->query($sql);
            if (! $resql)
            {
                $this->error=$this->db->lasterror();
                $error++;
            }

            // Si module stock gere et que incrementation faite depuis un dispatching en stock
            if (!$error && $entrepot > 0 && $conf->stock->enabled && $conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER)
            {
                $mouv = new MouvementStock($this->db);
                if ($product > 0)
                {
                    $result=$mouv->reception($user, $product, $entrepot, $qty, $price, $comment);
                    if ($result < 0)
                    {
                        $this->error=$mouv->error;
                        dol_syslog("CommandeFournisseur::DispatchProduct ".$this->error, LOG_ERR);
                        $error++;
                    }
                }
            }

            if ($error == 0)
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
        else
        {
            $this->error='BadStatusForObject';
            return -2;
        }
    }

    /**
     * 	Delete line
     *
     *	@param	int		$idligne	Id of line to delete
     *	@return						0 if Ok, <0 ik Ko
     */
    function deleteline($idligne)
    {
        if ($this->statut == 0)
        {
            $sql = "DELETE FROM ".MAIN_DB_PREFIX."commande_fournisseurdet WHERE rowid = ".$idligne;
            $resql=$this->db->query($sql);

            dol_syslog(get_class($this)."::deleteline sql=".$sql);
            if ($resql)
            {
                $result=$this->update_price();
                return 0;
            }
            else
            {
                $this->error=$this->db->error();
                return -1;
            }
        }
        else
        {
            return -1;
        }
    }

    /**
     *  Delete an order
     *
     *	@param	User	$user		Object user
     *	@return	int					<0 if KO, >0 if OK
     */
    function delete($user='')
    {
        global $langs,$conf;

        $error = 0;

        $this->db->begin();

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."commande_fournisseurdet WHERE fk_commande =". $this->id ;
        dol_syslog("FournisseurCommande::delete sql=".$sql, LOG_DEBUG);
        if (! $this->db->query($sql) )
        {
            $error++;
        }

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."commande_fournisseur WHERE rowid =".$this->id;
        dol_syslog("FournisseurCommande::delete sql=".$sql, LOG_DEBUG);
        if ($resql = $this->db->query($sql) )
        {
            if ($this->db->affected_rows($resql) < 1)
            {
                $error++;
            }
        }
        else
        {
            $error++;
        }

        if ($error == 0)
        {
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('ORDER_SUPPLIER_DELETE',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            // Fin appel triggers

            dol_syslog("CommandeFournisseur::delete : Success");
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
     *	Get list of order methods
     *
     *	@return 0 if Ok, <0 if Ko
     */
    function get_methodes_commande()
    {
        $sql = "SELECT rowid, libelle";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_input_method";
        $sql.= " WHERE active = 1";

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $i = 0;
            $num = $this->db->num_rows($resql);
            $this->methodes_commande = array();
            while ($i < $num)
            {
                $row = $this->db->fetch_row($resql);

                $this->methodes_commande[$row[0]] = $row[1];

                $i++;
            }
            return 0;
        }
        else
        {
            return -1;
        }
    }

    /**
     *  Change les conditions de reglement de la commande
     *
     *  @param      int		$cond_reglement_id      Id de la nouvelle condition de reglement
     *  @return     int                    			>0 si ok, <0 si ko
     */
    function cond_reglement($cond_reglement_id)
    {
        dol_syslog('CommandeFournisseur::cond_reglement('.$cond_reglement_id.')');
        if ($this->statut >= 0)
        {
            $sql = 'UPDATE '.MAIN_DB_PREFIX.'commande_fournisseur';
            $sql .= ' SET fk_cond_reglement = '.$cond_reglement_id;
            $sql .= ' WHERE rowid='.$this->id;
            if ( $this->db->query($sql) )
            {
                $this->cond_reglement_id = $cond_reglement_id;
                return 1;
            }
            else
            {
                dol_syslog('CommandeFournisseur::cond_reglement Erreur '.$sql.' - '.$this->db->error(), LOG_ERR);
                $this->error=$this->db->lasterror();
                return -1;
            }
        }
        else
        {
            dol_syslog('CommandeFournisseur::cond_reglement, etat commande incompatible', LOG_ERR);
            $this->error='Etat commande incompatible '.$this->statut;
            return -2;
        }
    }

    /**
     *  Change le mode de reglement
     *
     *  @param      int		$mode_reglement_id      Id du nouveau mode
     *  @return     int         					>0 if OK, <0 if KO
     */
    function mode_reglement($mode_reglement_id)
    {
        dol_syslog('CommandeFournisseur::mode_reglement('.$mode_reglement_id.')');
        if ($this->statut >= 0)
        {
            $sql = 'UPDATE '.MAIN_DB_PREFIX.'commande_fournisseur';
            $sql .= ' SET fk_mode_reglement = '.$mode_reglement_id;
            $sql .= ' WHERE rowid='.$this->id;
            if ( $this->db->query($sql) )
            {
                $this->mode_reglement_id = $mode_reglement_id;
                return 1;
            }
            else
            {
                dol_syslog('CommandeFournisseur::mode_reglement Erreur '.$sql.' - '.$this->db->error(), LOG_ERR);
                $this->error=$this->db->lasterror();
                return -1;
            }
        }
        else
        {
            dol_syslog('CommandeFournisseur::mode_reglement, etat commande incompatible', LOG_ERR);
            $this->error='Etat commande incompatible '.$this->statut;
            return -2;
        }
    }

    /**
     * 	Set a delivery in database for this supplier order
     *
     *	@param	User	$user		User that input data
     *	@param	date	$date		Date of reception
     *	@param	string	$type		Type of receipt
     *	@param	string	$comment	Comment
     *	@return	int					<0 if KO, >0 if OK
     */
    function Livraison($user, $date, $type, $comment)
    {
        $result = 0;

        dol_syslog("CommandeFournisseur::Livraison");

        if ($user->rights->fournisseur->commande->receptionner)
        {
            if ($type == 'par') $statut = 4;
            if ($type == 'tot')	$statut = 5;
            if ($type == 'nev') $statut = 7;
            if ($type == 'can') $statut = 7;

            if ($statut == 4 or $statut == 5 or $statut == 7)
            {
                $this->db->begin();

                $sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur";
                $sql.= " SET fk_statut = ".$statut;
                $sql.= " WHERE rowid = ".$this->id;
                $sql.= " AND fk_statut IN (3,4)";	// Process running or Partially received

                dol_syslog("CommandeFournisseur::Livraison sql=".$sql);
                $resql=$this->db->query($sql);
                if ($resql)
                {
                    $result = 0;
                    $result=$this->log($user, $statut, $date, $comment);

                    $this->db->commit();
                }
                else
                {
                    $this->db->rollback();
                    $this->error=$this->db->lasterror();
                    dol_syslog("CommandeFournisseur::Livraison Error ".$this->error, LOG_ERR);
                    $result = -1;
                }
            }
            else
            {
                dol_syslog("CommandeFournisseur::Livraison Error -2", LOG_ERR);
                $result = -2;
            }
        }
        else
        {
            dol_syslog("CommandeFournisseur::Livraison Not Authorized");
            $result = -3;
        }
        return $result ;
    }

    /**
     *  Update a supplier order from a customer order
     *
     *  @param  User	$user           User that create
     *  @param  int		$idc			Id of supplier order to update
     *  @param	int		$comclientid	Id of customer order to use as template
     *	@return	int						<0 if KO, >0 if OK
     */
    function updateFromCommandeClient($user, $idc, $comclientid)
    {
        $comclient = new Commande($this->db);
        $comclient->fetch($comclientid);

        $this->id = $idc;

        $this->lines = array();

        $num=count($comclient->lines);
        for ($i = 0; $i < $num; $i++)
        {
            $prod = new Product($this->db);
            if ($prod->fetch($comclient->lines[$i]->fk_product) > 0)
            {
                $libelle  = $prod->libelle;
                $ref      = $prod->ref;
            }

            $sql = "INSERT INTO ".MAIN_DB_PREFIX."commande_fournisseurdet";
            $sql .= " (fk_commande,label,description,fk_product, price, qty, tva_tx, localtax1_tx, localtax2_tx, remise_percent, subprice, remise, ref)";
            $sql .= " VALUES (".$idc.", '" . $this->db->escape($libelle) . "','" . $this->db->escape($comclient->lines[$i]->desc) . "'";
            $sql .= ",".$comclient->lines[$i]->fk_product.",'".price2num($comclient->lines[$i]->price)."'";
            $sql .= ", '".$comclient->lines[$i]->qty."', ".$comclient->lines[$i]->tva_tx.", ".$comclient->lines[$i]->localtax1_tx.", ".$comclient->lines[$i]->localtax2_tx.", ".$comclient->lines[$i]->remise_percent;
            $sql .= ", '".price2num($comclient->lines[$i]->subprice)."','0','".$ref."');";
            if ($this->db->query($sql))
            {
                $this->update_price();
            }
        }

        return 1;
    }


    /**
     *	Update notes
     *
     *	@param		User		$user			Object user
     *	@param		string		$note			Private note
     *	@param		string		$note_public	Public note
     *	@return		int							<0 if KO, >=0 if OK
     *
     *	TODO Use instead update_note_public and update_note
     */
    function UpdateNote($user, $note, $note_public)
    {
        // Clean parameters
        $note=trim($note);
        $note_public=trim($note_public);

        $result = 0;

        $sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur";
        $sql.= " SET note  ='".$this->db->escape($note)."',";
        $sql.= " note_public  ='".$this->db->escape($note_public)."'";
        $sql.= " WHERE rowid = ".$this->id;

        dol_syslog(get_class($this)."::UpdateNote sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $result = 0;
        }
        else
        {
            $this->error=$this->db->error();
            dol_syslog(get_class($this)."::UpdateNote ".$this->error, LOG_ERR);
            $result = -1;
        }

        return $result ;
    }

    /**
     *  Tag order with a particular status
     *
     *  @param      User	$user       Object user that change status
     *  @param      int		$status		New status
     *  @return     int         		<0 if KO, >0 if OK
     */
    function setStatus($user,$status)
    {
        global $conf,$langs;
        $error=0;

        $this->db->begin();

        $sql = 'UPDATE '.MAIN_DB_PREFIX.'commande_fournisseur';
        $sql.= ' SET fk_statut='.$status;
        $sql.= ' WHERE rowid = '.$this->id;

        dol_syslog("CommandeFournisseur::setStatus sql=".$sql);
        $resql = $this->db->query($sql);
        if ($resql)
        {

        }
        else
        {
            $error++;
            $this->error=$this->db->lasterror();
            dol_syslog("CommandeFournisseur::setStatus ".$this->error);
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
     *	Update line
     *
     *	@param     	int		$rowid           	Id de la ligne de facture
     *	@param     	string	$desc            	Description de la ligne
     *	@param     	double	$pu              	Prix unitaire
     *	@param     	double	$qty             	Quantity
     *	@param     	double	$remise_percent  	Pourcentage de remise de la ligne
     *	@param     	double	$txtva          	Taux TVA
     *  @param     	double	$txlocaltax1	    Localtax1 tax
     *  @param     	double	$txlocaltax2   		Localtax2 tax
     *  @param     	double	$price_base_type 	Type of price base
     *	@param		int		$info_bits			Miscellanous informations
     *	@param		int		$type				Type of line (0=product, 1=service)
     *	@return    	int             			< 0 if error, > 0 if ok
     */
    function updateline($rowid, $desc, $pu, $qty, $remise_percent, $txtva, $txlocaltax1=0, $txlocaltax2=0, $price_base_type='HT', $info_bits=0, $type=0)
    {
        dol_syslog("CommandeFournisseur::UpdateLine $rowid, $desc, $pu, $qty, $remise_percent, $txtva, $price_base_type, $info_bits, $type");
        include_once(DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php');

        if ($this->brouillon)
        {
            $this->db->begin();

            // Clean parameters
            if (empty($qty)) $qty=0;
            if (empty($info_bits)) $info_bits=0;
            if (empty($txtva)) $txtva=0;
            if (empty($txlocaltax1)) $txlocaltax1=0;
            if (empty($txlocaltax2)) $txlocaltax2=0;
            if (empty($remise)) $remise=0;
            if (empty($remise_percent)) $remise_percent=0;

            $remise_percent=price2num($remise_percent);
            $qty=price2num($qty);
            if (! $qty) $qty=1;
            $pu = price2num($pu);
            $txtva=price2num($txtva);
            $txlocaltax1=price2num($txlocaltax1);
            $txlocaltax2=price2num($txlocaltax2);

            // Check parameters
            if ($type < 0) return -1;

            // Calcul du total TTC et de la TVA pour la ligne a partir de
            // qty, pu, remise_percent et txtva
            // TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
            // la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.
            $tabprice=calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits);
            $total_ht  = $tabprice[0];
            $total_tva = $tabprice[1];
            $total_ttc = $tabprice[2];
            $total_localtax1 = $tabprice[9];
            $total_localtax2 = $tabprice[10];

            // Anciens indicateurs: $price, $subprice, $remise (a ne plus utiliser)
            $subprice = $pu;
            $remise = 0;
            if ($remise_percent > 0)
            {
                $remise = round(($pu * $remise_percent / 100),2);
            }
            $subprice  = price2num($subprice);

            // Mise a jour ligne en base
            $sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseurdet SET";
            $sql.= " description='".$this->db->escape($desc)."'";
            $sql.= ",subprice='".price2num($subprice)."'";
            $sql.= ",remise='".price2num($remise)."'";
            $sql.= ",remise_percent='".price2num($remise_percent)."'";
            $sql.= ",tva_tx='".price2num($txtva)."'";
            $sql.= ",localtax1_tx='".price2num($txlocaltax1)."'";
            $sql.= ",localtax2_tx='".price2num($txlocaltax2)."'";
            $sql.= ",qty='".price2num($qty)."'";
            /*if ($date_end) { $sql.= ",date_start='$date_end'"; }
            else { $sql.=',date_start=null'; }
            if ($date_end) { $sql.= ",date_end='$date_end'"; }
            else { $sql.=',date_end=null'; }*/
            $sql.= ",info_bits='".$info_bits."'";
            $sql.= ",total_ht='".price2num($total_ht)."'";
            $sql.= ",total_tva='".price2num($total_tva)."'";
            $sql.= ",total_localtax1='".price2num($total_localtax1)."'";
            $sql.= ",total_localtax2='".price2num($total_localtax2)."'";
            $sql.= ",total_ttc='".price2num($total_ttc)."'";
            $sql.= ",product_type='".$type."'";
            $sql.= " WHERE rowid = ".$rowid;

            dol_syslog("CommandeFournisseur::updateline sql=".$sql);
            $result = $this->db->query($sql);
            if ($result > 0)
            {
                // Mise a jour info denormalisees au niveau facture
                $this->update_price();

                $this->db->commit();
                return $result;
            }
            else
            {
                $this->error=$this->db->error();
                dol_syslog("CommandeFournisseur::updateline ".$this->error, LOG_ERR);
                $this->db->rollback();
                return -1;
            }
        }
        else
        {
            $this->error="Order status makes operation forbidden";
            dol_syslog("CommandeFournisseur::updateline ".$this->error, LOG_ERR);
            return -2;
        }
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
        global $user,$langs,$conf;

        dol_syslog("CommandeFournisseur::initAsSpecimen");

        $now=dol_now();

        // Charge tableau des produits prodids
        $prodids = array();

        $sql = "SELECT rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX."product";
        $sql.= " WHERE entity IN (".getEntity('product', 1).")";

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num_prods = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num_prods)
            {
                $i++;
                $row = $this->db->fetch_row($resql);
                $prodids[$i] = $row[0];
            }
        }

        // Initialise parametres
        $this->id=0;
        $this->ref = 'SPECIMEN';
        $this->specimen=1;
        $this->socid = 1;
        $this->date = $now;
        $this->date_commande = $now;
        $this->date_lim_reglement=$this->date+3600*24*30;
        $this->cond_reglement_code = 'RECEP';
        $this->mode_reglement_code = 'CHQ';
        $this->note_public='This is a comment (public)';
        $this->note='This is a comment (private)';
        // Lines
        $nbp = 5;
        $xnbp = 0;
        while ($xnbp < $nbp)
        {
            $line=new CommandeFournisseurLigne($this->db);
            $line->desc=$langs->trans("Description")." ".$xnbp;
            $line->qty=1;
            $line->subprice=100;
            $line->price=100;
            $line->tva_tx=19.6;
            $line->localtax1_tx=0;
            $line->localtax2_tx=0;
            if ($xnbp == 2)
            {
                $line->total_ht=50;
                $line->total_ttc=59.8;
                $line->total_tva=9.8;
                $line->remise_percent=50;
            }
            else
            {
                $line->total_ht=100;
                $line->total_ttc=119.6;
                $line->total_tva=19.6;
                $line->remise_percent=00;
            }
            $line->ref_fourn='SUPPLIER_REF_'.$xnbp;
            $prodid = rand(1, $num_prods);
            $line->fk_product=$prodids[$prodid];

            $this->lines[$xnbp]=$line;

            $this->total_ht       += $line->total_ht;
            $this->total_tva      += $line->total_tva;
            $this->total_ttc      += $line->total_ttc;

            $xnbp++;
        }
    }

    /**
     *	Load indicators for dashboard (this->nbtodo and this->nbtodolate)
     *
     *	@param          User	$user   Objet user
     *	@return         int    			<0 if KO, >0 if OK
     */
    function load_board($user)
    {
        global $conf, $user;

        $now=gmmktime();

        $this->nbtodo=$this->nbtodolate=0;
        $clause = " WHERE";

        $sql = "SELECT c.rowid, c.date_creation as datec, c.fk_statut";
        $sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as c";
        if (!$user->rights->societe->client->voir && !$user->societe_id)
        {
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON c.fk_soc = sc.fk_soc";
            $sql.= " WHERE sc.fk_user = " .$user->id;
            $clause = " AND";
        }
        $sql.= $clause." c.entity = ".$conf->entity;
        $sql.= " AND (c.fk_statut BETWEEN 1 AND 2)";
        if ($user->societe_id) $sql.=" AND c.fk_soc = ".$user->societe_id;

        $resql=$this->db->query($sql);
        if ($resql)
        {
            while ($obj=$this->db->fetch_object($resql))
            {
                $this->nbtodo++;
                if ($obj->fk_statut != 3 && $this->db->jdate($obj->datec) < ($now - $conf->commande->fournisseur->warning_delay)) $this->nbtodolate++;
            }
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }
}



/**
 *  \class      CommandeFournisseurLigne
 *  \brief      Classe de gestion des lignes de commande
 */
class CommandeFournisseurLigne extends OrderLine
{
    // From llx_commandedet
    var $qty;
    var $tva_tx;
    var $localtax1_tx;
    var $localtax2_tx;
    var $subprice;
    var $remise_percent;
    var $desc;          	// Description ligne
    var $fk_product;		// Id of predefined product
    var $product_type = 0;	// Type 0 = product, 1 = Service
    var $total_ht;
    var $total_tva;
    var $total_localtax1;
    var $total_localtax2;
    var $total_ttc;

    // From llx_product
    var $libelle;       // Label produit
    var $product_desc;  // Description produit

    // From llx_product_fournisseur_price
    var $ref_fourn;     // Ref supplier


    /**
     *	Constructor
     *
     *  @param		DoliDB		$db      Database handler
     */
    function CommandeFournisseurLigne($db)
    {
        $this->db= $db;
    }

    /**
     *  Load line order
     *
     *  @param  int		$rowid      Id line order
     *	@return	int					<0 if KO, >0 if OK
     */
    function fetch($rowid)
    {
        $sql = 'SELECT cd.rowid, cd.fk_commande, cd.fk_product, cd.product_type, cd.description, cd.qty, cd.tva_tx,';
        $sql.= ' cd.localtax1_tx, cd.localtax2_tx,';
        $sql.= ' cd.remise, cd.remise_percent, cd.subprice,';
        $sql.= ' cd.info_bits, cd.total_ht, cd.total_tva, cd.total_ttc,';
        $sql.= ' cd.total_localtax1, cd.local_localtax2,';
        $sql.= ' p.ref as product_ref, p.label as product_libelle, p.description as product_desc';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'commande_fournisseurdet as cd';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON cd.fk_product = p.rowid';
        $sql.= ' WHERE cd.rowid = '.$rowid;
        $result = $this->db->query($sql);
        if ($result)
        {
            $objp = $this->db->fetch_object($result);
            $this->rowid            = $objp->rowid;
            $this->fk_commande      = $objp->fk_commande;
            $this->desc             = $objp->description;
            $this->qty              = $objp->qty;
            $this->subprice         = $objp->subprice;
            $this->tva_tx           = $objp->tva_tx;
            $this->localtax1_tx		= $objp->localtax1_tx;
            $this->localtax2_tx		= $objp->localtax2_tx;
            $this->remise           = $objp->remise;
            $this->remise_percent   = $objp->remise_percent;
            $this->fk_product       = $objp->fk_product;
            $this->info_bits        = $objp->info_bits;
            $this->total_ht         = $objp->total_ht;
            $this->total_tva        = $objp->total_tva;
            $this->total_localtax1	= $objp->total_localtax1;
            $this->total_localtax2	= $objp->total_localtax2;
            $this->total_ttc        = $objp->total_ttc;
            $this->product_type     = $objp->product_type;

            $this->ref	            = $objp->product_ref;
            $this->product_libelle  = $objp->product_libelle;
            $this->product_desc     = $objp->product_desc;

            $this->db->free($result);
        }
        else
        {
            dol_print_error($this->db);
        }
    }

    /**
     *  Mise a jour de l'objet ligne de commande en base
     *
     *  @return		int		<0 si ko, >0 si ok
     */
    function update_total()
    {
        $this->db->begin();

        // Mise a jour ligne en base
        $sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseurdet SET";
        $sql.= " total_ht='".price2num($this->total_ht)."'";
        $sql.= ",total_tva='".price2num($this->total_tva)."'";
        $sql.= ",total_localtax1='".price2num($this->total_localtax1)."'";
        $sql.= ",total_localtax2='".price2num($this->total_localtax2)."'";
        $sql.= ",total_ttc='".price2num($this->total_ttc)."'";
        $sql.= " WHERE rowid = ".$this->rowid;

        dol_syslog("CommandeFournisseurLigne.class.php::update_total sql=$sql");

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->db->commit();
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            dol_syslog("CommandeFournisseurLigne.class.php::update_total Error ".$this->error, LOG_ERR);
            $this->db->rollback();
            return -2;
        }
    }
}

?>
