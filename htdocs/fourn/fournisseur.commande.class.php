<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerke@telenet.be>
 * Copyright (C) 2005-2008 Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       htdocs/fourn/fournisseur.commande.class.php
 *	\ingroup    fournisseur,commande
 *	\brief      Fichier des classes des commandes fournisseurs
 *	\version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT."/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/commande/commande.class.php");


/**
 *	\class      CommandeFournisseur
 *	\brief      Classe de gestion de commande fournisseur
 */
class CommandeFournisseur extends Commande
{
	var $db ;
	var $error;
	var $element='order_supplier';
	var $table_element='commande_fournisseur';
	var $table_element_line = 'commande_fournisseurdet';
	var $fk_element = 'fk_commande';

	var $id ;
	var $brouillon;


	/**   \brief      Constructeur
	 *    \param      DB      Handler d'acc�s aux bases de donn�es
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
	 *	\brief      Get object and lines from database
	 * 	\param		id			Id of order to load
	 * 	\param		ref			Ref of object
	 *	\return     int         >0 if OK, <0 if KO
	 */
	function fetch($id,$ref='')
	{
		$sql = "SELECT c.rowid, c.ref, c.date_creation, c.fk_soc, c.fk_user_author, c.fk_statut, c.amount_ht, c.total_ht, c.total_ttc, c.tva,";
		$sql .= " ".$this->db->pdate("c.date_commande")." as date_commande, c.fk_projet as fk_project, c.remise_percent, c.source, c.fk_methode_commande,";
		$sql .= " c.note, c.note_public, c.model_pdf,";
		$sql .= " cm.libelle as methode_commande";
		$sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as c";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_methode_commande_fournisseur as cm ON cm.rowid = c.fk_methode_commande";
		if ($ref) $sql.= " WHERE c.ref='".$ref."'";
		else $sql.= " WHERE c.rowid=".$id;

		dol_syslog("CommandeFournisseur::fetch sql=".$sql,LOG_DEBUG);
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
			$this->date_commande       = $obj->date_commande; // date � laquelle la commande a �t� transmise
			$this->date                = $obj->date_creation;
			$this->remise_percent      = $obj->remise_percent;
			$this->methode_commande_id = $obj->fk_methode_commande;
			$this->methode_commande    = $obj->methode_commande;

			$this->source              = $obj->source;
			$this->facturee            = $obj->facture;
			$this->fk_project          = $obj->fk_project;
			$this->projet_id           = $obj->fk_project;	// For compatibility with old code
			$this->note                = $obj->note;
			$this->note_public         = $obj->note_public;
			$this->modelpdf            = $obj->model_pdf;

			$this->db->free();

			if ($this->statut == 0) $this->brouillon = 1;

			// Now load lines
			$this->lignes = array();

			$sql = "SELECT l.rowid, l.ref as ref_fourn, l.fk_product, l.product_type, l.label, l.description,";
			$sql.= " l.qty,";
			$sql.= " l.tva_tx, l.remise_percent, l.subprice,";
			$sql.= " l.total_ht, l.total_tva, l.total_ttc,";
			$sql.= " p.rowid as product_id, p.ref, p.label as label, p.description as product_desc";
			$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet	as l";
			$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON l.fk_product = p.rowid';
			$sql.= " WHERE l.fk_commande = ".$this->id;
			$sql.= " ORDER BY l.rowid";
			//print $sql;

			dol_syslog("CommandeFournisseur::fetch sql=".$sql,LOG_DEBUG);
			$result = $this->db->query($sql);
			if ($result)
			{
				$num = $this->db->num_rows($result);
				$i = 0;

				while ($i < $num)
				{
					$objp                  = $this->db->fetch_object($result);

					$ligne                 = new CommandeFournisseurLigne($this->db);

					$ligne->id                  = $objp->rowid;
					$ligne->desc                = $objp->description;  // Description ligne
					$ligne->description         = $objp->description;  // Description ligne
					$ligne->qty                 = $objp->qty;
					$ligne->tva_tx              = $objp->tva_tx;
					$ligne->subprice            = $objp->subprice;
					$ligne->remise_percent      = $objp->remise_percent;
					$ligne->total_ht            = $objp->total_ht;
					$ligne->total_tva           = $objp->total_tva;
					$ligne->total_ttc           = $objp->total_ttc;
					$ligne->product_type        = $objp->product_type;

					$ligne->fk_product          = $objp->fk_product;   // Id du produit
					$ligne->libelle             = $objp->label;        // Label produit
					$ligne->product_desc        = $objp->product_desc; // Description produit

					$ligne->ref                 = $objp->ref;          // Reference
					$ligne->ref_fourn           = $objp->ref_fourn;    // Reference supplier

					$this->lignes[$i]      = $ligne;
					//dol_syslog("1 ".$ligne->desc);
					//dol_syslog("2 ".$ligne->product_desc);
					$i++;
				}
				$this->db->free($result);

				return 1;
			}
			else
			{
				$this->error=$this->db->error()." sql=".$sql;
				dol_syslog("CommandeFournisseur::Fetch ".$this->error, LOG_ERR);
				return -1;
			}
		}
		else
		{
			$this->error=$this->db->error()." sql=".$sql;
			dol_syslog("CommandeFournisseur::Fetch ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *      \brief      Add a line in log table
	 *      \param      user        User making action
	 *      \param      statut      Status of order
	 *      \param      datelog     Date of change
	 * 		\param		comment		Comment
	 *      \return     int         <0 if KO, >0 if OK
	 */
	function log($user, $statut, $datelog, $comment='')
	{
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."commande_fournisseur_log (datelog, fk_commande, fk_statut, fk_user, comment)";
		$sql.= " VALUES (".$this->db->idate($datelog).",".$this->id.", ".$statut.", ";
		$sql.= $user->id.", ";
		$sql.= ($comment?"'".addslashes($comment)."'":'null');
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
	 *		\brief		Valide la commande
	 *		\param		user		Utilisateur qui valide
	 */
	function valid($user)
	{
		global $langs,$conf;

		$error=0;

		dol_syslog("CommandeFournisseur::Valid");
		$result = 0;
		if ($user->rights->fournisseur->commande->valider)
		{
			$this->db->begin();

			// Definition du nom de module de numerotation de commande
			$soc = new Societe($this->db);
			$soc->fetch($this->fourn_id);

			// Check if object has a temporary ref
			if (eregi('^\(PROV', $this->ref))
			{
				$num = $this->getNextNumRef($soc);
			}
			else
			{
				$num = $this->ref;
			}

			$sql = 'UPDATE '.MAIN_DB_PREFIX."commande_fournisseur";
			$sql.= " SET ref='$num', fk_statut = 1, date_valid=".$this->db->idate(mktime()).", fk_user_valid=$user->id";
			$sql.= " WHERE rowid = $this->id AND fk_statut = 0";

			$resql=$this->db->query($sql);
			if ($resql)
			{
				$result = 1;
				$this->log($user, 1, time());	// Statut 1
				$this->ref = $num;

				if ($error == 0)
				{
					// Appel des triggers
					include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
					$interface=new Interfaces($this->db);
					$result=$interface->run_triggers('ORDER_SUPPLIER_VALIDATE',$this,$user,$langs,$conf);
					if ($result < 0) { $error++; $this->errors=$interface->errors; }
					// Fin appel triggers
				}

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
				$this->error=$this->db->lasterror();
				dol_syslog("CommandeFournisseur::valid ".$this->error);
				$this->db->rollback();
				return -1;
			}
		}
		else
		{
			$this->error='Not Authorized';
			dol_syslog("CommandeFournisseur::valid ".$this->error);
			return -1;
		}
	}

	/**
	 * 		\brief		Annule la commande
	 * 		\param		user		Utilisateur qui demande annulation
	 *		\remarks	L'annulation se fait apres la validation
	 */
	function Cancel($user)
	{
		global $langs,$conf;

		//dol_syslog("CommandeFournisseur::Cancel");
		$result = 0;
		if ($user->rights->fournisseur->commande->annuler)
		{
			$statut = 6;

			$this->db->begin();

			$sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur SET fk_statut = ".$statut;
			$sql .= " WHERE rowid = ".$this->id." AND fk_statut = 1";

			if ($this->db->query($sql))
			{
				$result = 0;
				$this->log($user, $statut, time());

				// Appel des triggers
				include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers('ORDER_SUPPLIER_VALIDATE',$this,$user,$langs,$conf);
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
				dol_syslog("CommandeFournisseur::Cancel Error -1");
				$this->db->rollback();
				$this->error=$this->db->lasterror();
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
	 *    \brief      Retourne le libell� du statut d'une commande (brouillon, valid�e, abandonn�e, pay�e
	 *    \param      mode          0=libell� long, 1=libell� court, 2=Picto + Libell� court, 3=Picto, 4=Picto + Libell� long
	 *    \return     string        Libelle
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->statut,$mode);
	}

	/**
	 *    	\brief      Renvoi le libell� d'un statut donn�
	 *		\param      statut        	Id statut
	 *    	\param      mode          	0=libell� long, 1=libell� court, 2=Picto + Libell� court, 3=Picto, 4=Picto + Libell� long, 5=Libell� court + Picto
	 *    	\return     string			Libell� du statut
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
	 *      \brief      Renvoie la r�f�rence de commande suivante non utilis�e en fonction du modul
	 *                  de num�rotation actif d�fini dans COMMANDE_SUPPLIER_ADDON
	 *      \param	    soc  		            objet societe
	 *      \return     string                  reference libre pour la facture
	 */
	function getNextNumRef($soc)
	{
		global $db, $langs, $conf;
		$langs->load("orders");

		$dir = DOL_DOCUMENT_ROOT .'/includes/modules/supplier_order/';
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
	 * 		\brief		Accept an order
	 *		\param		user		Object user
	 */
	function approve($user)
	{
		global $langs,$conf;

		$error=0;

		dol_syslog("CommandeFournisseur::Approve");

		if ($user->rights->fournisseur->commande->approuver)
		{
			$this->db->begin();

			$sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur SET fk_statut = 2";
			$sql .= " WHERE rowid = ".$this->id." AND fk_statut = 1 ;";

			if ($this->db->query($sql))
			{
				$result = 0;
				$this->log($user, 2, time());	// Statut 2

				// If stock is incremented on validate order, we must increment it
				if ($result >= 0 && $conf->stock->enabled && $conf->global->STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER == 1)
				{
					require_once(DOL_DOCUMENT_ROOT."/product/stock/mouvementstock.class.php");

					for ($i = 0 ; $i < sizeof($this->lignes) ; $i++)
					{
						$mouvP = new MouvementStock($this->db);
						// We decrement stock of product (and sub-products)
						$entrepot_id = "1"; //Todo: ajouter possibilite de choisir l'entrepot
						// TODO Add price of product in method or '' to update PMP
						$result=$mouvP->reception($user, $this->lignes[$i]->fk_product, $entrepot_id, $this->lignes[$i]->qty);
						if ($result < 0) { $error++; }
					}
				}

				if ($error == 0)
				{
					// Appel des triggers
					include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
					$interface=new Interfaces($this->db);
					$result=$interface->run_triggers('ORDER_SUPPLIER_APPROVE',$this,$user,$langs,$conf);
					if ($result < 0) { $error++; $this->errors=$interface->errors; }
					// Fin appel triggers
				}

				if ($error == 0)
				{
					$langs->load("other");

					$subject = $langs->trans("EMailTextOrderApproved",$this->ref);
					$message = $langs->trans("Hello").",\n\n";
					$message .= $langs->trans("EMailTextOrderApprovedBy",$this->ref,$user->fullname);
					$message .= "\n\n".$langs->trans("Sincerely").",\n\n";
					$this->_NotifyCreator($user, $subject, $message);
				}

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
	 * Refuse une commande
	 *
	 *
	 */
	function refuse($user)
	{
		dol_syslog("CommandeFournisseur::Refuse");
		$result = 0;
		if ($user->rights->fournisseur->commande->approuver)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseur SET fk_statut = 9";
			$sql .= " WHERE rowid = ".$this->id;

			if ($this->db->query($sql) )
	  {
	  	$result = 0;
	  	$this->log($user, 9, time());

	  	$subject = $langs->trans("EMailTextOrderRefused",$this->ref);
	  	$message = $langs->trans("Hello").",\n\n";
	  	$message .= $langs->trans("EMailTextOrderRefusedBy",$this->ref,$user->fullname);

	  	$this->_NotifyCreator($user, $subject, $message);
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
	 * 	Send a supplier order to supplier
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
	 *      \brief      Cr�� la commande au statut brouillon
	 *      \param      user        Utilisateur qui cr�e
	 *      \return     int         <0 si ko, >0 si ok
	 */
	function create($user)
	{
		global $langs,$conf;

		dol_syslog("CommandeFournisseur::Create soc id=".$this->socid);

		$this->db->begin();

		/* On positionne en mode brouillon la commande */
		$this->brouillon = 1;

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."commande_fournisseur (ref, fk_soc, date_creation, fk_user_author, fk_statut, source, model_pdf) ";
		$sql .= " VALUES ('',".$this->socid.", ".$this->db->idate(mktime()).", ".$user->id.",0,0,'".$conf->global->COMMANDE_SUPPLIER_ADDON_PDF."')";

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

				dol_syslog("CommandeFournisseur::Create : Success");
				$this->db->commit();
				return 1;
			}
			else
			{
				$this->error=$this->db->error()." - ".$sql;
				dol_syslog("CommandeFournisseur::Create: Failed -2 - ".$this->error);
				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$this->error=$this->db->error()." - ".$sql;
			dol_syslog("CommandeFournisseur::Create: Failed -1 - ".$this->error);
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *      \brief      Ajoute une ligne de commande
	 *      \param      desc            	Description
	 *      \param      pu              	Unit price
	 *      \param      qty             	Quantity
	 *      \param      txtva           	Taux tva
	 *      \param      fk_product      	Id produit
	 *      \param      remise_percent  	Remise
	 *      \param      price_base_type		HT or TTC
	 * 		\param		pu_ttc				Unit price TTC
	 * 		\param		type				Type of line (0=product, 1=service)
	 *      \return     int             	<=0 if KO, >0 if OK
	 */
	function addline($desc, $pu_ht, $qty, $txtva, $fk_product=0, $fk_prod_fourn_price=0, $fourn_ref='', $remise_percent=0, $price_base_type='HT', $pu_ttc=0, $type=0)
	{
		global $langs,$mysoc;

		dol_syslog("FournisseurCommande::addline $desc, $pu_ht, $qty, $txtva, $fk_product, $fk_prod_fourn_price, $fourn_ref, $remise_percent, $price_base_type, $pu_ttc, $type");
		include_once(DOL_DOCUMENT_ROOT.'/lib/price.lib.php');

		// Clean parameters
		$remise_percent=price2num($remise_percent);
		$qty=price2num($qty);
		if (! $qty) $qty=1;
		if (! $info_bits) $info_bits=0;
		$pu_ht=price2num($pu_ht);
		$pu_ttc=price2num($pu_ttc);
		$txtva = price2num($txtva);
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
			$tabprice = calcul_price_total($qty, $pu, $remise_percent, $txtva, 0, $price_base_type, $info_bits);
			$total_ht  = $tabprice[0];
			$total_tva = $tabprice[1];
			$total_ttc = $tabprice[2];

			$subprice = price2num($pu,'MU');

			// \TODO A virer
			// Anciens indicateurs: $price, $remise (a ne plus utiliser)
			$remise = 0;
			if ($remise_percent > 0)
			{
				$remise = round(($pu * $remise_percent / 100), 2);
			}

			$sql = "INSERT INTO ".MAIN_DB_PREFIX."commande_fournisseurdet";
			$sql.= " (fk_commande,label, description,";
			$sql.= " fk_product, product_type,";
			$sql.= " qty, tva_tx, remise_percent, subprice, remise, ref,";
			$sql.= " total_ht, total_tva, total_ttc";
			$sql.= ")";
			$sql.= " VALUES (".$this->id.", '" . addslashes($label) . "','" . addslashes($desc) . "',";
			if ($fk_product) { $sql.= $fk_product.","; }
			else { $sql.= "null,"; }
			$sql.= "'".$product_type."',";
			$sql.= "'".$qty."', ".$txtva.", ".$remise_percent.",'".price2num($subprice,'MU')."','".price2num($remise)."','".$ref."',";
			$sql.= "'".price2num($total_ht)."',";
			$sql.= "'".price2num($total_tva)."',";
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
	 * 	\brief	Dispatch un element de la commande dans un stock
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

		dol_syslog("CommandeFournisseur::DispatchProduct");

		if ( ($this->statut == 3 || $this->statut == 4 || $this->statut == 5) && $qty > 0)
		{
			$this->db->begin();

			$sql = "INSERT INTO ".MAIN_DB_PREFIX."commande_fournisseur_dispatch ";
			$sql.= " (fk_commande,fk_product, qty, fk_entrepot, fk_user, datec) VALUES ";
			$sql.= " ('".$this->id."','".$product."','".$qty."','".$entrepot."','".$user->id."',".$this->db->idate(mktime()).")";

			$resql = $this->db->query($sql);
			if (! $resql)
	  {
	  	$error = -1;
	  }
	  // Si module stock g�r� et que expedition faite depuis un entrepot
	  if (!$error && $conf->stock->enabled && $entrepot)
	  {
	  	$mouv = new MouvementStock($this->db);
	  	// TODO Add price of product in method or '' to update PMP
	  	$result=$mouv->reception($user, $product, $entrepot, $qty, $price);
	  	if ($result < 0)
	  	{
	  		$this->error=$this->db->error()." - sql=$sql";
	  		dol_syslog("CommandeFournisseur::DispatchProduct".$this->error);
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

			dol_syslog("Fournisseur.commande.class::delete_line sql=".$sql);
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
	 * 		\brief		Delete an order
	 *		\return		int		<0 if KO, >0 if OK
	 */
	function delete()
	{
		global $langs,$conf;

		$err = 0;

		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."commande_fournisseurdet WHERE fk_commande =". $this->id ;
		dol_syslog("FournisseurCommande::delete sql=".$sql, LOG_DEBUG);
		if (! $this->db->query($sql) )
		{
			$err++;
		}

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."commande_fournisseur WHERE rowid =".$this->id;
		dol_syslog("FournisseurCommande::delete sql=".$sql, LOG_DEBUG);
		if ($resql = $this->db->query($sql) )
		{
			if ($this->db->affected_rows($resql) < 1)
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
	function Livraison($user, $date, $type, $comment)
	{
		$result = 0;

		dol_syslog("CommandeFournisseur::Livraison");

		if ($user->rights->fournisseur->commande->receptionner && $date < gmmktime())
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
		}
		return $result ;
	}

	/**     \brief      Cr�� la commande depuis une propale existante
	 \param      user            Utilisateur qui cr�e
	 \param      propale_id      id de la propale qui sert de mod�le
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
		dol_syslog("CommandeFournisseur::UpdateNote");

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
			dol_syslog("CommandeFournisseur::UpdateNote "+$this->error);
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
			dol_syslog("ReadApprobators Erreur");
		}
	}


	/**
	 *      \brief     	Update line
	 *      \param     	rowid           Id de la ligne de facture
	 *      \param     	desc            Description de la ligne
	 *      \param     	pu              Prix unitaire
	 *      \param     	qty             Quantity
	 *      \param     	remise_percent  Pourcentage de remise de la ligne
	 *      \param     	tva_tx          Taux TVA
	 *	  	\param		info_bits		Miscellanous informations
	 *	  	\param		type			Type of line (0=product, 1=service)
	 *      \return    	int             < 0 si erreur, > 0 si ok
	 */
	function updateline($rowid, $desc, $pu, $qty, $remise_percent=0, $txtva, $price_base_type='HT', $info_bits=0, $type=0)
	{
		dol_syslog("CommandeFournisseur::UpdateLine $rowid, $desc, $pu, $qty, $remise_percent, $txtva, $price_base_type, $info_bits, $type");
		include_once(DOL_DOCUMENT_ROOT.'/lib/price.lib.php');

		if ($this->brouillon)
		{
			$this->db->begin();

			// Clean parameters
			$remise_percent=price2num($remise_percent);
			$qty=price2num($qty);
			if (! $qty) $qty=1;
			$pu = price2num($pu);
			$txtva=price2num($txtva);

			// Check parameters
			if ($type < 0) return -1;

			// Calcul du total TTC et de la TVA pour la ligne a partir de
			// qty, pu, remise_percent et txtva
			// TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
			// la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.
			$tabprice=calcul_price_total($qty, $pu, $remise_percent, $txtva, $price_base_type, $info_bits);
			$total_ht  = $tabprice[0];
			$total_tva = $tabprice[1];
			$total_ttc = $tabprice[2];

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
			$sql.= " description='".addslashes($desc)."'";
			$sql.= ",subprice='".price2num($subprice)."'";
			$sql.= ",remise='".price2num($remise)."'";
			$sql.= ",remise_percent='".price2num($remise_percent)."'";
			$sql.= ",tva_tx='".price2num($txtva)."'";
			$sql.= ",qty='".price2num($qty)."'";
			if ($date_end) { $sql.= ",date_start='$date_end'"; }
			else { $sql.=',date_start=null'; }
			if ($date_end) { $sql.= ",date_end='$date_end'"; }
			else { $sql.=',date_end=null'; }
			$sql.= ",info_bits='".$info_bits."'";
			$sql.= ",total_ht='".price2num($total_ht)."'";
			$sql.= ",total_tva='".price2num($total_tva)."'";
			$sql.= ",total_ttc='".price2num($total_ttc)."'";
			$sql.= ",product_type='".$type."'";
			$sql.= " WHERE rowid = ".$rowid;

			dol_syslog("CommandeFournisseur::updateline sql=".$sql);
			$result = $this->db->query( $sql);
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
				dol_syslog("CommandeFournisseur::updateline ".$this->error);
				$this->db->rollback();
				return -1;
			}
		}
		else
		{
			$this->error="Order status makes operation forbidden";
			dol_syslog("CommandeFournisseur::updateline ".$this->error);
			return -2;
		}
	}


	/**
	 *		\brief		Initialise la commande avec valeurs fictives al�atoire
	 *					Sert � g�n�rer une commande pour l'aperu des mod�les ou demo
	 */
	function initAsSpecimen()
	{
		global $user,$langs;

		dol_syslog("CommandeFournisseur::initAsSpecimen");

		// Charge tableau des id de soci�t� socids
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

		// Initialise param�tres
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
	var $desc;          	// Description ligne
	var $fk_product;		// Id of predefined product
	var $product_type = 0;	// Type 0 = product, 1 = Service
	var $total_ht;
	var $total_tva;
	var $total_ttc;

	// From llx_product
	var $libelle;       // Label produit
	var $product_desc;  // Description produit

	// From llx_product_fournisseur
	var $ref_fourn;     // Ref supplier


	/**
	 * Constructor
	 */
	function CommandeFournisseurLigne($DB)
	{
		$this->db= $DB;
	}

	/**
	 *  \brief     Load line order
	 *  \param     rowid           id line order
	 */
	function fetch($rowid)
	{
		$sql = 'SELECT cd.rowid, cd.fk_commande, cd.fk_product, cd.product_type, cd.description, cd.qty, cd.tva_tx,';
		$sql.= ' cd.remise, cd.remise_percent, cd.subprice,';
		$sql.= ' cd.info_bits, cd.total_ht, cd.total_tva, cd.total_ttc,';
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
			$this->remise           = $objp->remise;
			$this->remise_percent   = $objp->remise_percent;
			$this->produit_id       = $objp->fk_product;
			$this->info_bits        = $objp->info_bits;
			$this->total_ht         = $objp->total_ht;
			$this->total_tva        = $objp->total_tva;
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
	 *      \brief     	Mise a jour de l'objet ligne de commande en base
	 *		\return		int		<0 si ko, >0 si ok
	 */
	function update_total()
	{
		$this->db->begin();

		// Mise a jour ligne en base
		$sql = "UPDATE ".MAIN_DB_PREFIX."commande_fournisseurdet SET";
		$sql.= " total_ht='".price2num($this->total_ht)."'";
		$sql.= ",total_tva='".price2num($this->total_tva)."'";
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
			dol_syslog("CommandeFournisseurLigne.class.php::update_total Error ".$this->error);
			$this->db->rollback();
			return -2;
		}
	}
}

?>
