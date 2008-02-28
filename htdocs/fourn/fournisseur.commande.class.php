<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerke@telenet.be>
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
   \file       htdocs/fourn/fournisseur.commande.class.php
   \ingroup    fournisseur,commande
   \brief      Fichier des classes des commandes fournisseurs
   \version    $Id$
*/

require_once(DOL_DOCUMENT_ROOT."/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/commande/commande.class.php");


/**	  
	 \class      CommandeFournisseur
	 \brief      Classe de gestion de commande fournisseur
*/

class CommandeFournisseur extends Commande
{
  var $db ;
  var $error;
  var $element='order_supplier';
  var $table_element='commande_fournisseur';
  
  var $id ;
  var $brouillon;

  /**   \brief      Constructeur
   *    \param      DB      Handler d'accès aux bases de données
   */
  function CommandeFournisseur($DB)
    {
      $this->db = $DB;

      global $langs;
      $langs->load('orders');

      $this->statuts[0] = $langs->trans('StatusOrderDraft');
      $this->statuts[1] = $langs->trans('StatusOrderValidated');
      $this->statuts[2] = $langs->trans('StatusOrderApproved');
      $this->statuts[3] = $langs->trans('StatusOrderOnProcess');
      $this->statuts[4] = $langs->trans('StatusOrderReceivedPartially');
      $this->statuts[5] = $langs->trans('StatusOrderReceivedAll');
      $this->statuts[6] = $langs->trans('StatusOrderCanceled');
      $this->statuts[9] = $langs->trans('StatusOrderRefused');

      $this->products = array();
    }


    /**
     * Lit une commande
     */
	function fetch($id)
	{
		$sql = "SELECT c.rowid, c.date_creation, c.ref, c.fk_soc, c.fk_user_author, c.fk_statut, c.amount_ht, c.total_ht, c.total_ttc, c.tva,";
		$sql .= " ".$this->db->pdate("c.date_commande")." as date_commande, c.fk_projet, c.remise_percent, c.source, c.fk_methode_commande,";
		$sql .= " c.note, c.note_public, c.model_pdf,";
		$sql .= " cm.libelle as methode_commande";
		$sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as c";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_methode_commande_fournisseur as cm ON cm.rowid = c.fk_methode_commande";
		$sql .= " WHERE c.rowid = ".$id;
		
		$resql = $this->db->query($sql) ;
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			
			$this->id                  = $obj->rowid;
			$this->ref                 = $obj->ref;
			$this->socid               = $obj->fk_soc;
			$this->fourn_id            = $obj->fk_soc;
			$this->statut              = $obj->fk_statut;
			$this->user_author_id      = $obj->fk_user_author;
			$this->total_ht            = $obj->total_ht;
			$this->total_tva           = $obj->tva;
			$this->total_ttc           = $obj->total_ttc;
			$this->date_commande       = $obj->date_commande; // date à laquelle la commande a été transmise
			$this->date                = $obj->date_creation;
			$this->remise_percent      = $obj->remise_percent;
			$this->methode_commande_id = $obj->fk_methode_commande;
			$this->methode_commande    = $obj->methode_commande;
			
			$this->source              = $obj->source;
			$this->facturee            = $obj->facture;
			$this->projet_id           = $obj->fk_projet;
			$this->note                = $obj->note;
			$this->note_public         = $obj->note_public;
			$this->modelpdf            = $obj->model_pdf;
			
			$this->db->free();
			
			if ($this->statut == 0) $this->brouillon = 1;
			
			// export pdf -----------
			
			$this->lignes = array();
			$sql = 'SELECT l.fk_product, l.description, l.price, l.qty, l.rowid, l.tva_tx, l.remise_percent, l.subprice';
			$sql.= ', p.label, p.description as product_desc, p.rowid as prodid';
			$sql.= ', pf.ref_fourn';
			$sql.= ' FROM '.MAIN_DB_PREFIX.'commande_fournisseurdet as l';
			
			//Todo: revoir le fonctionnement de la base produit fournisseurs
			
			$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_fournisseur as pf ON l.fk_product = pf.fk_product AND l.ref = pf.ref_fourn';
			$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON l.fk_product = p.rowid';
			$sql.= ' WHERE l.fk_commande = '.$this->id;
			$sql.= ' ORDER BY l.rowid';
			$result = $this->db->query($sql);
			if ($result)
			{
				$num = $this->db->num_rows($result);
				$i = 0;
				
				while ($i < $num)
				{
					$objp                  = $this->db->fetch_object($result);
					
					$ligne                 = new CommandeFournisseurLigne();
					
					$ligne->desc                = $objp->description;  // Description ligne
					$ligne->qty                 = $objp->qty;
					$ligne->tva_tx              = $objp->tva_tx;
					$ligne->subprice            = $objp->subprice;
					$ligne->remise_percent      = $objp->remise_percent;
					$ligne->price               = $objp->price;
					
					$ligne->fk_product          = $objp->fk_product;   // Id du produit
					$ligne->libelle             = $objp->label;        // Label produit
					$ligne->product_desc        = $objp->product_desc; // Description produit

					$ligne->ref_fourn           = $objp->ref_fourn;    // Reference supplier
					
					$this->lignes[$i]      = $ligne;
					//dolibarr_syslog("1 ".$ligne->desc);
					//dolibarr_syslog("2 ".$ligne->product_desc);
					$i++;
				}
				$this->db->free($result);
				
				return 0;
			}
			else
			{
				$this->error=$this->db->error()." sql=".$sql;
				dolibarr_syslog("CommandeFournisseur::Fetch ".$this->error);
				return -1;
			}
		}
		else
		{
			$this->error=$this->db->error()." sql=".$sql;
			dolibarr_syslog("CommandeFournisseur::Fetch ".$this->error);
			return -1;
		}			
	}
	
  /**
   *      \brief      Insère ligne de log
   *      \param      user        Utilisateur qui modifie la commande
   *      \param      statut      Statut de la commande
   *      \param      datelog     Date de modification
   *      \return     int         <0 si ko, >0 si ok
   */
  function log($user, $statut, $datelog)
  {
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."commande_fournisseur_log (datelog, fk_commande, fk_statut, fk_user)";
    $sql.= " VALUES (".$this->db->idate($datelog).",".$this->id.", $statut, ".$user->id.")";
        
    if ( $this->db->query($sql) )
      {
	return 1;
      }
    else
      {
	return -1;
      }
  }
  /**
   *		\brief		Valide la commande
   *		\param		user		Utilisateur qui valide
   */
  function valid($user)
  {
    dolibarr_syslog("CommandeFournisseur.class::Valid");
    $result = 0;
    if ($user->rights->fournisseur->commande->valider)
      {
	$this->db->begin();
			
	// Definition du nom de module de numerotation de commande
	$soc = new Societe($this->db);
	$soc->fetch($this->fourn_id);
	$num=$this->getNextNumRef($soc);

	// on vérifie si la commande est en numérotation provisoire
	$comref = substr($this->ref, 1, 4);
	if ($comref == 'PROV')
	  {
	    $num = $this->getNextNumRef($soc);
	  }
	else
	  {
	    $num = $this->ref;
	  }

	$sql = 'UPDATE '.MAIN_DB_PREFIX."commande_fournisseur SET ref='$num', fk_statut = 1, date_valid=now(), fk_user_valid=$user->id";
	$sql .= " WHERE rowid = $this->id AND fk_statut = 0";

	$resql=$this->db->query($sql);
	if ($resql)
	  {
	    $result = 1;
	    $this->log($user, 1, time());	// Statut 1
	    $this->ref = $num;

	    // Appel des triggers
	    include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
	    $interface=new Interfaces($this->db);
	    $result=$interface->run_triggers('ORDER_SUPPLIER_VALIDATE',$this,$user,$langs,$conf);
        if ($result < 0) { $error++; $this->errors=$interface->errors; }
	    // Fin appel triggers

	    dolibarr_syslog("CommandeFournisseur::valid Success");
	    $this->db->commit();
	    return 1;
	  }
	else
	  {
	    $this->error=$this->db->error();
	    dolibarr_syslog("CommandeFournisseur::valid ".$this->error);
	    $this->db->rollback();
	    return -1;
	  }
      }
    else
      {
	$this->error='Not Authorized';
	dolibarr_syslog("CommandeFournisseur::valid ".$this->error);
	return -1;
      }
  }

  /**
   * 		\brief		Annule la commande
   * 		\param		user		Utilisateur qui demande annulation
   *		\remarks	L'annulation se fait après la validation
   */
  function Cancel($user)
  {
    //dolibarr_syslog("CommandeFournisseur::Cancel");
    $result = 0;
    if ($user->rights->fournisseur->commande->annuler)
      {
	$statut = 6;
	
	$sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur SET fk_statut = ".$statut;
	$sql .= " WHERE rowid = ".$this->id." AND fk_statut = 1 ;";
	
	if ($this->db->query($sql) )
	  {
	    $result = 0;
	    $this->log($user, $statut, time());
				
	    // Appel des triggers
	    include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
	    $interface=new Interfaces($this->db);
	    $result=$interface->run_triggers('ORDER_SUPPLIER_VALIDATE',$this,$user,$langs,$conf);
        if ($result < 0) { $error++; $this->errors=$interface->errors; }
	    // Fin appel triggers

	    return 1;
	  }
	else
	  {
	    dolibarr_syslog("CommandeFournisseur::Cancel Error -1");
	    return -1;
	  }
      }
    else
      {
	dolibarr_syslog("CommandeFournisseur::Cancel Not Authorized");
	return -1;
      }
  }


  /**
   *    \brief      Retourne le libellé du statut d'une commande (brouillon, validée, abandonnée, payée)
   *    \param      mode          0=libellé long, 1=libellé court, 2=Picto + Libellé court, 3=Picto, 4=Picto + Libellé long
   *    \return     string        Libelle
   */
  function getLibStatut($mode=0)
  {
    return $this->LibStatut($this->statut,$mode);
  }

  /**
   *    	\brief      Renvoi le libellé d'un statut donné
   *		\param      statut        	Id statut
   *    	\param      mode          	0=libellé long, 1=libellé court, 2=Picto + Libellé court, 3=Picto, 4=Picto + Libellé long, 5=Libellé court + Picto
   *    	\return     string			Libellé du statut
   */
  function LibStatut($statut,$mode=0)
  {
    global $langs;
    $langs->load('orders');
	
    if ($mode == 0)
      {
	return $this->statuts[$statut];
      }
    if ($mode == 1)
      {
	return $this->statuts[$statut];
      }
    if ($mode == 2)
      {
	return $this->statuts[$statut];
      }
    if ($mode == 3)
      {
	if ($statut==0) return img_picto($langs->trans('StatusOrderDraft'),'statut0');
	if ($statut==1) return img_picto($langs->trans('StatusOrderValidated'),'statut1');
	if ($statut==2) return img_picto($langs->trans('StatusOrderApproved'),'statut3');
	if ($statut==3) return img_picto($langs->trans('StatusOrderOnProcess'),'statut3');
	if ($statut==4) return img_picto($langs->trans('StatusOrderReceivedPartially'),'statut3');
	if ($statut==5) return img_picto($langs->trans('StatusOrderProcessed'),'statut6');
	if ($statut==6) return img_picto($langs->trans('StatusOrderCanceled'),'statut5');
	if ($statut==9) return img_picto($langs->trans('StatusOrderRefused'),'statut5');
      }
    if ($mode == 4)
      {
	if ($statut==0) return img_picto($langs->trans('StatusOrderDraft'),'statut0').' '.$this->statuts[$statut];
	if ($statut==1) return img_picto($langs->trans('StatusOrderValidated'),'statut1').' '.$this->statuts[$statut];
	if ($statut==2) return img_picto($langs->trans('StatusOrderApproved'),'statut3').' '.$this->statuts[$statut];
	if ($statut==3) return img_picto($langs->trans('StatusOrderOnProcess'),'statut3').' '.$this->statuts[$statut];
	if ($statut==4) return img_picto($langs->trans('StatusOrderReceivedPartially'),'statut3').' '.$this->statuts[$statut];
	if ($statut==5) return img_picto($langs->trans('StatusOrderProcessed'),'statut6').' '.$this->statuts[$statut];
	if ($statut==6) return img_picto($langs->trans('StatusOrderCanceled'),'statut5').' '.$this->statuts[$statut];
	if ($statut==9) return img_picto($langs->trans('StatusOrderRefused'),'statut5').' '.$this->statuts[$statut];
      }
    if ($mode == 5)
      {
	if ($statut==0) return $this->statuts[$statut].' '.img_picto($langs->trans('StatusOrderDraft'),'statut0');
	if ($statut==1) return $this->statuts[$statut].' '.img_picto($langs->trans('StatusOrderValidated'),'statut1');
	if ($statut==2) return $this->statuts[$statut].' '.img_picto($langs->trans('StatusOrderApproved'),'statut3');
	if ($statut==3) return $this->statuts[$statut].' '.img_picto($langs->trans('StatusOrderOnProcess'),'statut3');
	if ($statut==4) return $this->statuts[$statut].' '.img_picto($langs->trans('StatusOrderReceivedPartially'),'statut3');
	if ($statut==5) return $this->statuts[$statut].' '.img_picto($langs->trans('StatusOrderProcessed'),'statut6');
	if ($statut==6) return $this->statuts[$statut].' '.img_picto($langs->trans('StatusOrderCanceled'),'statut5');
	if ($statut==9) return $this->statuts[$statut].' '.img_picto($langs->trans('StatusOrderRefused'),'statut5');
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
   *      \brief      Renvoie la référence de commande suivante non utilisée en fonction du module
   *                  de numérotation actif défini dans COMMANDE_SUPPLIER_ADDON
   *      \param	    soc  		            objet societe
   *      \return     string                  reference libre pour la facture
   */
  function getNextNumRef($soc)
  {
    global $db, $langs, $conf;
    $langs->load("orders");

    $dir = DOL_DOCUMENT_ROOT .'/fourn/commande/modules';
    $modelisok=0;
    $liste=array();
	
    if (defined('COMMANDE_SUPPLIER_ADDON') && COMMANDE_SUPPLIER_ADDON)
      {
	$file = COMMANDE_SUPPLIER_ADDON.'.php';
			
	if (is_readable($dir.'/'.$file))
	  {
	    // Definition du nom de module de numerotation de commande fournisseur
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
		dolibarr_print_error($db,"Facture::getNextNumRef ".$obj->error);
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
   * 		\brief	Approuve une commande
   *
   */
  function approve($user)
  {
    dolibarr_syslog("CommandeFournisseur::Approve");
    $result = 0;
    if ($user->rights->fournisseur->commande->approuver)
      {
	$sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur SET fk_statut = 2";
	$sql .= " WHERE rowid = ".$this->id." AND fk_statut = 1 ;";
	
	if ($this->db->query($sql) )
	  {
	    $result = 0;
	    $this->log($user, 2, time());	// Statut 2
	
	    // Appel des triggers
	    include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
	    $interface=new Interfaces($this->db);
	    $result=$interface->run_triggers('ORDER_SUPPLIER_APPROVE',$this,$user,$langs,$conf);
        if ($result < 0) { $error++; $this->errors=$interface->errors; }
	    // Fin appel triggers

	    $subject = "Votre commande ".$this->ref." a été approuvée";
	    $message = "Bonjour,\n\n";
	    $message .= "Votre commande ".$this->ref." a été approuvée, par $user->fullname";
	    $message .= "\n\nCordialement,\n\n";
	    $this->_NotifyCreator($user, $subject, $message);

	    dolibarr_syslog("CommandeFournisseur::valid Success");
	    $this->db->commit();
	    return 1;
	  }
	else
	  {
	    dolibarr_syslog("CommandeFournisseur::Approve Error -1");
	    $result = -1;
	  }
      }
    else
      {
	dolibarr_syslog("CommandeFournisseur::Approve Not Authorized");
      }
    return $result ;
  }

  /**
   * Refuse une commande
   *
   *
   */
  function refuse($user)
  {
    dolibarr_syslog("CommandeFournisseur::Refuse");
    $result = 0;
    if ($user->rights->fournisseur->commande->approuver)
      {
	$sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur SET fk_statut = 9";
	$sql .= " WHERE rowid = ".$this->id;
	  
	if ($this->db->query($sql) )
	  {
	    $result = 0;
	    $this->log($user, 9, time());

	    $subject = "Votre commande ".$this->ref." a été refusée";
	    $message = "Votre commande ".$this->ref." a été refusée, par $user->fullname";

	    $this->_NotifyCreator($user, $subject, $message);
	  }
	else
	  {
	    dolibarr_syslog("CommandeFournisseur::Refuse Error -1");
	    $result = -1;
	  }	  
      }
    else
      {
	dolibarr_syslog("CommandeFournisseur::Refuse Not Authorized");
      }
    return $result ;
  }
  /*
   *
   *
   */
  function _NotifyCreator($user, $subject, $message)
  {
    require_once (DOL_DOCUMENT_ROOT."/lib/CMailFile.class.php");
    
    $cc = new User($this->db, $this->user_author_id);
    $cc->fetch();
    
    $sendto = $cc->email;
    $from = $user->email;

    $mailfile = new CMailFile($subject,
			      $sendto,
			      $from,
			      $message, array(), array(), array());
    if ( $mailfile->sendfile() )
      {
	return 0;
      }
  }

  /**
   * Envoie la commande au fournisseur
   *
   *
   */
  function commande($user, $date, $methode)
  {
    dolibarr_syslog("CommandeFournisseur::Commande");
    $result = 0;
    if ($user->rights->fournisseur->commande->commander)
      {
	$sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur SET fk_statut = 3, fk_methode_commande=".$methode.",date_commande=".$this->db->idate("$date");
	$sql .= " WHERE rowid = ".$this->id." AND fk_statut = 2 ;";
	  
	if ($this->db->query($sql) )
	  {
	    $result = 0;
	    $this->log($user, 3, $date);
	  }
	else
	  {
	    dolibarr_syslog("CommandeFournisseur::Commande Error -1");
	    $result = -1;
	  }	  
      }
    else
      {
	dolibarr_syslog("CommandeFournisseur::Commande Not Authorized");
      }
    return $result ;
  }

  /**
   *      \brief      Créé la commande au statut brouillon
   *      \param      user        Utilisateur qui crée
   *      \return     int         <0 si ko, >0 si ok
   */
  function create($user)
  {
    dolibarr_syslog("CommandeFournisseur::Create soc id=".$this->socid);

    $this->db->begin();
        
    /* On positionne en mode brouillon la commande */
    $this->brouillon = 1;
    
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."commande_fournisseur (fk_soc, date_creation, fk_user_author, fk_statut) ";
    $sql .= " VALUES (".$this->socid.", now(), ".$user->id.",0)";
    
    if ( $this->db->query($sql) )
      {
	$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."commande_fournisseur");
    
	$sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur";
	$sql.= " SET ref='(PROV".$this->id.")'";
	$sql.= " WHERE rowid=".$this->id;
	if ($this->db->query($sql))
	  {
	    // On logue creation pour historique   
	    $this->log($user, 0, time());
                
	    // Appel des triggers
	    include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
	    $interface=new Interfaces($this->db);
	    $result=$interface->run_triggers('ORDER_SUPPLIER_CREATE',$this,$user,$langs,$conf);
        if ($result < 0) { $error++; $this->errors=$interface->errors; }
	    // Fin appel triggers

	    dolibarr_syslog("CommandeFournisseur::Create : Success");
	    $this->db->commit();
	    return 1;
	  }
	else
	  {
	    $this->error=$this->db->error()." - ".$sql;
	    dolibarr_syslog("CommandeFournisseur::Create: Failed -2 - ".$this->error);
	    $this->db->rollback();
	    return -2;
	  }
      }
    else
      {
	$this->error=$this->db->error()." - ".$sql;
	dolibarr_syslog("CommandeFournisseur::Create: Failed -1 - ".$this->error);
	$this->db->rollback();
	return -1;
      }
  }

	/**
	*      \brief      Ajoute une ligne de commande
	*      \param      desc            	Description
	*      \param      pu              	Prix unitaire
	*      \param      qty             	Quantité
	*      \param      txtva           	Taux tva
	*      \param      fk_product      	Id produit
	*      \param      remise_percent  	Remise
	*      \param      price_base_type	HT or TTC
	*      \param      int             	<0 si ko, >0 si ok
	*/
	function addline($desc, $pu, $qty, $txtva, $fk_product=0, $fk_prod_fourn_price=0, $fourn_ref='', $remise_percent=0, $price_base_type='HT')
	{
		global $langs;
		
		// Clean parameters
		$qty  = price2num($qty);
		$pu   = price2num($pu);
		$desc = trim($desc);
		$remise_percent = price2num($remise_percent);
		
		dolibarr_syslog("Fournisseur.Commande.class::addline $desc, $pu, $qty, $txtva, $fk_product, $remise_percent");

		if ($qty < 1 && ! $fk_product)
		{
			$this->error=$langs->trans("ErrorFieldRequired",$langs->trans("Product"));
			return -1;
		}
		
		if ($this->brouillon)
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
						$txtva = $prod->tva_tx;
						$pu    = $prod->fourn_pu;
						$ref   = $prod->ref_fourn;
					}
					if ($result == 0 || $result == -1)
					{
						$this->error="Aucun tarif trouvé pour cette quantité. Quantité saisie insuffisante ?";
						$this->db->rollback();
						dolibarr_syslog("Fournisseur.commande.class::addline result=".$result." - ".$this->error);
						return -1;
					}
					if ($result < -1)
					{
						$this->error=$prod->error;
						$this->db->rollback();
						dolibarr_syslog("Fournisseur.commande.class::addline result=".$result." - ".$this->error);
						return -1;
					}
				}
				else
				{
					$this->error=$this->db->error();
					return -1;
				}
			}
			
			$subprice = price2num($pu,'MU');
			
			// Champ obsolete
			$remise = 0;
			$price = $subprice;
			if ($remise_percent > 0)
			{
				$remise = round(($pu * $remise_percent / 100), 2);
				$price = $pu - $remise;
			}
			
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."commande_fournisseurdet";
			$sql.= " (fk_commande,label, description,";
			$sql.= " fk_product,";
			$sql.= " price, qty, tva_tx, remise_percent, subprice, remise, ref)";
			$sql.= " VALUES (".$this->id.", '" . addslashes($label) . "','" . addslashes($desc) . "',";
			if ($fk_product) { $sql.= $fk_product.","; }
			else { $sql.= "null,"; }
			$sql.= price2num($price,'MU').", '$qty', $txtva, $remise_percent,'".price2num($subprice,'MU')."','".price2num($remise)."','".$ref."') ;";
			dolibarr_syslog('Fournisseur.commande.class::addline sql='.$sql);
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
				$this->db->rollback();
				return -1;
			}
		}
	}
	
	
  /**
   * Dispatch un element de la commande dans un stock
   *
   *
   *
   */
  function DispatchProducts($user, $products, $qtys, $entrepots)
  {
    global $conf;
    require_once DOL_DOCUMENT_ROOT ."/product/stock/mouvementstock.class.php";

    $this->db->begin();

    if ( is_array($products) )
      {

      }
    else
      {
	$res = $this->DispatchProduct($user, $product, $qty, $entrepot);
      }

    $this->db->rollback();
    
    return $res;
  }

  function DispatchProduct($user, $product, $qty, $entrepot, $price=0)
  {
    global $conf;
    $error = 0;
    require_once DOL_DOCUMENT_ROOT ."/product/stock/mouvementstock.class.php";
    
    dolibarr_syslog("CommandeFournisseur::DispatchProduct");

    if ( ($this->statut == 3 || $this->statut == 4) && $qty > 0)
      {
	$this->db->begin();
	
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."commande_fournisseur_dispatch ";
	$sql.= " (fk_commande,fk_product, qty, fk_entrepot, fk_user, datec) VALUES ";
	$sql.= " ('".$this->id."','".$product."','".$qty."','".$entrepot."','".$user->id."',now() );";
	
	$resql = $this->db->query($sql);
	if (! $resql)
	  {
	    $error = -1;
	  }
	// Si module stock géré et que expedition faite depuis un entrepot
	if (!$error && $conf->stock->enabled && $entrepot)
	  {
	    /*
	     * Enregistrement d'un mouvement de stock pour chaque produit de l'expedition
	     */	       
	    $mouv = new MouvementStock($this->db);
	    $result=$mouv->reception($user, $product, $entrepot, $qty, $price);
	    if ($result < 0)
	      {
		$this->error=$this->db->error()." - sql=$sql";
		dolibarr_syslog("CommandeFournisseur::DispatchProduct".$this->error);
		$error = -2;
	      }
	    $i++;	
	  }
	
	if ($error == 0)
	  {
	    $this->db->commit();
	    return 0;
	  }
	else
	  {
	    $this->db->rollback();
	    return -1;
	  }      
      }
    else
      {
	return -2;
      }
  }
  /**
   * Supprime une ligne de la commande
   *
   */
  function delete_line($idligne)
  {
    if ($this->statut == 0)
      {
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."commande_fournisseurdet WHERE rowid = ".$idligne;
	$resql=$this->db->query($sql);

	dolibarr_syslog("Fournisseur.commande.class::delete_line sql=".$sql);
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
   * Mettre à jour le prix
   *
   */
  function update_price()
  {
    include_once DOL_DOCUMENT_ROOT . "/lib/price.lib.php";

    /*
     *  Liste des produits a ajouter
     */
    $sql = "SELECT price, qty, tva_tx ";
    $sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet ";
    $sql .= " WHERE fk_commande = $this->id";

    if ( $this->db->query($sql) )
      {
	$num = $this->db->num_rows();
	$i = 0;
	  
	while ($i < $num)
	  {
	    $obj = $this->db->fetch_object();
	    $products[$i][0] = $obj->price;
	    $products[$i][1] = $obj->qty;
	    $products[$i][2] = $obj->tva_tx;
	    $i++;
	  }
      }
    $calculs = calcul_price($products, $this->remise_percent);

    $totalht = $calculs[0];
    $totaltva = $calculs[1];
    $totalttc = $calculs[2];
    $total_remise = $calculs[3];

    $this->remise         = $total_remise;
    $this->total_ht       = $totalht;
    $this->total_tva      = $totaltva;
    $this->total_ttc      = $totalttc;
    /*
     *
     */
    $sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur set";
    $sql .= "  amount_ht ='".price2num($totalht)."'";
    $sql .= ", total_ht  ='".price2num($totalht)."'";
    $sql .= ", tva       ='".price2num($totaltva)."'";
    $sql .= ", total_ttc ='".price2num($totalttc)."'";
    $sql .= ", remise    ='".price2num($total_remise)."'";
    $sql .= " WHERE rowid = $this->id";
    if ( $this->db->query($sql) )
      {
	return 1;
      }
    else
      {
	print "Erreur mise à jour du prix<p>".$sql;
	return -1;
      }
  }

  /**
   * 	\brief		Supprime la commande
   *
   */
  function delete()
  {
    $err = 0;
	
    $this->db->begin();
	
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."commande_fournisseurdet WHERE fk_commande =". $this->id ;
    if (! $this->db->query($sql) )
      {
	$err++;
      }
	
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."commande_fournisseur WHERE rowid =".$this->id;
    $sql.= " AND fk_statut = 0;";
    if ($resql = $this->db->query($sql) )
      {
	if ($this->db->affected_rows($resql) <> 1)
	  {
	    $err++;
	  }
      }
    else
      {
	$err++;
      }
	
    if ($err == 0)
      {
	// Appel des triggers
	include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
	$interface=new Interfaces($this->db);
	$result=$interface->run_triggers('ORDER_SUPPLIER_DELETE',$this,$user,$langs,$conf);
    if ($result < 0) { $error++; $this->errors=$interface->errors; }
	// Fin appel triggers

	dolibarr_syslog("CommandeFournisseur::delete : Success");
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
   *
   *
   */
  function get_methodes_commande()
  {
    $sql = "SELECT rowid, libelle FROM ".MAIN_DB_PREFIX."c_methode_commande_fournisseur";
    $sql .= " WHERE active = 1";

    if ($this->db->query($sql))
      {
	$i = 0;
	$num = $this->db->num_rows();
	$this->methodes_commande = array();
	while ($i < $num)
	  {
	    $row = $this->db->fetch_row();

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
	* 	\bref		Set a delivery in database for this supplier order
	*	\param		user		User that input data
	*	\param		date		Date of reception
	*	\param		type		Type of receipt
	*/
	function Livraison($user, $date, $type)
	{
		$result = 0;

		dolibarr_syslog("CommandeFournisseur::Livraison");

		if ($user->rights->fournisseur->commande->receptionner && $date < time())
		{
			if ($type == 'tot')	$statut = 5;
			if ($type == 'par') $statut = 4;
			if ($type == 'nev') $statut = 6;
			if ($type == 'can') $statut = 6;

			if ($statut == 4 or $statut == 5 or $statut == 6)
			{
				$this->db->begin();
				
				$sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur";
				$sql.= " SET fk_statut = ".$statut;
				$sql.= " WHERE rowid = ".$this->id;
				$sql.= " AND (fk_statut = 3 OR fk_statut = 4)";
			
				dolibarr_syslog("CommandeFournisseur::Livraison sql=".$sql);
				$resql=$this->db->query($sql);
				if ($resql)
				{
					$result = 0;
					$result=$this->log($user, $statut, $date);
					
					$this->db->commit();
				}
				else
				{
					$this->db->rollback();
					$this->error=$this->db->lasterror();
					dolibarr_syslog("CommandeFournisseur::Livraison Error ".$this->error, LOG_ERR);
					$result = -1;
				}	  
			}
			else
			{
				dolibarr_syslog("CommandeFournisseur::Livraison Error -2", LOG_ERR);
				$result = -2;
			}	
		}
		else
		{
			dolibarr_syslog("CommandeFournisseur::Livraison Not Authorized");
		}
		return $result ;
	}

  /**     \brief      Créé la commande depuis une propale existante
	  \param      user            Utilisateur qui crée
	  \param      propale_id      id de la propale qui sert de modèle
  */
  function updateFromCommandeClient($user, $idc, $comclientid)
  {
    $comclient = new Commande($this->db);
    $comclient->fetch($comclientid);
		
    $this->id = $idc;

    $this->lines = array();

    for ($i = 0 ; $i < sizeof($comclient->lignes) ; $i++)
      {
	$prod = new Product($this->db, $comclient->lignes[$i]->fk_product);
	if ($prod->fetch($comclient->lignes[$i]->fk_product) > 0)
	  {
	    $libelle  = $prod->libelle;
	    $ref      = $prod->ref;
	  }
			
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."commande_fournisseurdet";
	$sql .= " (fk_commande,label,description,fk_product, price, qty, tva_tx, remise_percent, subprice, remise, ref)";
	$sql .= " VALUES (".$idc.", '" . addslashes($libelle) . "','" . addslashes($comclient->lignes[$i]->desc) . "'";
	$sql .= ",".$comclient->lignes[$i]->fk_product.",'".price2num($comclient->lignes[$i]->price)."'";
	$sql .= ", '".$comclient->lignes[$i]->qty."', ".$comclient->lignes[$i]->tva_tx.", ".$comclient->lignes[$i]->remise_percent;
	$sql .= ", '".price2num($comclient->lignes[$i]->subprice)."','0','".$ref."') ;";
	if ( $this->db->query( $sql) )
	  {
	    $this->update_price();
	  }
      }

    return 1;
  }
	
	
  /**
   *		\brief		Met a jour les notes
   *		\return		int			<0 si ko, >=0 si ok
   */
  function UpdateNote($user, $note, $note_public)
  {
    dolibarr_syslog("CommandeFournisseur::UpdateNote");
	
    $result = 0;
	
    $sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur";
    $sql.= " SET note  ='".trim($note) ."',";
    $sql.= " note_public  ='".trim($note_public) ."'";
    $sql.= " WHERE rowid = ".$this->id;
	
    $resql=$this->db->query($sql);
    if ($resql)
      {
	$result = 0;
      }
    else
      {
	$this->error=$this->db->error();
	dolibarr_syslog("CommandeFournisseur::UpdateNote "+$this->error);
	$result = -1;
      }
	
    return $result ;
  }

  /*
   *
   *
   *
   */
  function ReadApprobators()
  {
    $this->approbs = array();

    $sql = "SELECT u.name, u.firstname, u.email";
    $sql .= " FROM ".MAIN_DB_PREFIX."user as u";
    $sql .= " , ".MAIN_DB_PREFIX."user_rights as ur";
    $sql .= " WHERE u.rowid = ur.fk_user";
    $sql .= " AND ur.fk_id = 184";
    
    $resql = $this->db->query($sql);

    if ($resql)
      {
	$num = $this->db->num_rows($resql);
	$i = 0;
	
	while ($i < $num)
	  {
	    $row = $this->db->fetch_row($resql);	    
	    $this->approbs[$i] = $row;
	    $i++;
	  }

	$this->db->free($resql);
      }
    else 
      {
	dolibarr_syslog("ReadApprobators Erreur");
      }    
  }

  /*
   *
   *
   */
  function _details_text()
  {
    $blank = "                                                                                                                            ";
    $this->details_text = substr("Produit".$blank,0,50);
    $this->details_text .= substr("Qty".$blank,0,8);
    $this->details_text .= substr("Prix".$blank,0,8);
    $this->details_text .= "\n";
    $this->details_text .= substr("-----------------------------------------------------------------------------------------------------------------------",0,66);
    $this->details_text .= "\n";

    $sql = "SELECT l.ref, l.fk_product, l.description, l.price, l.qty";
    $sql .= ", l.rowid, l.tva_tx, l.remise_percent, l.subprice";
    $sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as l ";
    $sql .= " WHERE l.fk_commande = ".$this->id." ORDER BY l.rowid";
    
    $resql = $this->db->query($sql);
    if ($resql)
      {
	$num_lignes = $this->db->num_rows($resql);
	$i = 0;
	
	while ($i < $num_lignes)
	  {
	    $objp = $this->db->fetch_object();

	    $this->details_text .=  "-".substr(stripslashes($objp->description).$blank, 0, 50);
	    $this->details_text .= substr($objp->qty.$blank, 0, 7);
	    $this->details_text .= substr($blank.price($objp->subprice),-8);
	    $this->details_text .= "\n";
	    $i++;
	    
	  }
	$this->details_text .= substr("-----------------------------------------------------------------------------------------------------------------------",0,66);
	$this->details_text .= "\n";
	$this->details_text .= substr($blank."Total HT : ".price($this->total_ht), -66);
	$this->details_text .= "\n";
	$this->details_text .= substr($blank."Total TTC : ".price($this->total_ttc), -66);
	$this->details_text .= "\n";
	
	$this->db->free();
      } 
    else
      {
	print $this->db->error();
      }
  }


  /**
   *      \brief     Mets à jour une ligne de commande
   *      \param     rowid            Id de la ligne de facture
   *      \param     desc             Description de la ligne
   *      \param     pu               Prix unitaire
   *      \param     qty              Quantité
   *      \param     remise_percent   Pourcentage de remise de la ligne
   *      \param     tva_tx           Taux TVA
   *	  \param	 info_bits		  Miscellanous informations
   *      \return    int              < 0 si erreur, > 0 si ok
   */
  function updateline($rowid, $desc, $pu, $qty, $remise_percent=0, $txtva, $price_base_type='HT', $info_bits=0)
  {
    dolibarr_syslog("CommandeFournisseur::UpdateLine $rowid, $desc, $pu, $qty, $remise_percent, $txtva");
    include_once(DOL_DOCUMENT_ROOT.'/lib/price.lib.php');

    if ($this->brouillon)
      {
	$this->db->begin();
			
	// Nettoyage paramètres
	$remise_percent=price2num($remise_percent);
	$qty=price2num($qty);
	if (! $qty) $qty=1;
	$pu = price2num($pu);
	$txtva=price2num($txtva);

	// Calcul du total TTC et de la TVA pour la ligne a partir de
	// qty, pu, remise_percent et txtva
	// TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker 
	// la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.
	$tabprice=calcul_price_total($qty, $pu, $remise_percent, $txtva, $price_base_type, $info_bits);
	$total_ht  = $tabprice[0];
	$total_tva = $tabprice[1];
	$total_ttc = $tabprice[2];

	// Anciens indicateurs: $price, $subprice, $remise (a ne plus utiliser)
	$price = $pu;
	$subprice = $pu;
	$remise = 0;
	if ($remise_percent > 0)
	  {
	    $remise = round(($pu * $remise_percent / 100),2);
	    $price = ($pu - $remise);
	  }
	$price    = price2num($price);
	$subprice  = price2num($subprice);

	// Mise a jour ligne en base
	$sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseurdet SET";
	$sql.= " description='".addslashes($desc)."'";
	$sql.= ",price='".price2num($price)."'";
	$sql.= ",subprice='".price2num($subprice)."'";
	$sql.= ",remise='".price2num($remise)."'";
	$sql.= ",remise_percent='".price2num($remise_percent)."'";
	$sql.= ",tva_tx='".price2num($txtva)."'";
	$sql.= ",qty='".price2num($qty)."'";
	//if ($date_end) { $sql.= ",date_start='$date_end'"; }
	//else { $sql.=',date_start=null'; }
	//if ($date_end) { $sql.= ",date_end='$date_end'"; }
	//else { $sql.=',date_end=null'; }
	//$sql.= " info_bits=".$info_bits.",";
	//$sql.= ",total_ht='".price2num($total_ht)."'";
	//$sql.= ",total_tva='".price2num($total_tva)."'";
	//$sql.= ",total_ttc='".price2num($total_ttc)."'";
	$sql.= " WHERE rowid = ".$rowid;

	$result = $this->db->query( $sql);
	if ($result > 0)
	  {
	    // Mise a jour info denormalisees au niveau facture
	    $this->update_price($this->id);
	    $this->db->commit();
	    return $result;
	  }
	else
	  {
	    $this->error=$this->db->error();
	    dolibarr_syslog("Commande.fournisseur.class::updateline ".$this->error);
	    $this->db->rollback();
	    return -1;
	  }
      }
    else
      {
	$this->error="Commande::updateline Order status makes operation forbidden";
	dolibarr_syslog("Commande.fournisseur.class::updateline ".$this->error);
	return -2;
      }
  }

  
  	/**
	 *		\brief		Initialise la commande avec valeurs fictives aléatoire
	 *					Sert à générer une commande pour l'aperu des modèles ou demo
	 */
	function initAsSpecimen()
	{
		global $user,$langs;

		dolibarr_syslog("CommandeFournisseur::initAsSpecimen");

		// Charge tableau des id de société socids
		$socids = array();
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe WHERE fournisseur=1 LIMIT 10";
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num_socs = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num_socs)
			{
				$i++;

				$row = $this->db->fetch_row($resql);
				$socids[$i] = $row[0];
			}
		}

		// Charge tableau des produits prodids
		$prodids = array();
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."product WHERE envente=1";
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

		// Initialise paramètres
		$this->id=0;
		$this->ref = 'SPECIMEN';
		$this->specimen=1;
		$socid = rand(1, $num_socs);
		$this->socid = $socids[$socid];
		$this->date = time();
		$this->date_lim_reglement=$this->date+3600*24*30;
		$this->cond_reglement_code = 'RECEP';
		$this->mode_reglement_code = 'CHQ';
		$this->note_public='SPECIMEN';
		$nbp = rand(1, 9);
		$xnbp = 0;
		while ($xnbp < $nbp)
		{
			$ligne=new CommandeFournisseurLigne($this->db);
			$ligne->desc=$langs->trans("Description")." ".$xnbp;
			$ligne->qty=1;
			$ligne->subprice=100;
			$ligne->price=100;
			$ligne->tva_tx=19.6;
			$prodid = rand(1, $num_prods);
			$ligne->produit_id=$prodids[$prodid];
			$this->lignes[$xnbp]=$ligne;
			$xnbp++;
		}

		$this->amount_ht      = $xnbp*100;
		$this->total_ht       = $xnbp*100;
		$this->total_tva      = $xnbp*19.6;
		$this->total_ttc      = $xnbp*119.6;
	}
}


/**
 *  \class      CommandeFournisseurLigne
 *  \brief      Classe de gestion des lignes de commande
 */
class CommandeFournisseurLigne extends CommandeLigne
{
  // From llx_commandedet
  var $qty;
  var $tva_tx;
  var $subprice;
  var $remise_percent;
  var $price;
  var $fk_product;
  var $desc;          // Description ligne
	
  // From llx_product
  var $libelle;       // Label produit
  var $product_desc;  // Description produit
  
  // From llx_product_fournisseur
  var $ref_fourn;     // Référence fournisseur

  function CommandeFournisseurLigne()
  {
		
  }
}

?>
