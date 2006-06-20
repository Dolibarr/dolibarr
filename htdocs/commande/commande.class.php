<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006 Regis Houssin        <regis.houssin@cap-networks.com>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
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
        \file       htdocs/commande/commande.class.php
        \ingroup    commande
        \brief      Fichier des classes de commandes
        \version    $Revision$
*/

require_once(DOL_DOCUMENT_ROOT ."/commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");
require_once(DOL_DOCUMENT_ROOT ."/contact.class.php");

 
/**
        \class      Commande
        \brief      Classe de gestion de commande
*/
class Commande extends CommonObject
{
	var $db ;
	var $element='commande';

	var $id ;
	var $ref;
	var $ref_client;
	var $socidp;
	var $contactid;
	var $projet_id;
	var $statut;
	var $facturee;
	var $brouillon;
	var $cond_reglement_id;
	var $cond_reglement_code;
	var $mode_reglement_id;
	var $mode_reglement_code;
	var $adresse_livraison_id;
	var $date;				// Date commande
	var $date_livraison;	// Date livraison souhaitée
	var $remise_percent;
	var $remise_absolue;
	
	var $lines = array();

	// Pour board
	var $nbtodo;
	var $nbtodolate;

	/**
	 *        \brief      Constructeur
	 *        \param      DB      Handler d'accès base
	 */
	function Commande($DB, $socidp="", $commandeid=0)
	{
		global $langs;
		$langs->load('orders');
		$this->db = $DB;
		$this->socidp = $socidp;
    	$this->id = $commandeid;

		$this->sources[0] = $langs->trans('OrderSource0');
		$this->sources[1] = $langs->trans('OrderSource1');
		$this->sources[2] = $langs->trans('OrderSource2');
		$this->sources[3] = $langs->trans('OrderSource3');
		$this->sources[4] = $langs->trans('OrderSource4');
		$this->sources[5] = $langs->trans('OrderSource5');

		$this->remise = 0;
		$this->remise_percent = 0;

		$this->products = array();
	}

    /**     \brief      Créé la commande depuis une propale existante
            \param      user            Utilisateur qui crée
            \param      propale_id      id de la propale qui sert de modèle
    */
	function create_from_propale($user, $propale_id)
	{
		dolibarr_syslog("Commande.class.php::create_from_propale propale_id=$propale_id");

		$propal = new Propal($this->db);
		$propal->fetch($propale_id);

		$this->lines = array();
		$this->date_commande = time();
		$this->source = 0;
		for ($i = 0 ; $i < sizeof($propal->lignes) ; $i++)
		{
			$CommLigne = new CommandeLigne($this->db);
			$CommLigne->libelle           = $propal->lignes[$i]->libelle;
			$CommLigne->desc              = $propal->lignes[$i]->desc;
			$CommLigne->price             = $propal->lignes[$i]->price;
			$CommLigne->subprice          = $propal->lignes[$i]->subprice;
			$CommLigne->tva_tx            = $propal->lignes[$i]->tva_tx;
			$CommLigne->qty               = $propal->lignes[$i]->qty;
			$CommLigne->remise_percent    = $propal->lignes[$i]->remise_percent;
			$CommLigne->fk_product        = $propal->lignes[$i]->product_id;
			$this->lines[$i] = $CommLigne;
		}

		$this->socidp               = $propal->socidp;
		$this->projetid             = $propal->projetidp;
		$this->cond_reglement_id    = $propal->cond_reglement_id;
		$this->mode_reglement_id    = $propal->mode_reglement_id;
		$this->date_livraison       = $propal->date_livraison;
		$this->adresse_livraison_id = $propal->adresse_livraison_id;
		$this->contact_id           = $propal->contactid;
		$this->ref_client           = $propal->ref_client;
    
		/* Définit la société comme un client */
		$soc = new Societe($this->db);
		$soc->id = $this->socidp;
		$soc->set_as_client();
		$this->propale_id = $propal->id;

		return $this->create($user);
	}

  /**   \brief      Valide la commande
        \param      user            Utilisateur qui valide
   */
	function valid($user)
	{
		$result = 0;
		global $conf;
		if ($user->rights->commande->valider)
		{
			if (defined('COMMANDE_ADDON'))
			{
				if (is_readable(DOL_DOCUMENT_ROOT .'/includes/modules/commande/'.COMMANDE_ADDON.'.php'))
				{
					require_once DOL_DOCUMENT_ROOT .'/includes/modules/commande/'.COMMANDE_ADDON.'.php';

					// Definition du nom de module de numerotation de commande

					// \todo  Normer le nom des classes des modules de numérotation de ref de commande avec un nom du type NumRefCommandesXxxx
					//
					//$list=split('_',COMMANDE_ADDON);
					//$numrefname=$list[2];
					//$modName = 'NumRefCommandes'.ucfirst($numrefname);
					$modName=COMMANDE_ADDON;

					// Recuperation de la nouvelle reference
					$objMod = new $modName($this->db);
					$soc = new Societe($this->db);
					$soc->fetch($this->socidp);
					
					// on vérifie si la commande est en numérotation provisoire
					$comref = substr($this->ref, 1, 4);
					if ($comref == PROV)
					{
						$num = $objMod->commande_get_num($soc);
					}
					else
					{
						$num = $this->ref;
					}

					$sql = 'UPDATE '.MAIN_DB_PREFIX."commande SET ref='$num', fk_statut = 1, date_valid=now(), fk_user_valid=$user->id";
					$sql .= " WHERE rowid = $this->id AND fk_statut = 0 ;";

					if ($this->db->query($sql) )
					{
							// On efface le répertoire de pdf provisoire
							$comref = sanitize_string($this->ref);
							if ($conf->commande->dir_output)
							{
								$dir = $conf->commande->dir_output . "/" . $comref ;
								$file = $conf->commande->dir_output . "/" . $comref . "/" . $comref . ".pdf";
								if (file_exists($file))
								{
									commande_delete_preview($this->db, $this->id, $this->ref);
									
									if (!dol_delete_file($file))
									{
                    $this->error=$langs->trans("ErrorCanNotDeleteFile",$file);
                    return 0;
                  }
                }
                if (file_exists($dir))
                {
                	if (!dol_delete_dir($dir))
                  {
                  	$this->error=$langs->trans("ErrorCanNotDeleteDir",$dir);
                    return 0;
                  }
                }
               }
	    			$result = 1;
					}
					else
					{
						$result = -1;
						dolibarr_print_error($this->db);
					}

				}
				else
				{
					print 'Impossible de lire le module de numérotation';
				}
			}
			else
			{
				print 'Le module de numérotation n\'est pas défini' ;
			}
		}
		return $result ;
	}
	
	/**
   *
   *
   */
    function reopen($userid)
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."commande SET fk_statut = 0";
    
        $sql .= " WHERE rowid = $this->id;";
    
        if ($this->db->query($sql) )
        {
            return 1;
        }
        else
        {
            dolibarr_print_error($this->db);
        }
    }
	
  /**
   *    \brief      Cloture la commande
   *    \param      user        Objet utilisateur qui cloture
   *    \return     int         <0 si ko, >0 si ok
   */
	function cloture($user)
	{
		global $conf;
		if ($user->rights->commande->valider)
		{
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'commande';
			$sql.= ' SET fk_statut = 3,';
			$sql.= ' fk_user_cloture = '.$user->id.',';
			$sql.= ' date_cloture = now()';
			$sql.= " WHERE rowid = $this->id AND fk_statut > 0 ;";

			if ($this->db->query($sql) )
			{
				if($conf->stock->enabled && $conf->global->PRODUIT_SOUSPRODUITS == 1)
							{
								require_once(DOL_DOCUMENT_ROOT."/product/stock/mouvementstock.class.php");
								for ($i = 0 ; $i < sizeof($this->lignes) ; $i++)
								{
										$prod = new Product($this->db, $this->lignes[$i]->fk_product);
										$prod -> get_sousproduits_arbo ();
										$prods_arbo = $prod->get_each_prod();
										if(sizeof($prods_arbo) > 0)
										{
											foreach($prods_arbo as $key => $value)
											{
													// on décompte le stock de tous les sousproduits
													$mouvS = new MouvementStock($this->db);
													$entrepot_id = "1";
                            						$result=$mouvS->livraison($user, $value[1], $entrepot_id, $value[0]);
													
											}
										}
										// on décompte pas le stock du produit principal, ça serait fait manuellement avec l'expédition
										// $result=$mouvS->livraison($user, $this->lignes[$i]->fk_product, $entrepot_id, $this->lignes[$i]->qty);
									}
							
							}
				return 1;
			}
			else
			{
				dolibarr_print_error($this->db);
				return -1;
			}
		}
	}
  /**
   * Annule la commande
   *
   */
	function cancel($user)
	{
		if ($user->rights->commande->valider)
		{
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'commande SET fk_statut = -1';
			$sql .= " WHERE rowid = $this->id AND fk_statut = 1 ;";

			if ($this->db->query($sql) )
			{
				return 1;
			}
			else
			{
				dolibarr_print_error($this->db);
			}
		}
	}

	/**
	 * 		\brief		Créé la commande
	 *		\param		user		Objet utilisateur qui crée
	 */
	function create($user)
	{
		global $conf,$langs,$mysoc;

		// Nettoyage parametres
		$this->brouillon = 1;		// On positionne en mode brouillon la commande

		dolibarr_syslog("Commande.class.php::create");

		// Vérification paramètres
		if ($this->source < 0)
		{
			$this->error=$langs->trans("ErrorFieldRequired",$langs->trans("Source"));
			dolibarr_syslog("Commande.class.php::create ".$this->error, LOG_ERR);
			return -1;
		}
		if (! $remise) $remise=0;
		if (! $this->projetid) $this->projetid = 0;

		$soc = new Societe($this->db);
		$result=$soc->fetch($this->socidp);
		if ($result < 0)
		{
			$this->error="Failed to fetch company";
			dolibarr_syslog("Commande.class.php::create ".$this->error, LOG_ERR);
			return -2;
		}

        $this->db->begin();
	
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'commande (';
		$sql.= 'fk_soc, date_creation, fk_user_author, fk_projet, date_commande, source, note_public, ref_client,';
		$sql.= ' model_pdf, fk_cond_reglement, fk_mode_reglement, date_livraison, fk_adresse_livraison,';
		$sql.= ' remise_absolue, remise_percent)';
		$sql.= ' VALUES ('.$this->socidp.', now(), '.$user->id.', '.$this->projetid.',';
		$sql.= ' '.$this->db->idate($this->date_commande).',';
		$sql.= ' '.$this->source.', ';
		$sql.= " '".addslashes($this->note)."', ";
		$sql.= " '".addslashes($this->ref_client)."', '".$this->modelpdf."', '".$this->cond_reglement_id."', '".$this->mode_reglement_id."',";
		$sql.= ' '.($this->date_livraison?$this->db->idate($this->date_livraison):'null').',';
		$sql.= " '".$this->adresse_livraison_id."',";
		$sql.= " '".$this->remise_absolue."',";
		$sql.= " '".$this->remise_percent."')";

		dolibarr_syslog("Commande.class.php::create sql=".$sql);
		
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'commande');

            if ($this->id)
            {
				/*
				 *  Insertion des produits dans la base
				 */
				for ($i = 0 ; $i < sizeof($this->lines) ; $i++)
				{
					$resql = $this->addline(
						$this->id,
						$this->lines[$i]->desc,
						$this->lines[$i]->price,
						$this->lines[$i]->qty,
						$this->lines[$i]->tva_tx,
						$this->lines[$i]->fk_product,
						$this->lines[$i]->remise_percent
						);

					if ($resql < 0)
					{
						$this->error=$this->db->error;
						dolibarr_print_error($this->db);
						break;
					}
				}
				
				// Mise a jour ref
				$sql = 'UPDATE '.MAIN_DB_PREFIX."commande SET ref='(PROV".$this->id.")' WHERE rowid=".$this->id;
				if ($this->db->query($sql))
				{
					if ($this->id && $this->propale_id)
					{
						$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'co_pr (fk_commande, fk_propale) VALUES ('.$this->id.','.$this->propale_id.')';
						$this->db->query($sql);
					}

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
		else
		{
			dolibarr_print_error($this->db);
	        $this->db->rollback();
			return -1;
		}
	}


    /**
     *    	\brief     	Ajout d'un produit dans la commande, en base
	 * 		\param    	commandeid      id de la commande
	 * 		\param    	desc            description de la ligne
	 * 		\param    	pu              prix unitaire
	 * 		\param    	qty             quantité
	 * 		\param    	txtva           taux de tva forcé, sinon -1
	 *		\param    	fk_product      id du produit/service predéfini
	 * 		\param    	remise_percent  pourcentage de remise de la ligne
     *    	\return    	int             >0 si ok, <0 si ko
     *    	\see       	add_product
	 * 		\remarks	Les parametres sont deja censé etre juste et avec valeurs finales a l'appel
	 *					de cette methode. Aussi, pour le taux tva, il doit deja avoir ete défini
	 *					par l'appelant par la methode get_default_tva(societe_vendeuse,societe_acheteuse,taux_produit)
 	 *					et le desc doit deja avoir la bonne valeur (a l'appelant de gerer le multilangue)
     */
    function addline($commandeid, $desc, $pu, $qty, $txtva, $fk_product=0, $remise_percent=0)
    {
    	dolibarr_syslog("Commande.class.php::addline this->id=$this->id, $commandeid, $desc, $pu, $qty, $txtva, $fk_product, $remise_percent");
		include_once(DOL_DOCUMENT_ROOT.'/lib/price.lib.php');

        if ($this->statut == 0)
        {
			$this->db->begin();

			// Nettoyage paramètres
            $remise_percent=price2num($remise_percent);
            $qty=price2num($qty);
			if (! $qty) $qty=1;
            $pu = price2num($pu);
			$txtva = price2num($txtva);

			// Calcul du total TTC et de la TVA pour la ligne a partir de
			// qty, pu, remise_percent et txtva
			// TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker 
			// la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.
			$tabprice=calcul_price_total($qty, $pu, $remise_percent, $txtva);
			$total_ht  = $tabprice[0];
			$total_tva = $tabprice[1];
			$total_ttc = $tabprice[2];

			// Anciens indicateurs: $price, $subprice, $remise (a ne plus utiliser)
            $price = $pu;
            $subprice = $pu;
            if ($remise_percent > 0)
            {
                $remise = round(($pu * $remise_percent / 100), 2);
                $price = $pu - $remise;
            }

			// Récupère rang max de la commande dans $rangmax
			$sql = 'SELECT max(rang) FROM '.MAIN_DB_PREFIX.'commandedet';
			$sql.= ' WHERE fk_commande ='.$commandeid;
			$resql = $this->db->query($sql);
			if ($resql)
			{
				$row = $this->db->fetch_row($resql);
				$rangmax = $row[0];
			}
			else
			{
				dolibarr_print_error($this->db);
				$this->db->rollback();
				return -1;
			}

			// Insertion ligne
			$ligne=new CommandeLigne($this->db);

			$ligne->fk_commande=$commandeid;
			$ligne->desc=$desc;
			$ligne->price=$price;
			$ligne->qty=$qty;
			$ligne->tva_tx=$txtva;
			$ligne->fk_product=$fk_product;
			$ligne->remise_percent=$remise_percent;
			$ligne->subprice=$subprice;
			$ligne->remise=$remise;
			$ligne->rang=($rangmax+1);
			$ligne->info_bits=$info_bits;
			$ligne->total_ht=$total_ht;
			$ligne->total_tva=$total_tva;
			$ligne->total_ttc=$total_ttc;

			$result=$ligne->insert();			
			if ($result > 0)
            {
				// Mise a jour informations denormalisees au niveau de la facture meme
				$result=$this->update_price($this->id);

                if ($result > 0)
                {
					$this->db->commit();
					return 1;
                }
                else
                {
	            	$this->error=$this->db->error();
	            	dolibarr_syslog("Error sql=$sql, error=".$this->error);
					$this->db->rollback();
					return -1;
                }
            }
            else
            {
            	$this->error=$ligne->error;
				$this->db->rollback();
                return -2;
            }
        }
    }


	/**
 	 * Ajoute un produit dans la commande
	 *
	 */
	function add_product($idproduct, $qty, $remise_percent=0)
	{
		global $conf;

		if (!$qty) $qty = 1 ;
			
		if ($idproduct > 0)
		{
			$ligne=new CommandeLigne($this->db);
			$ligne->fk_product=$idproduct;
			$ligne->qty=$qty;
			$ligne->remise_percent=$remise_percent;
			
			$i = sizeof($this->lines);
			$this->lines[$i] = $ligne;

			/** POUR AJOUTER AUTOMATIQUEMENT LES SOUSPRODUITS À LA COMMANDE
			if($conf->global->PRODUIT_SOUSPRODUITS == 1)
			{
				$prod = new Product($this->db, $idproduct);
				$prod -> get_sousproduits_arbo ();
				$prods_arbo = $prod->get_each_prod();
				if(sizeof($prods_arbo) > 0)
				{
					foreach($prods_arbo as $key => $value)
					{
						// print "id : ".$value[1].' :qty: '.$value[0].'<br>';
						if(! in_array($value[1],$this->products))
							$this->add_product($value[1], $value[0]);
					
					}
				}
			
			}
			**/
		}
	}
	
 /**
	 *      \brief      Stocke un numéro de rang pour toutes les lignes de
	 *                  detail d'une commande qui n'en ont pas.
	 */
	function line_order()
	{
		$sql = 'SELECT count(rowid) FROM '.MAIN_DB_PREFIX.'commandedet';
		$sql .= ' WHERE fk_commande='.$this->id;
		$sql .= ' AND rang = 0';
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$row = $this->db->fetch_row($resql);
			$nl = $row[0];
		}
		if ($nl > 0)
		{
			$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'commandedet';
			$sql .= ' WHERE fk_commande='.$this->id;
			$sql .= ' ORDER BY rang ASC, rowid ASC';
			$resql = $this->db->query($sql);
			if ($resql)
			{
				$num = $this->db->num_rows($resql);
				$i = 0;
				while ($i < $num)
				{
					$row = $this->db->fetch_row($resql);
					$li[$i] = $row[0];
					$i++;
				}
			}
			for ($i = 0 ; $i < sizeof($li) ; $i++)
			{
				$sql = 'UPDATE '.MAIN_DB_PREFIX.'commandedet SET rang = '.($i+1);
				$sql .= ' WHERE rowid = '.$li[$i];
				if (!$this->db->query($sql) )
				{
					dolibarr_syslog($this->db->error());
				}
			}
		}
	}

	function line_up($rowid)
	{
		$this->line_order();

		/* Lecture du rang de la ligne */
		$sql = 'SELECT rang FROM '.MAIN_DB_PREFIX.'commandedet';
		$sql .= ' WHERE rowid ='.$rowid;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$row = $this->db->fetch_row($resql);
			$rang = $row[0];
		}

		if ($rang > 1 )
		{
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'commandedet SET rang = '.$rang ;
			$sql .= ' WHERE fk_commande  = '.$this->id;
			$sql .= ' AND rang = '.($rang - 1);
			if ($this->db->query($sql) )
			{
				$sql = 'UPDATE '.MAIN_DB_PREFIX.'commandedet SET rang  = '.($rang - 1);
				$sql .= ' WHERE rowid = '.$rowid;
				if (! $this->db->query($sql) )
				{
					dolibarr_print_error($this->db);
				}
			}
			else
			{
				dolibarr_print_error($this->db);
			}
		}
	}

	function line_down($rowid)
	{
		$this->line_order();

		/* Lecture du rang de la ligne */
		$sql = 'SELECT rang FROM '.MAIN_DB_PREFIX.'commandedet';
		$sql .= ' WHERE rowid ='.$rowid;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$row = $this->db->fetch_row($resql);
			$rang = $row[0];
		}

		/* Lecture du rang max de la facture */
		$sql = 'SELECT max(rang) FROM '.MAIN_DB_PREFIX.'commandedet';
		$sql .= ' WHERE fk_commande ='.$this->id;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$row = $this->db->fetch_row($resql);
			$max = $row[0];
		}

		if ($rang < $max )
		{
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'commandedet SET rang = '.$rang;
			$sql .= ' WHERE fk_commande  = '.$this->id;
			$sql .= ' AND rang = '.($rang+1);
			if ($this->db->query($sql) )
			{
				$sql = 'UPDATE '.MAIN_DB_PREFIX.'commandedet SET rang = '.($rang+1);
				$sql .= ' WHERE rowid = '.$rowid;
				if (! $this->db->query($sql) )
				{
					dolibarr_print_error($this->db);
				}
			}
			else
			{
				dolibarr_print_error($this->db);
			}
		}
	}

    /**
     *    \brief      Recupère de la base les caractéristiques d'une commande
     *    \param      rowid       id de la commande à récupérer
     */
	function fetch($id)
	{
		$sql = 'SELECT c.rowid, c.date_creation, c.ref, c.fk_soc, c.fk_user_author, c.fk_statut, c.amount_ht, c.total_ht, c.total_ttc, c.tva, c.fk_cond_reglement, c.fk_mode_reglement,';
		$sql.= ' '.$this->db->pdate('c.date_commande').' as date_commande, '.$this->db->pdate('c.date_livraison').' as date_livraison,';
		$sql.= ' c.fk_projet, c.remise_percent, c.remise, c.remise_absolue, c.source, c.facture as facturee, c.note, c.note_public, c.ref_client, c.model_pdf, c.fk_adresse_livraison';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'commande as c';
		$sql.= ' WHERE c.rowid = '.$id;
	
		dolibarr_syslog("Commande::fetch sql=$sql");
	
		$result = $this->db->query($sql) ;
		if ($result)
		{
			$obj = $this->db->fetch_object($result);
			if ($obj)
			{
				$this->id                   = $obj->rowid;
				$this->ref                  = $obj->ref;
				$this->ref_client           = $obj->ref_client;
				$this->socidp               = $obj->fk_soc;
				$this->statut               = $obj->fk_statut;
				$this->user_author_id       = $obj->fk_user_author;
				$this->total_ht             = $obj->total_ht;
				$this->total_tva            = $obj->tva;
				$this->total_ttc            = $obj->total_ttc;
				$this->date                 = $obj->date_commande;
				$this->remise               = $obj->remise;
				$this->remise_percent       = $obj->remise_percent;
				$this->remise_absolue       = $obj->remise_absolue;
				$this->source               = $obj->source;
				$this->facturee             = $obj->facturee;
				$this->note                 = $obj->note;
				$this->note_public          = $obj->note_public;
				$this->projet_id            = $obj->fk_projet;
				$this->modelpdf             = $obj->model_pdf;
				$this->cond_reglement_id    = $obj->fk_cond_reglement;
				$this->mode_reglement_id    = $obj->fk_mode_reglement;
				$this->date_livraison       = $obj->date_livraison;
				$this->adresse_livraison_id = $obj->fk_adresse_livraison;
				if ($this->statut == 0) $this->brouillon = 1;
		
				$this->db->free();
		
				if ($this->cond_reglement_id)
				{
					$sql = "SELECT rowid, libelle, code";
					$sql.= " FROM ".MAIN_DB_PREFIX."cond_reglement";
					$sql.= " WHERE rowid = ".$this->cond_reglement_id;
		
					$resqlcond = $this->db->query($sql);
		
					if ($resqlcond)
					{
						$objc = $this->db->fetch_object($resqlcond);
						$this->cond_reglement      = $objc->libelle;
						$this->cond_reglement_code = $objc->code;
					}
				}
		
				if ($this->user_author_id)
				{
					$sql = "SELECT name, firstname";
					$sql.= " FROM ".MAIN_DB_PREFIX."user";
					$sql.= " WHERE rowid = ".$this->user_author_id;
		
					$resqluser = $this->db->query($sql);
		
					if ($resqluser)
					{
						$obju = $this->db->fetch_object($resqluser);
						$this->user_author_name      = $obju->name;
						$this->user_author_firstname = $obju->firstname;
					}
				}
		
	
				$this->lignes = $this->fetch_lignes();
		
				/*
				* Propale associée
				*/
				$sql = 'SELECT cp.fk_propale';
				$sql .= ' FROM '.MAIN_DB_PREFIX.'co_pr as cp';
				$sql .= ' WHERE cp.fk_commande = '.$this->id;
				if ($this->db->query($sql))
				{
					if ($this->db->num_rows())
					{
						$obj = $this->db->fetch_object();
						$this->propale_id = $obj->fk_propale;
					}
					return 1;
				}
				else
				{
					dolibarr_print_error($this->db);
					return -1;
				}
			}
			else
			{
				$this->error="Order not found";
				return -2;	
			}
		}
		else
		{
			dolibarr_print_error($this->db);
			return -3;
		}
	}

	
	/*
	*
	*
	*
	*/
	function set_pdf_model($user, $modelpdf)
	{
      if ($user->rights->commande->creer)
	     {

	      $sql = "UPDATE ".MAIN_DB_PREFIX."commande SET model_pdf = '$modelpdf'";
	      $sql .= " WHERE rowid = $this->id AND fk_statut < 2 ;";
	  
	     if ($this->db->query($sql) )
	      {
	        return 1;
	      }
	     else
	     {
    	  dolibarr_print_error($this->db);
	      return 0;
	     }
	  }
  }
		
    /**
     *      \brief      Reinitialise le tableau lignes
     *		\param		only_product	Ne renvoie que ligne liées à des produits physiques prédéfinis
     *		\return		array			Tableau de CommandeLigne
     */
	function fetch_lignes($only_product=0)
	{
		$this->lignes = array();
		$sql = 'SELECT l.rowid, l.fk_product, l.fk_commande, l.description, l.price, l.qty, l.tva_tx,';
		$sql.= ' l.remise_percent, l.subprice, l.rang, l.coef, l.label,';
		$sql.= ' p.ref as product_ref, p.description as product_desc';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'commandedet as l';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON (p.rowid = l.fk_product)';
		$sql.= ' WHERE l.fk_commande = '.$this->id;
		if ($only_product==1) $sql .= ' AND p.fk_product_type = 0';
		$sql .= ' ORDER BY l.rang';

		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows();
			$i = 0;
			while ($i < $num)
			{
				$objp = $this->db->fetch_object($result);

				$ligne = new CommandeLigne($this->db);
				$ligne->rowid          = $objp->rowid;
				$ligne->id             = $objp->rowid;				// \deprecated
				$ligne->fk_commande    = $objp->fk_commande;
				$ligne->commande_id    = $objp->fk_commande;		// \deprecated
				$ligne->desc           = $objp->description;  // Description ligne
				$ligne->qty            = $objp->qty;
				$ligne->tva_tx         = $objp->tva_tx;
				$ligne->subprice       = $objp->subprice;
				$ligne->remise_percent = $objp->remise_percent;
				$ligne->price          = $objp->price;
				$ligne->fk_product     = $objp->fk_product;
				$ligne->coef           = $objp->coef;
				$ligne->rang           = $objp->rang;

				$ligne->libelle        = $objp->label;        // Label produit
				$ligne->product_desc   = $objp->product_desc; // Description produit
				$ligne->ref            = $objp->product_ref;

				$this->lignes[$i] = $ligne;
				$i++;
			}
			$this->db->free();
		}
		return $this->lignes;
	}
	
	
	/**
   *
   *
   */
	 
  function fetch_client()
    {
      $client = new Societe($this->db);
      $client->fetch($this->socidp);
      $this->client = $client;
    }
	
	/**
   *
   *
   */
	 
  function fetch_adresse_livraison($id)
    {
    	$idadresse = $id;
      $adresse = new Societe($this->db);
      $adresse->fetch_adresse_livraison($idadresse);
      $this->adresse = $adresse;
    }


    /**
     *      \brief      Renvoie un tableau avec les expéditions par ligne
     *      \param      filtre_statut       Filtre sur statut
     *      \return     int                 0 si OK, <0 si KO
     */
	function expedition_array($filtre_statut=-1)
	{
		$this->livraisons = array();
		$sql = 'SELECT fk_product, sum(ed.qty)';
		$sql.=' FROM '.MAIN_DB_PREFIX.'expeditiondet as ed, '.MAIN_DB_PREFIX.'expedition as e, '.MAIN_DB_PREFIX.'commande as c, '.MAIN_DB_PREFIX.'commandedet as cd';
		$sql.=' WHERE ed.fk_expedition = e.rowid AND ed.fk_commande_ligne = cd .rowid AND cd.fk_commande = c.rowid';
		$sql.=' AND cd.fk_commande =' .$this->id;
		if ($filtre_statut >= 0) $sql.=' AND e.fk_statut = '.$filtre_statut;
		$sql .= ' GROUP BY fk_product ';
		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows();
			$i = 0;
			while ($i < $num)
			{
				$row = $this->db->fetch_row( $i);
				$this->expeditions[$row[0]] = $row[1];
				$i++;
			}
			$this->db->free();
		}
	
	    return 0;
	}
	
  /**
   * Renvoie un tableau avec les expeditions par ligne
   *
   */
	function nb_expedition()
	{
		$sql = 'SELECT count(*) FROM '.MAIN_DB_PREFIX.'expedition as e';
		$sql .=" WHERE e.fk_commande = $this->id";

		$result = $this->db->query($sql);
		if ($result)
		{
			$row = $this->db->fetch_row(0);
			return $row[0];
		}
	}
	
	    /**
     *      \brief      Renvoie un tableau avec les livraisons par ligne
     *      \param      filtre_statut       Filtre sur statut
     *      \return     int                 0 si OK, <0 si KO
     */
	function livraison_array($filtre_statut=-1)
	{
		$this->livraisons = array();
		$sql = 'SELECT cd.fk_product, sum(ed.qty)';
		$sql.=' FROM '.MAIN_DB_PREFIX.'livraisondet as ld, '.MAIN_DB_PREFIX.'livraison as l, '.MAIN_DB_PREFIX.'commande as c, '.MAIN_DB_PREFIX.'commandedet as cd';
		$sql.=' WHERE ld.fk_livraison = l.rowid AND ld.fk_commande_ligne = cd .rowid AND cd.fk_commande = c.rowid';
		$sql.=' AND cd.fk_commande =' .$this->id;
		if ($filtre_statut >= 0) $sql.=' AND l.fk_statut = '.$filtre_statut;
		$sql .= ' GROUP BY cd.fk_product ';
		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows();
			$i = 0;
			while ($i < $num)
			{
				$row = $this->db->fetch_row( $i);
				$this->livraisons[$row[0]] = $row[1];
				$i++;
			}
			$this->db->free();
		}
	
	    return 0;
	}
	
  /**
   *    \brief      Supprime une ligne de la commande
   *    \param      idligne     Id de la ligne à supprimer
   *    \return     int         >0 si ok, <0 si ko
   */
	function delete_line($idligne)
	{
		if ($this->statut == 0)
		{
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX."commandedet WHERE rowid = $idligne";
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
	}

	/**
	 * 		\brief     	Applique une remise relative
	 * 		\param     	user		User qui positionne la remise
	 * 		\param     	remise
	 *		\return		int 		<0 si ko, >0 si ok
	 */
	function set_remise($user, $remise)
	{
		$remise=trim($remise)?trim($remise):0;

		if ($user->rights->commande->creer)
		{
			$remise=price2num($remise);

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'commande';
			$sql.= ' SET remise_percent = '.$remise;
			$sql.= ' WHERE rowid = '.$this->id.' AND fk_statut = 0 ;';

			if ($this->db->query($sql))
			{
				$this->remise_percent = $remise;
				$this->update_price($this->id);
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
	 * 		\brief     	Applique une remise absolue
	 * 		\param     	user 		User qui positionne la remise
	 * 		\param     	remise
	 *		\return		int 		<0 si ko, >0 si ok
	 */
	function set_remise_absolue($user, $remise)
	{
		$remise=trim($remise)?trim($remise):0;
		
		if ($user->rights->commande->creer)
		{
			$remise=price2num($remise);
			
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'commande';
			$sql.= ' SET remise_absolue = '.$remise;
			$sql.= ' WHERE rowid = '.$this->id.' AND fk_statut = 0 ;';

			dolibarr_syslog("Commande::set_remise_absolue sql=$sql");

			if ($this->db->query($sql))
			{
				$this->remise_absolue = $remise;
				$this->update_price($this->id);
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
     *    \brief      Mets à jour le prix total de la commnde
     *    \return     int     <0 si ko, >0 si ok
     */
    function update_price()
    {
		include_once(DOL_DOCUMENT_ROOT.'/lib/price.lib.php');

        // Liste des lignes factures a sommer
        $sql = "SELECT price, qty, tva_tx, total_ht, total_tva, total_ttc";
        $sql.= " FROM ".MAIN_DB_PREFIX."commandedet";
        $sql.= " WHERE fk_commande = ".$this->id;

		dolibarr_syslog("Commande.class.php::update_price this->id=".$this->id);
		
		$result = $this->db->query($sql);
		if ($result)
        {
			$this->total_ht  = 0;
			$this->total_tva = 0;
			$this->total_ttc = 0;
			
            $num = $this->db->num_rows($result);
            $i = 0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($result);

				$this->total_ht    += $obj->total_ht;
				$this->total_tva   += ($obj->total_ttc - $obj->total_ht);
				$this->total_ttc   += $obj->total_ttc;

				// Anciens indicateurs
				$this->amount_ht      += $obj->price * $obj->qty;
				$this->total_remise   += 0;		// Plus de remise globale (toute remise est sur une ligne)
/* \deprecated car simplifie par les 3 indicateurs total_ht, total_tva et total_ttc sur lignes
                $products[$i][0] = $obj->price;
                $products[$i][1] = $obj->qty;
                $products[$i][2] = $obj->tva_tx;
*/
                $i++;
            }

			$this->db->free($result);
        }
/* \deprecated car simplifie par les 3 indicateurs total_ht, total_tva et total_ttc sur lignes
        $calculs = calcul_price($products, $this->remise_percent, $this->remise_absolue);
        $this->total_remise   = $calculs[3];
		$this->amount_ht      = $calculs[4];
        $this->total_ht       = $calculs[0];
        $this->total_tva      = $calculs[1];
        $this->total_ttc      = $calculs[2];
		$tvas                 = $calculs[5];
*/
        // Met a jour en base
        $sql = "UPDATE ".MAIN_DB_PREFIX."commande SET";
        $sql .= "  amount_ht='".price2num($this->total_ht)."'";
        $sql .= ", total_ht='". price2num($this->total_ht)."'";
        $sql .= ", tva='".      price2num($this->total_tva)."'";
        $sql .= ", total_ttc='".price2num($this->total_ttc)."'";
        $sql .= ", remise='".price2num($this->total_remise)."'";
        $sql .=" WHERE rowid = ".$this->id;
        if ( $this->db->query($sql) )
        {
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
			dolibarr_syslog("Commande::update_price error=".$this->error);
            return -1;
        }
    }

	/**
 	 *    \brief      Mets à jour les commentaires privés
	 *    \param      note        	Commentaire
	 *    \return     int         	<0 si ko, >0 si ok
	 */
	function update_note($note)
	{
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'commande';
		$sql.= " SET note = '".addslashes($note)."'";
		$sql.= " WHERE rowid =". $this->id;

		if ($this->db->query($sql))
		{
			$this->note = $note;
			return 1;
		}
		else
		{
            $this->error=$this->db->error();
			return -1;
		}
	}

	/**
 	 *    \brief      Mets à jour les commentaires publiques
	 *    \param      note_public	Commentaire
	 *    \return     int         	<0 si ko, >0 si ok
	 */
	function update_note_public($note_public)
	{
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'commande';
		$sql.= " SET note_public = '".addslashes($note_public)."'";
		$sql.= " WHERE rowid =". $this->id;

		if ($this->db->query($sql))
		{
			$this->note_public = $note_public;
			return 1;
		}
		else
		{
            $this->error=$this->db->error();
			return -1;
		}
	}
	
	/**
     *      \brief      Définit une date de livraison
     *      \param      user        		Objet utilisateur qui modifie
     *      \param      date_livraison      Date de livraison  
     *      \return     int         		<0 si ko, >0 si ok
     */
    function set_date_livraison($user, $date_livraison)
    {
        if ($user->rights->commande->creer)
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX."commande";
            $sql.= " SET date_livraison = ".($date_livraison ? $this->db->idate($date_livraison) : 'null');
            $sql.= " WHERE rowid = ".$this->id." AND fk_statut = 0";

            $resql=$this->db->query($sql);
            if ($resql)
            {
                $this->date_livraison = $date_livraison;
                return 1;
            }
            else
            {
                $this->error=$this->db->error();
                dolibarr_syslog("Commande::set_date_livraison Erreur SQL sql=$sql");
                return -1;
            }
        }
        else
        {
        	return -2;
        }
    }
    
    /**
     *      \brief      Définit une adresse de livraison
     *      \param      user        		Objet utilisateur qui modifie
     *      \param      adresse_livraison      Adresse de livraison  
     *      \return     int         		<0 si ko, >0 si ok
     */
    function set_adresse_livraison($user, $adresse_livraison)
    {
        if ($user->rights->commande->creer)
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX."commande SET fk_adresse_livraison = '".$adresse_livraison."'";
            $sql.= " WHERE rowid = ".$this->id." AND fk_statut = 0";
    
            if ($this->db->query($sql) )
            {
                $this->adresse_livraison_id = $adresse_livraison;
                return 1;
            }
            else
            {
                $this->error=$this->db->error();
                dolibarr_syslog("Commande::set_adresse_livraison Erreur SQL");
                return -1;
            }
        }
    }
    
  /**
   *    \brief      Renvoi la liste des propal (éventuellement filtrée sur un user) dans un tableau
   *    \param      brouillon       0=non brouillon, 1=brouillon
   *    \param      user            Objet user de filtre
   *    \return     int             -1 si erreur, tableau résultat si ok
   */
	 
    function liste_array ($brouillon=0, $user='')
    {
        $ga = array();
    
        $sql = "SELECT rowid, ref FROM ".MAIN_DB_PREFIX."commande";
    
        if ($brouillon)
        {
            $sql .= " WHERE fk_statut = 0";
            if ($user)
            {
                $sql .= " AND fk_user_author".$user;
            }
        }
        else
        {
            if ($user)
            {
                $sql .= " WHERE fk_user_author".$user;
            }
        }
    
        $sql .= " ORDER BY date_commande DESC";

        $result=$this->db->query($sql);
        if ($result)
        {
            $numc = $this->db->num_rows($result);
    
            if ($numc)
            {
                $i = 0;
                while ($i < $numc)
                {
                    $obj = $this->db->fetch_object($result);
    
                    $ga[$obj->rowid] = $obj->ref;
                    $i++;
                }
            }
            return $ga;
        }
        else
        {
            return -1;
        }
    }

	/**
	 *   \brief      Change les conditions de réglement de la commande
	 *   \param      cond_reglement_id      Id de la nouvelle condition de réglement
	 *   \return     int                    >0 si ok, <0 si ko
	 */
	function cond_reglement($cond_reglement_id)
	{
		dolibarr_syslog('Commande::cond_reglement('.$cond_reglement_id.')');
		if ($this->statut >= 0)
		{
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'commande';
			$sql .= ' SET fk_cond_reglement = '.$cond_reglement_id;
			$sql .= ' WHERE rowid='.$this->id;
			if ( $this->db->query($sql) )
			{
				$this->cond_reglement_id = $cond_reglement_id;
				return 1;
			}
			else
			{
				dolibarr_syslog('Commande::cond_reglement Erreur '.$sql.' - '.$this->db->error());
				$this->error=$this->db->error();
				return -1;
			}
		}
		else
		{
			dolibarr_syslog('Commande::cond_reglement, etat commande incompatible');
			$this->error='Etat commande incompatible '.$this->statut;
			return -2;
		}
	}


	/**
	 *   \brief      Change le mode de réglement
	 *   \param      mode        Id du nouveau mode
	 *   \return     int         >0 si ok, <0 si ko
	 */
	function mode_reglement($mode_reglement_id)
	{
		dolibarr_syslog('Commande::mode_reglement('.$mode_reglement_id.')');
		if ($this->statut >= 0)
		{
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'commande';
			$sql .= ' SET fk_mode_reglement = '.$mode_reglement_id;
			$sql .= ' WHERE rowid='.$this->id;
			if ( $this->db->query($sql) )
			{
				$this->mode_reglement_id = $mode_reglement_id;
				return 1;
			}
			else
			{
				dolibarr_syslog('Commande::mode_reglement Erreur '.$sql.' - '.$this->db->error());
				$this->error=$this->db->error();
				return -1;
			}
		}
		else
		{
			dolibarr_syslog('Commande::mode_reglement, etat facture incompatible');
			$this->error='Etat commande incompatible '.$this->statut;
			return -2;
		}
	}

    /**
     *      \brief      Positionne numero reference commande client
     *      \param      user            Utilisateur qui modifie
     *      \param      ref_client      Reference commande client
     *      \return     int             <0 si ko, >0 si ok
     */
	function set_ref_client($user, $ref_client)
	{
		if ($user->rights->commande->creer)
		{
    		dolibarr_syslog('Commande::set_ref_client this->id='.$this->id.', ref_client='.$ref_client);

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'commande SET ref_client = '.(empty($ref_client) ? 'NULL' : '\''.addslashes($ref_client).'\'');
			$sql.= ' WHERE rowid = '.$this->id;
			if ($this->db->query($sql) )
			{
				$this->ref_client = $ref_client;
				return 1;
			}
			else
			{
                $this->error=$this->db->error();
				dolibarr_syslog('Commande::set_ref_client Erreur '.$this->error.' - '.$sql);
			    return -2;
			}
		}
		else
		{
		    return -1;
		}
	}


	/**
	 *        \brief      Classe la commande comme facturée
	 *        \return     int     <0 si ko, >0 si ok
	 */
	function classer_facturee()
	{
		global $conf;
		
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'commande SET facture = 1';
		$sql .= ' WHERE rowid = '.$this->id.' AND fk_statut > 0 ;';
		if ($this->db->query($sql) )
		{
			if (($conf->global->PROPALE_CLASSIFIED_INVOICED_WITH_ORDER == 1) && $this->propale_id)
			{
				$propal = new Propal($this->db);
				$propal->fetch($this->propale_id);
				$propal->classer_facturee();				
			}
			return 1;
		}
		else
		{
			dolibarr_print_error($this->db);
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
	 *      \return    int              < 0 si erreur, > 0 si ok
	 */
	function update_line($rowid, $desc, $pu, $qty, $remise_percent=0, $txtva)
	{
		dolibarr_syslog("Commande::UpdateLine $rowid, $desc, $pu, $qty, $remise_percent, $txtva");
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
			$tabprice=calcul_price_total($qty, $pu, $remise_percent, $txtva);
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
			$sql = "UPDATE ".MAIN_DB_PREFIX."commandedet SET";
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
			$sql.= ",total_ht='".price2num($total_ht)."'";
			$sql.= ",total_tva='".price2num($total_tva)."'";
			$sql.= ",total_ttc='".price2num($total_ttc)."'";
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
				$this->db->rollback();
				return -1;
			}
		}
		else
		{
			$this->error="Commande::updateline Order status makes operation forbidden";
			return -2;
		}
	}
	

	/**
	* Supprime la commande
	*
	*/
	function delete()
	{
		global $conf, $lang;
		
		$err = 0;
		$this->db->begin();
		$sql = 'DELETE FROM '.MAIN_DB_PREFIX."commandedet WHERE fk_commande = $this->id ;";
		if (! $this->db->query($sql) )
		{
			$err++;
		}

		$sql = 'DELETE FROM '.MAIN_DB_PREFIX."commande WHERE rowid = $this->id;";
		if (! $this->db->query($sql) )
		{
			$err++;
		}

		$sql = 'DELETE FROM '.MAIN_DB_PREFIX."co_pr WHERE fk_commande = $this->id;";
		if (! $this->db->query($sql) )
		{
			$err++;
		}
		
		// On efface le répertoire de pdf provisoire
		$comref = sanitize_string($this->ref);
		if ($conf->commande->dir_output)
		{
				$dir = $conf->commande->dir_output . "/" . $comref ;
				$file = $conf->commande->dir_output . "/" . $comref . "/" . $comref . ".pdf";
				if (file_exists($file))
				{
					commande_delete_preview($this->db, $this->id, $this->ref);
					
					if (!dol_delete_file($file))
					{
              $this->error=$langs->trans("ErrorCanNotDeleteFile",$file);
              return 0;
           }
        }
        if (file_exists($dir))
        {
         	if (!dol_delete_dir($dir))
          {
            	$this->error=$langs->trans("ErrorCanNotDeleteDir",$dir);
              return 0;
          }
        }
     }

		if ($err == 0)
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
	 *        \brief      Classer la commande dans un projet
	 *        \param      cat_id      Id du projet
	 */
	function classin($cat_id)
	{
		$sql = 'UPDATE '.MAIN_DB_PREFIX."commande SET fk_projet = $cat_id";
		$sql .= " WHERE rowid = $this->id;";

		if ($this->db->query($sql) )
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
     *      \brief          Charge indicateurs this->nbtodo et this->nbtodolate de tableau de bord
     *      \param          user    Objet user
     *      \return         int     <0 si ko, >0 si ok
     */
	function load_board($user)
	{
		global $conf, $user;

		$this->nbtodo=$this->nbtodolate=0;
		$sql = 'SELECT c.rowid,'.$this->db->pdate('c.date_creation').' as datec';
		if (!$user->rights->commercial->client->voir && !$user->societe_id) $sql .= ", sc.fk_soc, sc.fk_user";
		$sql.= ' FROM '.MAIN_DB_PREFIX.'commande as c';
		if (!$user->rights->commercial->client->voir && !$user->societe_id) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= ' WHERE c.fk_statut BETWEEN 1 AND 2';
		if ($user->societe_id) $sql.=' AND c.fk_soc = '.$user->societe_id;
		if (!$user->rights->commercial->client->voir && !$user->societe_id) $sql .= " AND c.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		$resql=$this->db->query($sql);
		if ($resql)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$this->nbtodo++;
				if ($obj->datec < (time() - $conf->commande->traitement->warning_delay)) $this->nbtodolate++;
			}
			return 1;
		}
		else
		{
			 $this->error=$this->db->error();
			return -1;
		}
	}


	/**
	 *    	\brief      Retourne le libellé du statut de la commande
	 *    	\param      mode        0=libellé long, 1=libellé court, 2=Picto + Libellé court, 3=Picto, 4=Picto + Libellé long, 5=Libellé court + Picto
	 *    	\return     string      Libellé
	 */
	function getLibStatut($mode)
	{
		return $this->LibStatut($this->statut,$this->facturee,$mode);
	}

	/**
	 *		\brief      Renvoi le libellé d'un statut donné
	 *    	\param      statut      Id statut
	 *    	\param      facturee    Si facturee
	 *    	\param      mode        0=libellé long, 1=libellé court, 2=Picto + Libellé court, 3=Picto, 4=Picto + Libellé long, 5=Libellé court + Picto
	 *    	\return     string		Libellé
	 */
	function LibStatut($statut,$facturee,$mode)
	{
		global $langs;
		
        if ($mode == 0)
        {
        	if ($statut==-1) return $langs->trans('StatusOrderCanceled');
        	if ($statut==0) return $langs->trans('StatusOrderDraft');
        	if ($statut==1) return $langs->trans('StatusOrderValidated');
        	if ($statut==2) return $langs->trans('StatusOrderOnProcess');
        	if ($statut==3 && ! $facturee) return $langs->trans('StatusOrderToBill');
        	if ($statut==3 && $facturee) return $langs->trans('StatusOrderProcessed');
		}
        if ($mode == 1)
        {
        	if ($statut==-1) return $langs->trans('StatusOrderCanceledShort');
        	if ($statut==0) return $langs->trans('StatusOrderDraftShort');
        	if ($statut==1) return $langs->trans('StatusOrderValidatedShort');
        	if ($statut==2) return $langs->trans('StatusOrderOnProcessShort');
        	if ($statut==3 && ! $facturee) return $langs->trans('StatusOrderToBillShort');
        	if ($statut==3 && $facturee) return $langs->trans('StatusOrderProcessed');
        }
        if ($mode == 2)
        {
        	if ($statut==-1) return img_picto($langs->trans('StatusOrderCanceledShort'),'statut5').' '.$langs->trans('StatusOrderCanceled');
        	if ($statut==0) return img_picto($langs->trans('StatusOrderDraftShort'),'statut0').' '.$langs->trans('StatusOrderDraft');
        	if ($statut==1) return img_picto($langs->trans('StatusOrderValidatedShort'),'statut1').' '.$langs->trans('StatusOrderValidated');
        	if ($statut==2) return img_picto($langs->trans('StatusOrderOnProcessShort'),'statut3').' '.$langs->trans('StatusOrderOnProcess');
        	if ($statut==3 && ! $facturee) return img_picto($langs->trans('StatusOrderToBillShort'),'statut4').' '.$langs->trans('StatusOrderToBill');
        	if ($statut==3 && $facturee) return img_picto($langs->trans('StatusOrderProcessedShort'),'statut6').' '.$langs->trans('StatusOrderProcessed');
        }
        if ($mode == 3)
        {
        	if ($statut==-1) return img_picto($langs->trans('StatusOrderCanceled'),'statut5');
        	if ($statut==0) return img_picto($langs->trans('StatusOrderDraft'),'statut0');
        	if ($statut==1) return img_picto($langs->trans('StatusOrderValidated'),'statut1');
        	if ($statut==2) return img_picto($langs->trans('StatusOrderOnProcess'),'statut3');
        	if ($statut==3 && ! $facturee) return img_picto($langs->trans('StatusOrderToBill'),'statut4');
        	if ($statut==3 && $facturee) return img_picto($langs->trans('StatusOrderProcessed'),'statut6');
        }
        if ($mode == 4)
        {
        	if ($statut==-1) return img_picto($langs->trans('StatusOrderCanceled'),'statut5').' '.$langs->trans('StatusOrderCanceled');
        	if ($statut==0) return img_picto($langs->trans('StatusOrderDraft'),'statut0').' '.$langs->trans('StatusOrderDraft');
        	if ($statut==1) return img_picto($langs->trans('StatusOrderValidated'),'statut1').' '.$langs->trans('StatusOrderValidated');
        	if ($statut==2) return img_picto($langs->trans('StatusOrderOnProcess'),'statut3').' '.$langs->trans('StatusOrderOnProcess');
        	if ($statut==3 && ! $facturee) return img_picto($langs->trans('StatusOrderToBill'),'statut4').' '.$langs->trans('StatusOrderToBill');
        	if ($statut==3 && $facturee) return img_picto($langs->trans('StatusOrderProcessed'),'statut6').' '.$langs->trans('StatusOrderProcessed');
        }
        if ($mode == 5)
        {
        	if ($statut==-1) return $langs->trans('StatusOrderCanceledShort').' '.img_picto($langs->trans('StatusOrderCanceledShort'),'statut5');
        	if ($statut==0) return $langs->trans('StatusOrderDraftShort').' '.img_picto($langs->trans('StatusOrderDraftShort'),'statut0');
        	if ($statut==1) return $langs->trans('StatusOrderValidatedShort').' '.img_picto($langs->trans('StatusOrderValidatedShort'),'statut1');
        	if ($statut==2) return $langs->trans('StatusOrderOnProcessShort').' '.img_picto($langs->trans('StatusOrderOnProcessShort'),'statut3');
        	if ($statut==3 && ! $facturee) return $langs->trans('StatusOrderToBillShort').' '.img_picto($langs->trans('StatusOrderToBillShort'),'statut4');
        	if ($statut==3 && $facturee) return $langs->trans('StatusOrderProcessedShort').' '.img_picto($langs->trans('StatusOrderProcessedShort'),'statut6');
        }
	}


    /**
     *      \brief     Charge les informations d'ordre info dans l'objet commande
     *      \param     id       Id de la commande a charger
     */
	function info($id)
	{
		$sql = 'SELECT c.rowid, '.$this->db->pdate('date_creation').' as datec,';
		$sql.= ' '.$this->db->pdate('date_valid').' as datev,';
		$sql.= ' '.$this->db->pdate('date_cloture').' as datecloture,';
		$sql.= ' fk_user_author, fk_user_valid, fk_user_cloture';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'commande as c';
		$sql.= ' WHERE c.rowid = '.$id;
		$result=$this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
				if ($obj->fk_user_author)
				{
					$cuser = new User($this->db, $obj->fk_user_author);
					$cuser->fetch();
					$this->user_creation   = $cuser;
				}

				if ($obj->fk_user_valid)
				{
					$vuser = new User($this->db, $obj->fk_user_valid);
					$vuser->fetch();
					$this->user_validation = $vuser;
				}

				if ($obj->fk_user_cloture)
				{
					$cluser = new User($this->db, $obj->fk_user_cloture);
					$cluser->fetch();
					$this->user_cloture   = $cluser;
				}

				$this->date_creation     = $obj->datec;
				$this->date_validation   = $obj->datev;
				$this->date_cloture      = $obj->datecloture;
			}

			$this->db->free($result);

		}
		else
		{
			dolibarr_print_error($this->db);
		}
	}

}



/**
    	\class      CommandeLigne
		\brief      Classe de gestion des lignes de commande
*/

class CommandeLigne
{
	var $db;
	var $error;
	
	// From llx_commandedet
	var $rowid;
	var $fk_facture;
	var $desc;          	// Description ligne
	
	var $qty;
	var $tva_tx;
	var $subprice;
	var $remise;
	var $remise_percent;
	var $price;
	var $rang;
	var $coef;

	var $fk_product;		// Id produit prédéfini

	var $info_bits;			// Bit 0: 	0 si TVA normal - 1 si TVA NPR
	var $total_ht;			// Total HT  de la ligne toute quantité et incluant la remise ligne
	var $total_tva;			// Total TVA  de la ligne toute quantité et incluant la remise ligne
	var $total_ttc;			// Total TTC de la ligne toute quantité et incluant la remise ligne
	
	// From llx_product
	var $ref;				// Reference produit
	var $libelle;       	// Label produit
	var $product_desc;  	// Description produit


	/**
	 *      \brief     Constructeur d'objets ligne de commande
	 *      \param     DB      handler d'accès base de donnée
	 */
	function CommandeLigne($DB)
	{
		$this->db= $DB ;
	}

	/**
	 *      \brief     Recupére l'objet ligne de commande
	 *      \param     rowid           id de la ligne de commande
	 */
	function fetch($rowid)
	{
		$sql = 'SELECT cd.rowid, cd.fk_commande, cd.fk_product, cd.description, cd.price, cd.qty, cd.tva_tx,';
		$sql.= ' cd.label,';
		$sql.= ' cd.remise, cd.remise_percent, cd.fk_remise_except, cd.subprice,';
		$sql.= ' cd.info_bits, cd.total_ht, cd.total_tva, cd.total_ttc, cd.coef, cd.rang,';
		$sql.= ' p.ref as product_ref, p.label as product_libelle, p.description as product_desc';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'commandedet as cd';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON cd.fk_product = p.rowid';
		$sql.= ' WHERE cd.rowid = '.$rowid;
		$result = $this->db->query($sql);
		if ($result)
		{
			$objp = $this->db->fetch_object($result);
			$this->rowid          = $objp->rowid;
			$this->fk_propal      = $objp->fk_propal;
			$this->label          = $objp->label;
			$this->desc           = $objp->description;
			$this->qty            = $objp->qty;
			$this->price          = $objp->price;
			$this->subprice       = $objp->subprice;
			$this->tva_tx         = $objp->tva_tx;
			$this->remise         = $objp->remise;
			$this->remise_percent = $objp->remise_percent;
			$this->fk_remise_except = $objp->fk_remise_except;
			$this->produit_id     = $objp->fk_product;
			$this->info_bits      = $objp->info_bits;
			$this->total_ht       = $objp->total_ht;
			$this->total_tva      = $objp->total_tva;
			$this->total_ttc      = $objp->total_ttc;
			$this->coef           = $objp->coef;
			$this->rang           = $objp->rang;

			$this->ref			  = $objp->product_ref;
			$this->libelle		  = $objp->product_libelle;
			$this->product_desc	  = $objp->product_desc;

			$this->db->free($result);
		}
		else
		{
			dolibarr_print_error($this->db);
		}
	}
	
	/**
	 *      \brief     	Insère l'objet ligne de commande en base
	 *		\return		int		<0 si ko, >0 si ok
	 */
	function insert()
	{
		$this->db->begin();

		// Insertion dans base de la ligne
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'commandedet';
		$sql.= ' (fk_commande, description, price, qty, tva_tx,';
		$sql.= ' fk_product, remise_percent, subprice, remise, fk_remise_except, ';
		$sql.= ' rang, coef,';
		$sql.= ' info_bits, total_ht, total_tva, total_ttc)';
		$sql.= " VALUES (".$this->fk_commande.",";
		$sql.= " '".addslashes($this->desc)."',";
		$sql.= " '".price2num($this->price)."',";
		$sql.= " '".price2num($this->qty)."',";
		$sql.= " '".price2num($this->tva_tx)."',";
		if ($this->fk_product) { $sql.= "'".$this->fk_product."',"; }
		else { $sql.='null,'; }
		$sql.= " '".price2num($this->remise_percent)."',";
		$sql.= " '".price2num($this->subprice)."',";
		$sql.= " '".price2num($this->remise)."',";
		if ($this->fk_remise_except) $sql.= $this->fk_remise_except.",";
		else $sql.= 'null,';
		$sql.= ' '.$this->rang.',';
		if (isset($this->coef)) $sql.= ' '.$this->coef.',';
		else $sql.= ' null,';
		$sql.= " '".$this->info_bits."',";
		$sql.= " '".price2num($this->total_ht)."',";
		$sql.= " '".price2num($this->total_tva)."',";
		$sql.= " '".price2num($this->total_ttc)."'";
		$sql.= ')';

       	dolibarr_syslog("CommandeLigne.class.php::insert sql=$sql");

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->db->commit();
			return 1;	
		}
		else
		{
        	$this->error=$this->db->error();
        	dolibarr_syslog("CommandeLigne.class.php::insert Error ".$this->error);
			$this->db->rollback();
            return -2;
		}
	}
	
	
	/**
	 *      \brief     	Mise a jour de l'objet ligne de commande en base
	 *		\return		int		<0 si ko, >0 si ok
	 */
	function update()
	{
		$this->db->begin();

		// Mise a jour ligne en base
		$sql = "UPDATE ".MAIN_DB_PREFIX."commandedet SET";
		$sql.= " description='".addslashes($this->desc)."'";
		$sql.= ",price='".price2num($this->price)."'";
		$sql.= ",subprice='".price2num($this->subprice)."'";
		$sql.= ",remise='".price2num($this->remise)."'";
		$sql.= ",remise_percent='".price2num($this->remise_percent)."'";
		if ($fk_remise_except) $sql.= ",fk_remise_except=".$this->fk_remise_except;
		else $sql.= ",fk_remise_except=null";
		$sql.= ",tva_tx='".price2num($this->tva_tx)."'";
		$sql.= ",qty='".price2num($this->qty)."'";
		$sql.= ",rang='".$this->rang."'";
		$sql.= ",coef='".$this->coef."'";
		$sql.= ",info_bits='".$this->info_bits."'";
		$sql.= ",total_ht='".price2num($this->total_ht)."'";
		$sql.= ",total_tva='".price2num($this->total_tva)."'";
		$sql.= ",total_ttc='".price2num($this->total_ttc)."'";
		$sql.= " WHERE rowid = ".$this->rowid;

       	dolibarr_syslog("CommandeLigne.class.php::update sql=$sql");

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->db->commit();
			return 1;	
		}
		else
		{
        	$this->error=$this->db->error();
        	dolibarr_syslog("CommandeLigne.class.php::update Error ".$this->error);
			$this->db->rollback();
            return -2;
		}
	}
		
}

?>
