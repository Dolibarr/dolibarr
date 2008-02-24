<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
 */
 
/**
   \file       htdocs/commande/commande.class.php
   \ingroup    commande
   \brief      Fichier des classes de commandes
   \version    $Id$
*/

require_once(DOL_DOCUMENT_ROOT."/commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");

 
/**
   \class      Commande
   \brief      Class to manage orders
*/
class Commande extends CommonObject
{
  var $db ;
  var $element='commande';
  
  var $id ;
  
  var $socid;		// Id client
  var $client;		// Objet societe client (à charger par fetch_client)
  
  var $ref;
  var $ref_client;
  var $contactid;
  var $projet_id;
  var $statut;		// -1=Annulee, 0=Brouillon, 1=Validée, 2=Acceptée, 3=Reçue (facturee ou non)
  var $facturee;
  var $brouillon;
  var $cond_reglement_id;
  var $cond_reglement_code;
  var $mode_reglement_id;
  var $mode_reglement_code;
  var $adresse_livraison_id;
  var $adresse;
  var $date;				// Date commande
  var $date_livraison;	// Date livraison souhaitée
  var $fk_remise_except;
  var $remise_percent;
  var $remise_absolue;
  var $modelpdf;
  var $info_bits;
  
  var $lines = array();
  
  // Pour board
  var $nbtodo;
  var $nbtodolate;
  
  /**
   *        \brief      Constructeur
   *        \param      DB      Handler d'accès base
   */
  function Commande($DB, $socid="", $commandeid=0)
  {
    global $langs;
    $langs->load('orders');
    $this->db = $DB;
    $this->socid = $socid;
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
    dolibarr_syslog("Commande.class::create_from_propale propale_id=$propale_id");
    
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
	      $CommLigne->fk_remise_except  = $propal->lignes[$i]->fk_remise_except;
	      $CommLigne->remise_percent    = $propal->lignes[$i]->remise_percent;
	      $CommLigne->fk_product        = $propal->lignes[$i]->fk_product;
	      $CommLigne->info_bits         = $propal->lignes[$i]->info_bits;
	      $this->lines[$i] = $CommLigne;
      }

    $this->socid                = $propal->socid;
    $this->projetid             = $propal->projetidp;
    $this->cond_reglement_id    = $propal->cond_reglement_id;
    $this->mode_reglement_id    = $propal->mode_reglement_id;
    $this->date_livraison       = $propal->date_livraison;
    $this->adresse_livraison_id = $propal->adresse_livraison_id;
    $this->contact_id           = $propal->contactid;
    $this->ref_client           = $propal->ref_client;
    $this->note                 = $propal->note;
    $this->note_public          = $propal->note_public;
    
    /* Définit la société comme un client */
    $soc = new Societe($this->db);
    $soc->id = $this->socid;
    $soc->set_as_client();
    $this->propale_id = $propal->id;

    return $this->create($user);
  }


  /**
   *      \brief      Renvoie la référence de commande suivante non utilisée en fonction du module
   *                  de numérotation actif défini dans COMMANDE_ADDON
   *      \param	    soc  		            objet societe
   *      \return     string                  reference libre pour la commande
   */
  function getNextNumRef($soc)
  {
    global $db, $langs, $conf;
    $langs->load("order");

    $dir = DOL_DOCUMENT_ROOT . "/includes/modules/commande";

    if (defined("COMMANDE_ADDON") && COMMANDE_ADDON)
      {
	$file = COMMANDE_ADDON.".php";

            // Chargement de la classe de numérotation
            $classname = $conf->global->COMMANDE_ADDON;
            require_once($dir.'/'.$file);

            $obj = new $classname();
            $numref = "";
            $numref = $obj->getNextValue($soc,$this);

            if ( $numref != "")
            {
	      return $numref;
            }
            else
            {
	      dolibarr_print_error($db,"Commande::getNextNumRef ".$obj->error);
	      return "";
            }
        }
        else
        {
            print $langs->trans("Error")." ".$langs->trans("Error_COMMANDE_ADDON_NotDefined");
            return "";
        }
    }
    
    
  /**
   *  \brief   Valide la commande
   *  \param   user     Utilisateur qui valide
   *  \return  int	<=0 si ko, >0 si ok
   */
  function valid($user)
  {
    global $conf;
    
    if ($user->rights->commande->valider)
      {
	$this->db->begin();
	
	// Definition du nom de module de numerotation de commande
	$soc = new Societe($this->db);
	$soc->fetch($this->socid);
	$num=$this->getNextNumRef($soc);
	
	// Classe la société rattachée comme client
	$result=$soc->set_as_client();
	
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
	
	$sql = 'UPDATE '.MAIN_DB_PREFIX."commande SET ref='$num', fk_statut = 1, date_valid=now(), fk_user_valid=$user->id";
	$sql .= " WHERE rowid = $this->id AND fk_statut = 0";
	
	$resql=$this->db->query($sql);
	if ($resql)
  {
	  // On efface le répertoire de pdf provisoire
	  if ($comref == 'PROV')
	  {
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
			       $this->db->rollback();
			       return 0;
		       }
		      }
		      if (file_exists($dir))
		      {
		        if (!dol_delete_dir($dir))
		        {
			        $this->error=$langs->trans("ErrorCanNotDeleteDir",$dir);
			        $this->db->rollback();
			        return 0;
		        }
		      }
	     }
	   }
	   
	  //Si activé on décrémente le produit principal et ses composants à la validation de commande
	  if($conf->stock->enabled && $conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER == 1)
		{
			require_once(DOL_DOCUMENT_ROOT."/product/stock/mouvementstock.class.php");

				for ($i = 0 ; $i < sizeof($this->lignes) ; $i++)
		    {
		    	if ($conf->global->PRODUIT_SOUSPRODUITS == 1)
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
		    		    $entrepot_id = "1"; //Todo: ajouter possibilité de choisir l'entrepot
		    		    $result=$mouvS->livraison($user, $value[1], $entrepot_id, $value[0]*$this->lignes[$i]->qty);
		    	    }
		        }
		      }
		     $mouvP = new MouvementStock($this->db);
		     // on décompte le stock du produit principal
		     $entrepot_id = "1"; //Todo: ajouter possibilité de choisir l'entrepot
		    $result=$mouvP->livraison($user, $this->lignes[$i]->fk_product, $entrepot_id, $this->lignes[$i]->qty);
		  }
		}

	    // Appel des triggers
	    include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
	    $interface=new Interfaces($this->db);
	    $result=$interface->run_triggers('ORDER_VALIDATE',$this,$user,$langs,$conf);
        if ($result < 0) { $error++; $this->errors=$interface->errors; }
	    // Fin appel triggers
	    
	    $this->db->commit();
	    return $this->id;
	  }
	else
	  {
	    $this->db->rollback();
	    $this->error=$this->db->error();
	    return -1;
	  }
      }
    
    $this->error='Autorisation insuffisante';
    return -1;
  }
  
  /**
   *
   *
   */
  function set_draft($user)
  {
  	global $conf;

    $sql = "UPDATE ".MAIN_DB_PREFIX."commande SET fk_statut = 0";
    $sql .= " WHERE rowid = $this->id;";
    
    if ($this->db->query($sql))
    {
    	//Si activé on incrémente le produit principal et ses composants à l'édition de la commande
	  if($conf->stock->enabled && $conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER == 1)
		{
			require_once(DOL_DOCUMENT_ROOT."/product/stock/mouvementstock.class.php");

				for ($i = 0 ; $i < sizeof($this->lignes) ; $i++)
		    {
		    	if ($conf->global->PRODUIT_SOUSPRODUITS == 1)
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
		    		    $entrepot_id = "1"; //Todo: ajouter possibilité de choisir l'entrepot
		    		    $result=$mouvS->reception($user, $value[1], $entrepot_id, $value[0]*$this->lignes[$i]->qty);
		    	    }
		        }
		      }
		     $mouvP = new MouvementStock($this->db);
		     // on décompte le stock du produit principal
		     $entrepot_id = "1"; //Todo: ajouter possibilité de choisir l'entrepot
		    $result=$mouvP->reception($user, $this->lignes[$i]->fk_product, $entrepot_id, $this->lignes[$i]->qty);
		  }
		}
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
	
	if ($this->db->query($sql))
	{
		if($conf->stock->enabled && $conf->global->PRODUIT_SOUSPRODUITS == 1 && $conf->global->STOCK_CALCULATE_ON_SHIPMENT == 1)
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
		    		$result=$mouvS->livraison($user, $value[1], $entrepot_id, $value[0]*$this->lignes[$i]->qty);
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
  	global $conf;
    if ($user->rights->commande->valider)
    {
	    $sql = 'UPDATE '.MAIN_DB_PREFIX.'commande SET fk_statut = -1';
	    $sql .= " WHERE rowid = $this->id AND fk_statut = 1 ;";
	
	    if ($this->db->query($sql) )
	    {
	  //Si activé on incrémente le produit principal et ses composants à l'édition de la commande
	  if($conf->stock->enabled && $conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER == 1)
		{
			require_once(DOL_DOCUMENT_ROOT."/product/stock/mouvementstock.class.php");

				for ($i = 0 ; $i < sizeof($this->lignes) ; $i++)
		    {
		    	if ($conf->global->PRODUIT_SOUSPRODUITS == 1)
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
		    		    $entrepot_id = "1"; //Todo: ajouter possibilité de choisir l'entrepot
		    		    $result=$mouvS->reception($user, $value[1], $entrepot_id, $value[0]*$this->lignes[$i]->qty);
		    	    }
		        }
		      }
		     $mouvP = new MouvementStock($this->db);
		     // on décompte le stock du produit principal
		     $entrepot_id = "1"; //Todo: ajouter possibilité de choisir l'entrepot
		    $result=$mouvP->reception($user, $this->lignes[$i]->fk_product, $entrepot_id, $this->lignes[$i]->qty);
		  }
		}
	      return 1;
	    }
	    else
	    {
	      dolibarr_print_error($this->db);
	    }
    }
  }
  
  /**
   *  \brief	Créé la commande
   *  \param	user Objet utilisateur qui crée
   */
  function create($user)
  {
    global $conf,$langs,$mysoc;
    
    // Nettoyage parametres
    $this->brouillon = 1;		// On positionne en mode brouillon la commande
    
    dolibarr_syslog("Commande.class::create");
    
    // Vérification paramètres
    if ($this->source < 0)
    {
    	$this->error=$langs->trans("ErrorFieldRequired",$langs->trans("Source"));
	    dolibarr_syslog("Commande.class::create ".$this->error, LOG_ERR);
	    return -1;
    }
    if (! $remise) $remise=0;
    if (! $this->projetid) $this->projetid = 0;
    
    $soc = new Societe($this->db);
    $result=$soc->fetch($this->socid);
    if ($result < 0)
    {
    	$this->error="Failed to fetch company";
	    dolibarr_syslog("Commande.class::create ".$this->error, LOG_ERR);
	    return -2;
    }
    
    $this->db->begin();
    
    $sql = 'INSERT INTO '.MAIN_DB_PREFIX.'commande (';
    $sql.= 'fk_soc, date_creation, fk_user_author, fk_projet, date_commande, source, note, note_public, ref_client,';
    $sql.= ' model_pdf, fk_cond_reglement, fk_mode_reglement, date_livraison, fk_adresse_livraison,';
    $sql.= ' remise_absolue, remise_percent)';
    $sql.= ' VALUES ('.$this->socid.', now(), '.$user->id.', '.$this->projetid.',';
    $sql.= ' '.$this->db->idate($this->date_commande).',';
    $sql.= ' '.$this->source.', ';
    $sql.= " '".addslashes($this->note)."', ";
    $sql.= " '".addslashes($this->note_public)."', ";
    $sql.= " '".addslashes($this->ref_client)."', '".$this->modelpdf."', '".$this->cond_reglement_id."', '".$this->mode_reglement_id."',";
    $sql.= " ".($this->date_livraison?"'".$this->db->idate($this->date_livraison)."'":"null").",";
    $sql.= " '".$this->adresse_livraison_id."',";
    $sql.= " '".$this->remise_absolue."',";
    $sql.= " '".$this->remise_percent."')";
    
    dolibarr_syslog("Commande::create sql=".$sql);
    $resql=$this->db->query($sql);
    if ($resql)
      {
	$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'commande');
	
	if ($this->id)
	  {
	    /*
	     *  Insertion du detail des produits dans la base
	     */
	    for ($i = 0 ; $i < sizeof($this->lines) ; $i++)
	      {
		      $resql = $this->addline(
					$this->id,
					$this->lines[$i]->desc,
					$this->lines[$i]->subprice,
					$this->lines[$i]->qty,
					$this->lines[$i]->tva_tx,
					$this->lines[$i]->fk_product,
					$this->lines[$i]->remise_percent,
					$this->lines[$i]->fk_remise_except,
					$this->lines[$i]->info_bits
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
		    
		    // On récupère les différents contact interne et externe
		    $prop = New Propal($this->db, $this->socid, $this->propale_id);
		    
		    // On récupère le commercial suivi propale
		    $this->userid = $prop->getIdcontact('internal', 'SALESREPFOLL');
		    
		    if ($this->userid)
		      {
			//On passe le commercial suivi propale en commercial suivi commande
			$this->add_contact($this->userid[0], 'SALESREPFOLL', 'internal');
		      }
		    
		    // On récupère le contact client suivi propale
		    $this->contactid = $prop->getIdcontact('external', 'CUSTOMER');
		    
		    if ($this->contactid)
		      {
			//On passe le contact client suivi propale en contact client suivi commande
			$this->add_contact($this->contactid[0], 'CUSTOMER', 'external');
		      }
		  }
		
		// Appel des triggers
		include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
		$interface=new Interfaces($this->db);
		$result=$interface->run_triggers('ORDER_CREATE',$this,$user,$langs,$conf);
        if ($result < 0) { $error++; $this->errors=$interface->errors; }
		// Fin appel triggers
		
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
   *    	\brief     	Ajoute une ligne de produit (associé à un produit/service prédéfini ou non)
   * 		\param    	commandeid      	Id de la commande
   * 		\param    	desc            	Description de la ligne
   * 		\param    	pu_ht              	Prix unitaire HT
   * 		\param    	qty             	Quantité
   * 		\param    	txtva           	Taux de tva forcé, sinon -1
   *		\param    	fk_product      	Id du produit/service predéfini
   * 		\param    	remise_percent  	Pourcentage de remise de la ligne
   * 		\param    	info_bits			Bits de type de lignes
   *		\param    	fk_remise_exscept	Id remise
   *		\param		price_base_type		HT or TTC
   * 		\param    	pu_ttc             	Prix unitaire TTC
   *    	\return    	int             	>0 si ok, <0 si ko
   *    	\see       	add_product
   * 		\remarks	Les parametres sont deja censé etre juste et avec valeurs finales a l'appel
   *					de cette methode. Aussi, pour le taux tva, il doit deja avoir ete défini
   *					par l'appelant par la methode get_default_tva(societe_vendeuse,societe_acheteuse,taux_produit)
   *					et le desc doit deja avoir la bonne valeur (a l'appelant de gerer le multilangue)
   */
	function addline($commandeid, $desc, $pu_ht, $qty, $txtva, $fk_product=0, $remise_percent=0, $info_bits=0, $fk_remise_except=0, $price_base_type='HT', $pu_ttc=0)
	{
		dolibarr_syslog("Commande::addline commandeid=$commandeid, desc=$desc, pu_ht=$pu_ht, qty=$qty, txtva=$txtva, fk_product=$fk_product, remise_percent=$remise_percent, info_bits=$info_bits, fk_remise_except=$fk_remise_except, price_base_type=$price_base_type, pu_ttc=$pu_ttc");
		include_once(DOL_DOCUMENT_ROOT.'/lib/price.lib.php');
		
		if ($this->statut == 0)
		{
			$this->db->begin();
			
			// Nettoyage paramètres
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

			// Calcul du total TTC et de la TVA pour la ligne a partir de
			// qty, pu, remise_percent et txtva
			// TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker 
			// la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.
			$tabprice = calcul_price_total($qty, $pu, $remise_percent, $txtva, 0, $price_base_type, $info_bits);
			$total_ht  = $tabprice[0];
			$total_tva = $tabprice[1];
			$total_ttc = $tabprice[2];
			
			// \TODO A virer
			// Anciens indicateurs: $price, $remise (a ne plus utiliser)
			$price = $pu;
			$remise = 0;
			if ($remise_percent > 0)
			{
				$remise = round(($pu * $remise_percent / 100), 2);
				$price = $pu - $remise;
			}
			
			// Insertion ligne
			$ligne=new CommandeLigne($this->db);
			
			$ligne->fk_commande=$commandeid;
			$ligne->desc=$desc;
			$ligne->qty=$qty;
			$ligne->tva_tx=$txtva;
			$ligne->fk_product=$fk_product;
			$ligne->fk_remise_except=$fk_remise_except;
			$ligne->remise_percent=$remise_percent;
			$ligne->subprice=$pu_ht;
			$ligne->rang=-1;
			$ligne->info_bits=$info_bits;
			$ligne->total_ht=$total_ht;
			$ligne->total_tva=$total_tva;
			$ligne->total_ttc=$total_ttc;
			
			// \TODO Ne plus utiliser
			$ligne->price=$price;
			$ligne->remise=$remise;
			
			$result=$ligne->insert();			
			if ($result > 0)
			{
				// Mise a jour informations denormalisees au niveau de la commande meme
				$result=$this->update_price($commandeid);
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
   * 		\brief				Ajoute une ligne dans tableau lines
   *		\param				idproduct			Id du produit à ajouter
   *		\param				qty					Quantité
   *		\remise_percent		remise_percent		Remise relative effectuée sur le produit
   * 		\return    			void
   *		\remarks			$this->client doit etre chargé
   *		\TODO	Remplacer les appels a cette fonction par generation objet Ligne 
   *				inséré dans tableau $this->products
   */
  function add_product($idproduct, $qty, $remise_percent=0)
  {
    global $conf, $mysoc;
    
    if (! $qty) $qty = 1;
    
    if ($idproduct > 0)
      {
	$prod=new Product($this->db);
	$prod->fetch($idproduct);
	
	$tva_tx = get_default_tva($mysoc,$this->client,$prod->tva_tx);
	// multiprix
	if($conf->global->PRODUIT_MULTIPRICES == 1)
	  $price = $prod->multiprices[$this->client->price_level];
	else
	  $price = $prod->price;
	
	$line=new CommandeLigne($this->db);
	$line->fk_product=$idproduct;
	$line->desc=$prod->description;
	$line->qty=$qty;
	$line->subprice=$price;
	$line->remise_percent=$remise_percent;
	$line->tva_tx=$tva_tx;
	$line->ref=$prod->ref;
	$line->libelle=$prod->libelle;
	$line->product_desc=$prod->description;
	
	$this->lines[] = $line;
	
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
    $sql = 'SELECT c.rowid, c.date_creation, c.ref, c.fk_soc, c.fk_user_author, c.fk_statut';
    $sql.= ', c.amount_ht, c.total_ht, c.total_ttc, c.tva, c.fk_cond_reglement, c.fk_mode_reglement';
    $sql.= ', '.$this->db->pdate('c.date_commande').' as date_commande';
    $sql.= ', '.$this->db->pdate('c.date_livraison').' as date_livraison';
    $sql.= ', c.fk_projet, c.remise_percent, c.remise, c.remise_absolue, c.source, c.facture as facturee';
    $sql.= ', c.note, c.note_public, c.ref_client, c.model_pdf, c.fk_adresse_livraison';
    $sql.= ', p.code as mode_reglement_code, p.libelle as mode_reglement_libelle';
    $sql.= ', cr.code as cond_reglement_code, cr.libelle as cond_reglement_libelle, cr.libelle_facture as cond_reglement_libelle_facture';
    $sql.= ', cp.fk_propale';
    $sql.= ' FROM '.MAIN_DB_PREFIX.'commande as c';
    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'cond_reglement as cr ON (c.fk_cond_reglement = cr.rowid)';
    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as p ON (c.fk_mode_reglement = p.id)';
    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'co_pr as cp ON (cp.fk_commande = c.rowid)';
    $sql.= ' WHERE c.rowid = '.$id;
    
    dolibarr_syslog("Commande::fetch sql=$sql");
    
    $result = $this->db->query($sql) ;
    if ($result)
      {
	$obj = $this->db->fetch_object($result);
	if ($obj)
	  {
	    $this->id                     = $obj->rowid;
	    $this->ref                    = $obj->ref;
	    $this->ref_client             = $obj->ref_client;
	    $this->socid                  = $obj->fk_soc;
	    $this->statut                 = $obj->fk_statut;
	    $this->user_author_id         = $obj->fk_user_author;
	    $this->total_ht               = $obj->total_ht;
	    $this->total_tva              = $obj->tva;
	    $this->total_ttc              = $obj->total_ttc;
	    $this->date                   = $obj->date_commande;
	    $this->remise                 = $obj->remise;
	    $this->remise_percent         = $obj->remise_percent;
	    $this->remise_absolue         = $obj->remise_absolue;
	    $this->source                 = $obj->source;
	    $this->facturee               = $obj->facturee;
	    $this->note                   = $obj->note;
	    $this->note_public            = $obj->note_public;
	    $this->projet_id              = $obj->fk_projet;
	    $this->modelpdf               = $obj->model_pdf;
	    $this->mode_reglement_id      = $obj->fk_mode_reglement;
	    $this->mode_reglement_code    = $obj->mode_reglement_code;
	    $this->mode_reglement         = $obj->mode_reglement_libelle;
	    $this->cond_reglement_id      = $obj->fk_cond_reglement;
	    $this->cond_reglement_code    = $obj->cond_reglement_code;
	    $this->cond_reglement         = $obj->cond_reglement_libelle;
	    $this->cond_reglement_facture = $obj->cond_reglement_libelle_facture;
	    $this->date_livraison         = $obj->date_livraison;
	    $this->adresse_livraison_id   = $obj->fk_adresse_livraison;
	    $this->propale_id             = $obj->fk_propale;
	    $this->lignes                 = array();
	    
	    if ($this->statut == 0) $this->brouillon = 1;
		
	    $this->db->free();
	    
	    if ($this->propale_id)
	    {
	    	$sqlp = "SELECT ref";
		    $sqlp.= " FROM ".MAIN_DB_PREFIX."propal";
		    $sqlp.= " WHERE rowid = ".$this->propale_id;

		    $resqlprop = $this->db->query($sqlp);

		    if ($resqlprop)
		    {
		      $objp = $this->db->fetch_object($resqlprop);
		      $this->propale_ref = $objp->ref;
		      $this->db->free($resqlprop);
		    }
	    }

	    /*
	     * Lignes
	     */
	    $result=$this->fetch_lines();
	    if ($result < 0)
	    {
	    	return -3;
	    }
	    return 1;
	  }
	  else
	  {
	    dolibarr_syslog('Commande::Fetch Error rowid='.$rowid.' numrows=0 sql='.$sql);
	    $this->error='Order with id '.$rowid.' not found sql='.$sql;
	    return -2;	
	  }
	}
	else
  {
  	dolibarr_syslog('Commande::Fetch Error rowid='.$rowid.' Erreur dans fetch de la commande');
	  $this->error=$this->db->error();
	  return -1;
  }
}

	
  /**
   *    \brief     Ajout d'une ligne remise fixe dans la commande, en base
   *    \param     idremise			Id de la remise fixe
   *    \return    int          		>0 si ok, <0 si ko
   */
  function insert_discount($idremise)
  {
    global $langs;

    include_once(DOL_DOCUMENT_ROOT.'/lib/price.lib.php');
    include_once(DOL_DOCUMENT_ROOT.'/discount.class.php');

    $this->db->begin();

    $remise=new DiscountAbsolute($this->db);
    $result=$remise->fetch($idremise);

    if ($result > 0)
    {
    	if ($remise->fk_facture)	// Protection against multiple submission
			{
				$this->error=$langs->trans("ErrorDiscountAlreadyUsed");
				$this->db->rollback();
				return -5;
			}
    	
    	$comligne=new CommandeLigne($this->db);
    	$comligne->fk_commande=$this->id;
    	$comligne->fk_remise_except=$remise->id;
    	$comligne->desc=$remise->description;   	// Description ligne
    	$comligne->tva_tx=$remise->tva_tx;
    	$comligne->subprice=-$remise->amount_ht;
    	$comligne->price=-$remise->amount_ht;
    	$comligne->fk_product=0;					// Id produit prédéfini
    	$comligne->qty=1;
    	$comligne->remise=0;
    	$comligne->remise_percent=0;
    	$comligne->rang=-1;
    	$comligne->info_bits=2;
    	
    	$comligne->total_ht  = -$remise->amount_ht;
			$comligne->total_tva = -$remise->amount_tva;
			$comligne->total_ttc = -$remise->amount_ttc;
    	
    	$result=$comligne->insert();
    	if ($result > 0)
    	{
    		$result=$this->update_price($this->id);
    		if ($result > 0)
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
	    	$this->error=$comligne->error;
	    	$this->db->rollback();	
	    	return -2;
	    }
	  }
    else
    {
    	$this->db->rollback();
    	return -2;
    }
  }
	
	
  /**
   *		\brief		Positionne modele derniere generation
   *		\param		user		Objet use qui modifie
   *		\param		modelpdf	Nom du modele
   */
  function set_pdf_model($user, $modelpdf)
  {
    if ($user->rights->commande->creer)
      {
	$sql = "UPDATE ".MAIN_DB_PREFIX."commande SET model_pdf = '$modelpdf'";
	$sql.= " WHERE rowid = ".$this->id;
	
	if ($this->db->query($sql) )
	  {
	    $this->modelpdf=$modelpdf;
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
	function fetch_lines($only_product=0)
	{
		$this->lignes=array();
		
		$sql = 'SELECT l.rowid, l.fk_product, l.fk_commande, l.description, l.price, l.qty, l.tva_tx,';
		$sql.= ' l.fk_remise_except, l.remise_percent, l.subprice, l.marge_tx, l.marque_tx, l.rang, l.info_bits,';
		$sql.= ' l.total_ht, l.total_ttc, l.total_tva,';
		$sql.= ' p.ref as product_ref, p.description as product_desc, p.fk_product_type, p.label';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'commandedet as l';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON (p.rowid = l.fk_product)';
		$sql.= ' WHERE l.fk_commande = '.$this->id;
		if ($only_product==1) $sql .= ' AND p.fk_product_type = 0';
		$sql .= ' ORDER BY l.rang';

		dolibarr_syslog("Commande::fetch_lines sql=".$sql,LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);

			$i = 0;
			while ($i < $num)
			{
				$objp = $this->db->fetch_object($result);

				$ligne = new CommandeLigne($this->db);
				$ligne->rowid            = $objp->rowid;
				$ligne->id               = $objp->rowid;				// \deprecated
				$ligne->fk_commande      = $objp->fk_commande;
				$ligne->commande_id      = $objp->fk_commande;		// \deprecated
				$ligne->desc             = $objp->description;  // Description ligne
				$ligne->qty              = $objp->qty;
				$ligne->tva_tx           = $objp->tva_tx;
				$ligne->total_ht         = $objp->total_ht;
				$ligne->total_ttc        = $objp->total_ttc;
				$ligne->total_tva        = $objp->total_tva;
				$ligne->subprice         = $objp->subprice;
				$ligne->fk_remise_except = $objp->fk_remise_except;
				$ligne->remise_percent   = $objp->remise_percent;
				$ligne->price            = $objp->price;
				$ligne->fk_product       = $objp->fk_product;
				$ligne->marge_tx         = $objp->marge_tx;
				$ligne->marque_tx        = $objp->marque_tx;
				$ligne->rang             = $objp->rang;
				$ligne->info_bits        = $objp->info_bits;

				$ligne->ref              = $objp->product_ref;
				$ligne->libelle          = $objp->label;	
				$ligne->product_desc     = $objp->product_desc; 		// Description produit
				$ligne->fk_product_type  = $objp->fk_product_type;	// Produit ou service

				$this->lignes[$i] = $ligne;
				$i++;
			}
			$this->db->free($result);
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dolibarr_syslog('Commande::fetch_lines: Error '.$this->error);
			return -3;
		}
	}


  /**
   *      \brief      Renvoie nombre de lignes de type produits. Doit etre appelé après fetch_lines
   *		\return		int		<0 si ko, Nbre de lignes produits sinon
   */
  function getNbOfProductsLines()
  {
    $nb=0;
    foreach($this->lignes as $ligne)
      {
	if ($ligne->fk_product_type == 0) $nb++;
      }
    return $nb;
  }

    /**
     *      \brief      Charge tableau avec les expéditions par ligne
     *      \param      filtre_statut       Filtre sur statut
     *      \return     int                	<0 if KO, Nb of records if OK
     */
	function loadExpeditions($filtre_statut=-1)
	{
		$num=0;
		$this->expeditions = array();
		
		$sql = 'SELECT fk_product, sum(ed.qty)';
		$sql.=' FROM '.MAIN_DB_PREFIX.'expeditiondet as ed, '.MAIN_DB_PREFIX.'expedition as e, '.MAIN_DB_PREFIX.'commande as c, '.MAIN_DB_PREFIX.'commandedet as cd';
		$sql.=' WHERE ed.fk_expedition = e.rowid AND ed.fk_origin_line = cd.rowid AND cd.fk_commande = c.rowid';
		$sql.=' AND cd.fk_commande =' .$this->id;
		if ($filtre_statut >= 0) $sql.=' AND e.fk_statut = '.$filtre_statut;
		$sql .= ' GROUP BY fk_product ';

		dolibarr_syslog("Commande::loadExpedition sql=".$sql,LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			$i = 0;
			while ($i < $num)
			{
				$row = $this->db->fetch_row($result);
				$this->expeditions[$row[0]] = $row[1];
				$i++;
			}
			$this->db->free();
			return $num;
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}
	
	}
	
	/**
	* Renvoie un tableau avec nombre de lignes d'expeditions
	*
	*/
	function nb_expedition()
	{
		$sql = 'SELECT count(*) FROM '.MAIN_DB_PREFIX.'expedition as e';
		$sql .=" WHERE e.fk_commande = ".$this->id;

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
     *      \brief      Renvoie un tableau avec les stocks restant par produit
     *      \param      filtre_statut       Filtre sur statut
     *      \return     int                 0 si OK, <0 si KO
     *		\todo		FONCTION NON FINIE A FINIR
     */
	function stock_array($filtre_statut=-1)
	{
		$this->stocks = array();

		// Tableau des id de produit de la commande
		
		
		// Recherche total en stock pour chaque produit
		if (sizeof($array_of_product))
		{
	        $sql = "SELECT fk_product, sum(ps.reel) as total";
	        $sql.= " FROM ".MAIN_DB_PREFIX."product_stock as ps";
	        $sql.= " WHERE ps.fk_product in (".join(',',$array_of_product).")";
			$sql.= ' GROUP BY fk_product ';
			$result = $this->db->query($sql);
			if ($result)
			{
				$num = $this->db->num_rows($result);
				$i = 0;
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($result);
					$this->stocks[$obj->fk_product] = $obj->total;
					$i++;
				}
				$this->db->free();
			}
		}
	    return 0;
	}
  
	/**
	*  \brief      Supprime une ligne de la commande
	*  \param      idligne     Id de la ligne à supprimer
	*  \return     int         >0 si ok, 0 si rien à supprimer, <0 si ko
	*/
	function delete_line($idligne)
	{
		if ($this->statut == 0)
		{
			$this->db->begin();
			
			$sql = "SELECT fk_product, qty";
			$sql.= " FROM ".MAIN_DB_PREFIX."commandedet";
			$sql.= " WHERE rowid = '$idligne'";
			
			$result = $this->db->query($sql);
			if ($result)
			{
				$obj = $this->db->fetch_object($result);
				
				if ($obj)
				{
					$product = new Product($this->db);
					$product->id = $obj->fk_product;

					$result=$product->ajust_stock_commande($obj->qty, 1);

					// Supprime ligne
					$ligne = new CommandeLigne($this->db);
					$ligne->id = $idligne;
					$ligne->fk_commande = $this->id; // On en a besoin dans les triggers
					$result=$ligne->delete();
		
					if ($result > 0)
					{
						$result=$this->update_price();
					
						if ($result > 0)
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
						return -1;
					}
				}
				else
				{
					$this->db->rollback();
					return 0;
				}
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
			return -1;
		}
	}

  /**
   * 	\brief     	Applique une remise relative
   * 	\param     	user		User qui positionne la remise
   * 	\param     	remise
   *	\return		int 		<0 si ko, >0 si ok
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
		
		$tvas=array();
		$err=0;

		// Liste des lignes factures a sommer
		$sql = "SELECT price, qty, tva_tx, total_ht, total_tva, total_ttc";
		$sql.= " FROM ".MAIN_DB_PREFIX."commandedet";
		$sql.= " WHERE fk_commande = ".$this->id;
		
		dolibarr_syslog("Commande::update_price sql=".$sql);
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
				
				$tvas[$obj->tva_taux] += ($obj->total_ttc - $obj->total_ht);
				$i++;
			}
			
			$this->db->free($result);

			// Met a jour indicateurs
			$sql = "UPDATE ".MAIN_DB_PREFIX."commande SET";
			$sql .= " total_ht='". price2num($this->total_ht)."',";
			$sql .= " tva='".      price2num($this->total_tva)."',";
			$sql .= " total_ttc='".price2num($this->total_ttc)."'";
			$sql .=" WHERE rowid = ".$this->id;

			dolibarr_syslog("Facture::update_price sql=".$sql);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				return 1;
			}
			else
			{
				$this->error=$this->db->error();
				dolibarr_syslog("Commande::update_price error=".$this->error,LOG_ERR);
				return -1;
			}
		}
		else
		{
			$this->error=$this->db->error();
			dolibarr_syslog("Commande::update_price error=".$this->error,LOG_ERR);
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
			
			dolibarr_syslog("Commande::set_date_livraison sql=$sql",LOG_DEBUG);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$this->date_livraison = $date_livraison;
				return 1;
			}
			else
			{
				$this->error=$this->db->error();
				dolibarr_syslog("Commande::set_date_livraison ".$this->error,LOG_ERR);
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
	 *    \brief      Renvoi la liste des commandes (éventuellement filtrée sur un user) dans un tableau
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
   *  \brief     Mets à jour une ligne de commande
   *  \param     rowid            Id de la ligne de facture
   *  \param     desc             Description de la ligne
   *  \param     pu               Prix unitaire
   *  \param     qty              Quantité
   *  \param     remise_percent   Pourcentage de remise de la ligne
   *  \param     tva_tx           Taux TVA
   *  \param     info_bits        Miscellanous informations on line
   *  \return    int              < 0 si erreur, > 0 si ok
   */
  function updateline($rowid, $desc, $pu, $qty, $remise_percent=0, $txtva, $price_base_type='HT', $info_bits=0)
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
	$tabprice=calcul_price_total($qty, $pu, $remise_percent, $txtva, 0, $price_base_type, $info_bits);
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


	$LigneOld = new CommandeLigne($this->db);
	$LigneOld->fetch($rowid);
	
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

	    if ($LigneOld->qty <> $qty && $LigneOld->produit_id)
	      {
		$delta = $qty - $LigneOld->qty;
		$op = ($delta > 0) ? 0 : 1; 

		$product = new Product($this->db);
		$product->id = $LigneOld->produit_id;
		$product->ajust_stock_commande(abs($delta), $op);
	      }

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
   *		\brief		Supprime la commande
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
		$this->db->rollback();
		return 0;
	      }
	  }
	if (file_exists($dir))
	  {
	    if (!dol_delete_dir($dir))
	      {
		$this->error=$langs->trans("ErrorCanNotDeleteDir",$dir);
		$this->db->rollback();
		return 0;
	      }
	  }
      }
    
    if ($err == 0)
      {
	// Appel des triggers
	include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
	$interface=new Interfaces($this->db);
	$result=$interface->run_triggers('ORDER_DELETE',$this,$user,$langs,$conf);
    if ($result < 0) { $error++; $this->errors=$interface->errors; }
	// Fin appel triggers
	
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
		$clause = "WHERE";
		
		$sql = 'SELECT c.rowid,'.$this->db->pdate('c.date_creation').' as datec';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'commande as c';
		if (!$user->rights->commercial->client->voir && !$user->societe_id)
		{
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON c.fk_soc = sc.fk_soc";
			$sql.= " WHERE sc.fk_user = " .$user->id;
			$clause = "AND";
		}
		$sql.= ' '.$clause.' c.fk_statut BETWEEN 1 AND 2';
		if ($user->societe_id) $sql.=' AND c.fk_soc = '.$user->societe_id;
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
		\brief      Renvoie nom clicable (avec eventuellement le picto)
		\param		withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
		\param		option			Sur quoi pointe le lien: 0,1,2,4,-1 fiche commande, 3 fiche compta commande
		\return		string			Chaine avec URL
	*/
	function getNomUrl($withpicto=0,$option=0)
	{
		global $langs;
		
		$result='';
		$urlOption='';
		
		if ($option == 3) $urlOption = '/compta';
		
		$lien = '<a href="'.DOL_URL_ROOT.$urlOption.'/commande/fiche.php?id='.$this->id.'">';
		$lienfin='</a>';
		
		$picto='order';
		$label=$langs->trans("ShowOrder").': '.$this->ref;
		
		if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
		if ($withpicto && $withpicto != 2) $result.=' ';
		$result.=$lien.$this->ref.$lienfin;
		return $result;
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


	/**
	 *		\brief		Initialise la commande avec valeurs fictives aléatoire
	 *					Sert à générer une commande pour l'aperu des modèles ou demo
	 */
	function initAsSpecimen()
	{
		global $user,$langs;

		dolibarr_syslog("Commande::initAsSpecimen");

		// Charge tableau des id de société socids
		$socids = array();
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe WHERE client=1 LIMIT 10";
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
			$ligne=new CommandeLigne($this->db);
			$ligne->desc=$langs->trans("Description")." ".$xnbp;
			$ligne->qty=1;
			$ligne->subprice=100;
			$ligne->price=100;
			$ligne->tva_tx=19.6;
			$ligne->total_ht=100;
			$ligne->total_ttc=119.6;
			$ligne->total_tva=19.6;
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


	/**
	*      \brief      Charge indicateurs this->nb de tableau de bord
	*      \return     int         <0 si ko, >0 si ok
	*/
	function load_state_board()
	{
		global $conf, $user;

		$this->nb=array();

		$sql = "SELECT count(co.rowid) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."commande as co";
		if (!$user->rights->commercial->client->voir && !$user->societe_id)
		{
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON co.fk_soc = s.rowid";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
			$sql.= " WHERE sc.fk_user = " .$user->id;
		}
		$resql=$this->db->query($sql);
		if ($resql)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$this->nb["orders"]=$obj->nb;
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



/**
 *  \class      CommandeLigne
 *  \brief      Classe de gestion des lignes de commande
 */
class CommandeLigne
{
	var $db;
	var $error;

	// From llx_commandedet
	var $rowid;
	var $fk_facture;
	var $desc;          	// Description ligne
	var $fk_product;		// Id produit prédéfini

	var $qty;		// Quantité (exemple 2)
	var $tva_tx;		// Taux tva produit/service (exemple 19.6)
	var $subprice;      	// P.U. HT (exemple 100)
	var $remise_percent;	// % de la remise ligne (exemple 20%)
	var $rang = 0;
	var $marge_tx;
	var $marque_tx;
	var $info_bits = 0;		// Bit 0: 	0 si TVA normal - 1 si TVA NPR
							// Bit 1:	0 ligne normale - 1 si ligne de remise fixe
	var $total_ht;			// Total HT  de la ligne toute quantité et incluant la remise ligne
	var $total_tva;			// Total TVA  de la ligne toute quantité et incluant la remise ligne
	var $total_ttc;			// Total TTC de la ligne toute quantité et incluant la remise ligne

	// Ne plus utiliser
	var $remise;
	var $price;

	// From llx_product
	var $ref;				// Reference produit
	var $product_libelle; 	// Label produit
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
	*  \brief     Load line order
	*  \param     rowid           id line order
	*/
	function fetch($rowid)
	{
		$sql = 'SELECT cd.rowid, cd.fk_commande, cd.fk_product, cd.description, cd.price, cd.qty, cd.tva_tx,';
		$sql.= ' cd.remise, cd.remise_percent, cd.fk_remise_except, cd.subprice,';
		$sql.= ' cd.info_bits, cd.total_ht, cd.total_tva, cd.total_ttc, cd.marge_tx, cd.marque_tx, cd.rang,';
		$sql.= ' p.ref as product_ref, p.label as product_libelle, p.description as product_desc';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'commandedet as cd';
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
			$this->price            = $objp->price;
			$this->subprice         = $objp->subprice;
			$this->tva_tx           = $objp->tva_tx;
			$this->remise           = $objp->remise;
			$this->remise_percent   = $objp->remise_percent;
			$this->fk_remise_except = $objp->fk_remise_except;
			$this->produit_id       = $objp->fk_product;
			$this->info_bits        = $objp->info_bits;
			$this->total_ht         = $objp->total_ht;
			$this->total_tva        = $objp->total_tva;
			$this->total_ttc        = $objp->total_ttc;
			$this->marge_tx         = $objp->marge_tx;
			$this->marque_tx        = $objp->marque_tx;
			$this->rang             = $objp->rang;
			
			$this->ref	            = $objp->product_ref;
			$this->product_libelle  = $objp->product_libelle;
			$this->product_desc     = $objp->product_desc;
			
			$this->db->free($result);
		}
		else
		{
			dolibarr_print_error($this->db);
		}
	}

	/**
	*    	\brief     	Supprime la ligne de commande en base
	*		\return		int <0 si ko, >0 si ok
	*/
	function delete()
	{
		global $langs, $conf, $user;

		$sql = 'DELETE FROM '.MAIN_DB_PREFIX."commandedet WHERE rowid='".$this->id."';";

		dolibarr_syslog("CommandeLigne::delete sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			// Appel des triggers
			include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
			$interface=new Interfaces($this->db);
			$result=$interface->run_triggers('LINEORDER_DELETE',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
			// Fin appel triggers

			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			dolibarr_syslog("CommandeLigne::delete ".$this->error);
			return -1;
		}   
	}

	/**
	*   \brief     	Insère l'objet ligne de commande en base
	*   \param      notrigger		1 ne declenche pas les triggers, 0 sinon
	*	\return		int				<0 si ko, >0 si ok
	*/
	function insert($notrigger=0)
	{
		global $langs, $conf, $user;

		dolibarr_syslog("CommandeLigne::insert rang=".$this->rang);
		$this->db->begin();
		
		$rangtouse=$this->rang;
		if ($rangtouse == -1)
		{
			// Récupère rang max de la commande dans $rangmax
			$sql = 'SELECT max(rang) as max FROM '.MAIN_DB_PREFIX.'commandedet';
			$sql.= ' WHERE fk_commande ='.$this->fk_commande;
			$resql = $this->db->query($sql);
			if ($resql)
			{
				$obj = $this->db->fetch_object($resql);
				$rangtouse = $obj->max + 1;
			}
			else
			{
				dolibarr_print_error($this->db);
				$this->db->rollback();
				return -1;
			}
		}
		
		// Insertion dans base de la ligne
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'commandedet';
		$sql.= ' (fk_commande, description, qty, tva_tx,';
		$sql.= ' fk_product, remise_percent, subprice, price, remise, fk_remise_except,';
		$sql.= ' rang, marge_tx, marque_tx,';
		$sql.= ' info_bits, total_ht, total_tva, total_ttc)';
		$sql.= " VALUES (".$this->fk_commande.",";
		$sql.= " '".addslashes($this->desc)."',";
		$sql.= " '".price2num($this->qty)."',";
		$sql.= " '".price2num($this->tva_tx)."',";
		if ($this->fk_product) { $sql.= "'".$this->fk_product."',"; }
		else { $sql.='null,'; }
		$sql.= " '".price2num($this->remise_percent)."',";
		$sql.= " '".price2num($this->subprice)."',";
		$sql.= " '".price2num($this->price)."',";
		$sql.= " '".price2num($this->remise)."',";
		if ($this->fk_remise_except) $sql.= $this->fk_remise_except.",";
		else $sql.= 'null,';
		$sql.= ' '.$rangtouse.',';
		if (isset($this->marge_tx)) $sql.= ' '.$this->marge_tx.',';
		else $sql.= ' null,';
		if (isset($this->marque_tx)) $sql.= ' '.$this->marque_tx.',';
		else $sql.= ' null,';
		$sql.= " '".$this->info_bits."',";
		$sql.= " '".price2num($this->total_ht)."',";
		$sql.= " '".price2num($this->total_tva)."',";
		$sql.= " '".price2num($this->total_ttc)."'";
		$sql.= ')';
		
		if ($this->fk_product) 
		{
			$product = new Product($this->db);
			$product->id = $this->fk_product;
			$product->ajust_stock_commande($this->qty, 0);
		}

		dolibarr_syslog("CommandeLigne::insert sql=$sql");
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->rowid=$this->db->last_insert_id(MAIN_DB_PREFIX.'commandedet');

            if (! $notrigger)
            {
				// Appel des triggers
				include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers('LINEORDER_INSERT',$this,$user,$langs,$conf);
                if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// Fin appel triggers
			}
			
			$this->db->commit();
			return $this->rowid;
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
	function update_total()
	{
		$this->db->begin();
		
		// Mise a jour ligne en base
		$sql = "UPDATE ".MAIN_DB_PREFIX."commandedet SET";
		$sql.= " total_ht='".price2num($this->total_ht)."'";
		$sql.= ",total_tva='".price2num($this->total_tva)."'";
		$sql.= ",total_ttc='".price2num($this->total_ttc)."'";
		$sql.= " WHERE rowid = ".$this->rowid;
		
		dolibarr_syslog("CommandeLigne.class.php::update_total sql=$sql");
		
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->db->commit();
			return 1;	
		}
		else
		{
			$this->error=$this->db->error();
			dolibarr_syslog("CommandeLigne.class.php::update_total Error ".$this->error);
			$this->db->rollback();
			return -2;
		}
	}  
}

?>
