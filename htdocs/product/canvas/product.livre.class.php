<?php
/* Copyright (C) 2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 \file       htdocs/product/canvas/product.livre.class.php
  \ingroup    produit
   \brief      Fichier de la classe des produits specifiques de type livre
    \version    $Revision$
*/

require_once(DOL_DOCUMENT_ROOT.'/product/canvas/product.livrecontrat.class.php');

/**
   \class      ProductLivre
   \brief      Classe permettant la gestion des livres, cette classe surcharge la classe produit
*/

class ProductLivre extends Product
{
  /**
   *    \brief      Constructeur de la classe
   *    \param      DB          Handler accès base de données
   *    \param      id          Id produit (0 par defaut)
   */
  function ProductLivre($DB=0, $id=0)
  {
    $this->db = $DB;
    $this->id = $id ;
    $this->canvas = "livre";
    $this->name = "livre";
    $this->description = "Gestion des livres";
    $this->active = PRODUIT_SPECIAL_LIVRE;
    $this->menu_new = 'NewBook';
    $this->menu_add = 1;
    $this->menu_clear = 1;
    
    $this->menus[0][0] = DOL_URL_ROOT."/product/fiche.php?action=create&amp;type=0&amp;canvas=livre";
    $this->menus[0][1] = 'NewBook';
    $this->menus[1][0] = DOL_URL_ROOT."/product/liste.php?canvas=livre";
    $this->menus[1][1] = 'ListBook';
    /*
    $this->menus[2][0] = DOL_URL_ROOT."/product/liste.php?canvas=livrecontrat";
    $this->menus[2][1] = 'ListContract';
    $this->menus[3][0] = DOL_URL_ROOT."/product/liste.php?canvas=livrecouverture";
    $this->menus[3][1] = 'ListCover';
    */
    $this->next_prev_filter = "canvas='livre'";
  }

  function GetListeTitre()
  {
    return 'Livres';
  }

  /**
   *   \brief  Personnalise les menus
   *   \param  menu       Objet Menu
   *   \todo   Rodo - faire plus propre c'est trop goret
   */  
  function PersonnalizeMenu(&$menu)
  {
    $menu->remove_last();
    $menu->remove_last();
  }
  /**
   *    \brief      Creation
   *    \param      id          Id livre
   */
  function Create($user,$datas)
  {
    $this->db->begin();

    $id = parent::Create($user);
    //$id = $this->create($user);


    $this->pages         = abs(trim($datas["pages"]));
    $this->px_feuillet   = str_replace(',','.',abs(trim($datas["px_feuillet"])));
    $this->px_couverture = str_replace(',','.',abs(trim($datas["px_couverture"])));
    $this->px_revient = $this->_calculate_prix_revient($pages, $px_couverture, $px_feuillet, $quant);
    $this->stock_loc     = trim($datas["stock_loc"]);

    if ($id > 0)
      {
	$error = 0;
      }

    if ( $error === 0 )
      {
	$sql = " INSERT INTO ".MAIN_DB_PREFIX."product_cnv_livre (rowid)";
	$sql.= " VALUES ('".$id."');";
	
	$result = $this->db->query($sql) ;
	if ($result)
	  {
	    $error = 0;
	  }
	else
	  {
	    $error = -6;
	  }
      }
    // Creation du contrat associe
    if ( $error === 0 )
      {
	$this->contrat = new ProductLivreContrat($this->db);

	$this->contrat->ref                = $this->ref.'-CL';
	$this->contrat->libelle            = 'Contrat';
	$this->contrat->price              = 0;
	$this->contrat->tva_tx             = 0;
	$this->contrat->type               = 0;
	$this->contrat->status             = 0;
	$this->contrat->description        = 'Droits du livre';
	$this->contrat->seuil_stock_alerte = $_POST["seuil_stock_alerte"];
	$this->contrat->canvas             = 'livrecontrat';

	$contrat_id = $this->contrat->Create($user, $this->id, $datas);

	if ($contrat_id > 0)
	  {
	    $this->add_subproduct($this->contrat->id);
	  }
      }
    // Creation du produit couverture
    if ( $error === 0 )
      {
	$this->couverture = new Product($this->db);

	$this->couverture->ref                = $this->ref.'-CO';
	$this->couverture->libelle            = 'Couverture';
	$this->couverture->price              = 0;
	$this->couverture->tva_tx             = 0;
	$this->couverture->type               = 0;
	$this->couverture->status             = 0;
	$this->couverture->description        = 'Couverture du livre';
	$this->couverture->seuil_stock_alerte = $_POST["seuil_stock_alerte"];
	$this->couverture->canvas             = 'livrecouverture';

	$this->couverture_id = $this->couverture->create($user);

	if ($this->couverture_id > 0)
	  {
	    $this->add_subproduct($this->couverture_id);
	  }
      }

    if ( $error === 0 )
      {
	$error = $this->UpdateCanvas($datas);
      }

    if ( $error === 0 )
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
  /**
   *    \brief      Supression
   *    \param      id          Id livre
   */
  function DeleteCanvas($id)
  {
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."product_cnv_livre ";
    $sql.= " WHERE rowid = '".$id."';";

    $result = $this->db->query($sql) ;

    return 0;
  }
  /**
   *    \brief      Lecture des donnees dans la base
   *    \param      id          Id livre ('' par defaut)
   *    \param      ref         Reference du livre ('' par defaut)
   */
  function FetchCanvas($id='', $ref='')
  {
    $result = $this->fetch($id,$ref);

    if ($result >= 0)
      {
	$sql = "SELECT rowid,isbn,ean,pages,px_feuillet,fk_couverture,format,fk_contrat";
	$sql.= " FROM ".MAIN_DB_PREFIX."product_cnv_livre";
	if ($id) $sql.= " WHERE rowid = '".$id."'";
	if ($ref) $sql.= " WHERE ref = '".addslashes($ref)."'";
	
	$result = $this->db->query($sql) ;

	if ( $result )
	  {
	    $result = $this->db->fetch_array();

	    $this->isbn               = $result["isbn"];
	    $this->ean                = $result["ean"];
	    $this->pages              = $result["pages"];
	    $this->format             = $result["format"];
	    $this->px_feuillet        = $result["px_feuillet"];
	    $this->couverture_id      = $result["fk_couverture"];

	    $this->db->free();
	  }

	$this->contrat = new ProductLivreContrat($this->db);
	$this->contrat->FetchCanvas($result["fk_contrat"]);
      }

    return $result;
  }
  /**
   *    \brief      Mise a jour des donnees dans la base
   *    \param      datas        Tableau de donnees
   */
  function UpdateCanvas($datas)
  {
    dolibarr_syslog("ProductLivre::UpdateCanvas");

    $isbna = trim($datas["isbna"]);
    $isbnb = trim($datas["isbnb"]);

    $sp = 9 - (strlen($isbna) + strlen($isbnb) );
    $isbnc = substr( str_repeat('0',10) . $datas["isbnc"], -$sp , $sp); // on complete a 10

    $key = $this->calculate_isbn_key($isbna.$isbnb.$isbnc);

    $isbn = $isbna.'-'.$isbnb.'-'.$isbnc.'-'.$key;

    $ean = '978'.$isbna.$isbnb.$isbnc;

    $ean = $ean . $this->calculate_ean_key($ean);

    $this->pages         = abs(trim($datas["pages"]));
    $this->px_feuillet   = str_replace(',','.',abs(trim($datas["px_feuillet"])));
    $this->px_couverture = str_replace(',','.',abs(trim($datas["px_couverture"])));

    $this->px_revient = $this->_calculate_prix_revient($pages, $px_couverture, $px_feuillet, $quant);

    $this->stock_loc     = trim($datas["stock_loc"]);
    $format        = trim($datas["format"]);

    $sql = "UPDATE ".MAIN_DB_PREFIX."product_cnv_livre ";
    $sql .= " SET isbn = '$isbn'";
    $sql .= " , ean = '$ean'";
    $sql .= " , pages         = '".$this->pages."'";
    $sql .= " , px_feuillet   = '".$this->px_feuillet."'";
    $sql .= " , px_revient    = '".$this->px_revient."'";
    $sql .= " , fk_couverture = '".$this->couverture->id."'";
    $sql .= " , fk_contrat    = '".$this->contrat->id."'";
    $sql .= " , format        = '$format'";
    $sql .= " WHERE rowid = " . $this->id;

    if ( $this->db->query($sql) )
      {
	$error = 0;
      }
    else
      {
	$error = -1;
      }

    $this->contrat->UpdateCanvas($datas);
    
    return $error;
  }
  /**
   *    \brief      Calcule le prix de revient d'un livre
   *    \param      pages     Nombre de pages
   *    \param      couv      Prix de la couverture
   *    \param      feuil     Prix d'un feuillet
   *    \param      quant     Nombre de drois achetes
   *    \note       source http://fr.wikipedia.org/wiki/ISBN
   */
  function _calculate_prix_revient($pages, $couv, $feuil, $quant)
  {
    $cost = ( ($pages / 2 * $feuil) + $couv ) * $quant;

    return $cost;
  }
  /**
   *    \brief      Calcule la clef d'un numero ISBN
   *    \param      isbn        Clef International Standard Book Number
   *    \note       source http://fr.wikipedia.org/wiki/ISBN
   */
  function calculate_isbn_key($isbn)
  {
    $sum = 0;
    for ($i = 0 ; $i < 9 ; $i++)
      {
	$sum += $isbn{$i} * (10 - $i);
      }

    $key = 11 - ($sum % 11);

    if ($key == 0)
      $key = 1;

    if ($key == 11)
      $key = 'X';

    return $key;
  }
  /**
   *    \brief      Calcule la clef d'un numero EAN 13
   *    \param      ean        Clef EAN
   *    \note       source http://fr.wikipedia.org/wiki/ISBN
   */
  function calculate_ean_key($ean)
  {
    $sum = 0;
    for ($i = 0 ; $i < 12 ; $i = $i+2)
      {
	$sum += $ean{$i};
      }
    for ($i = 1 ; $i < 12 ; $i = $i+2)
      {
	$sum += 3 * $ean{$i};
      }

    $key = (10 - ($sum % 10));

    return $key;
  }
   /**
   *    \brief      Assigne les valeurs pour les templates Smarty
   *    \param      smarty     Instance de smarty
   */
  function assign_smarty_values(&$smarty)
  {
    if ($this->errno == 257)
      {
	$smarty->assign('class_normal_ref', 'error');
	$smarty->assign('class_focus_ref',  'focuserr');
      }
    else
      {
	$smarty->assign('class_normal_ref', 'normal');
	$smarty->assign('class_focus_ref',  'focus');
      }

    $smarty->assign('prod_id',          $this->id);
    $smarty->assign('prod_ref',         $this->ref);
    $smarty->assign('prod_label',       $this->libelle);
    $smarty->assign('prod_note',        $this->note);
    $smarty->assign('prod_description', $this->description);
    $smarty->assign('prod_canvas',      $this->canvas);

    $smarty->assign('prod_isbn',     $this->isbn);

    $isbn_parts = explode('-',$this->isbn);
    
    $smarty->assign('prod_isbna',    $isbn_parts[0]);
    $smarty->assign('prod_isbnb',    $isbn_parts[1]);
    $smarty->assign('prod_isbnc',    $isbn_parts[2]);
    $smarty->assign('prod_ean',      $this->ean);

    $smarty->assign('prod_isbn13',           '978-'.substr($this->isbn,0,12).substr($this->ean,-1,1));

    $smarty->assign('prod_tva_tx',            $this->tva_tx);

    $smarty->assign('prod_pages',             $this->pages);
    $smarty->assign('prod_format',            $this->format);
    $smarty->assign('prod_pxfeuil',           $this->px_feuillet);

    $smarty->assign('prod_pxcouv',            $this->couverture->price);

    $smarty->assign('prod_weight',            $this->weight);
    $smarty->assign('prod_weight_units',      $this->weight_units);

    $smarty->assign('prod_pxrevient',         price($this->px_revient));
    $smarty->assign('prod_pxvente',           price($this->price_ttc));

    $smarty->assign('livre_contrat_taux',     $this->contrat->taux);
    $smarty->assign('livre_contrat_duree',    $this->contrat->duree);
    $smarty->assign('livre_contrat_quant',    $this->contrat->quantite);
    $smarty->assign('livre_contrat_date_app', $this->contrat->date_app);

    $smarty->assign('prod_stock_loc',         $this->stock_loc);

    $smarty->assign('prod_stock_reel',        $this->stock_reel);
    $smarty->assign('prod_stock_dispo',       ($this->stock_reel - $this->stock_in_command));
    $smarty->assign('prod_stock_in_command',  $this->stock_in_command);
    $smarty->assign('prod_stock_alert',       $this->seuil_stock_alerte);

    if ($this->status==1)
      $smarty->assign('prod_statut', 'En vente');

    if ($this->seuil_stock_alerte > ($this->stock_reel - $this->stock_in_command) && $this->status == 1)
      {
	$smarty->assign('smarty_stock_dispo_class', 'class="alerte"');
      }
  }

  /* 
   * Fetch Datas Liste
   *
   *
   */
  function LoadListDatas($limit, $offset, $sortfield, $sortorder)
  {
    $sql = 'SELECT p.rowid, p.ref, p.label, pl.px_feuillet as price, p.fk_product_type,';
    $sql.= ' p.duration, p.envente as statut, p.stock_loc';
    $sql.= ',pl.pages';
    $sql.= ',SUM(fd.qty) as ventes';
    $sql.= ' FROM '.MAIN_DB_PREFIX.'product_cnv_livre as pl,'.MAIN_DB_PREFIX.'product as p';  
    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'facturedet as fd ON fd.fk_product = p.rowid';
    $sql .= " WHERE p.rowid=pl.rowid ";

    if ($sall)
      {
	$sql .= " AND (p.ref like '%".$sall."%' OR p.label like '%".$sall."%' OR p.description like '%".$sall."%' OR p.note like '%".$sall."%')";
      }

    if ($sref)
      {
	$sql .= " AND p.ref like '%".$sref."%'";
      }

    if ($snom)
      {
	$sql .= " AND p.label like '%".$snom."%'";
      }

    if (isset($_GET["envente"]) && strlen($_GET["envente"]) > 0)
      {
	$sql .= " AND p.envente = ".$_GET["envente"];
      }
    $sql.= " GROUP BY p.rowid";
    $sql.= " ORDER BY $sortfield $sortorder ";
    $sql.= $this->db->plimit($limit + 1 ,$offset);

    $this->list_datas = array();    

    $resql = $this->db->query($sql) ;

    if ($resql)
      {
	$num = $this->db->num_rows($resql);

	$i = 0;
	while ($i < min($num,$limit))
	  {
	    $datas = array();
	    $obj = $this->db->fetch_object($resql);
	    
	    $datas["id"]        = $obj->rowid;
	    $datas["ref"]       = $obj->ref;
	    $datas["titre"]     = $obj->label;
	    $datas["casier"]    = 0;
	    $datas["entrepot"]  = 0;
	    $datas["ventes"]    = $obj->ventes;
	    $datas["stock"]     = 0;
	    $datas["stock_loc"] = stripslashes($obj->stock_loc);
	    $datas["pages"]     = $obj->pages;
	    $datas["prix"]      = price($obj->price);
	    $datas["valo"]      = 0;
	    
	    array_push($this->list_datas,$datas);
	    
	    $i++;
	  }
	$this->db->free($resql);
      }
    else
      {
	print $sql;
      }
  }
}
?>
