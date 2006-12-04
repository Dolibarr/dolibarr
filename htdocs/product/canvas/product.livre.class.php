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
    $this->menus[1][0] = DOL_URL_ROOT."/product/liste.php?action=create&amp;type=0&amp;canvas=livre";
    $this->menus[1][1] = 'ListBook';
    $this->menus[2][0] = DOL_URL_ROOT."/product/liste.php?action=create&amp;type=0&amp;canvas=livrecontrat";
    $this->menus[2][1] = 'ListContract';
    $this->menus[3][0] = DOL_URL_ROOT."/product/liste.php?action=create&amp;type=0&amp;canvas=livrecouverture";
    $this->menus[3][1] = 'ListCover';
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
  function CreateCanvas($user,$datas)
  {
    $this->db->begin();

    $id = $this->create($user);

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

	$contrat_id = $this->contrat->CreateCanvas($user, $this->id, $datas);

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
    $isbna = trim($datas["isbna"]);
    $isbnb = trim($datas["isbnb"]);

    $sp = 9 - (strlen($isbna) + strlen($isbnb) );
    $isbnc = substr( str_repeat('0',10) . $datas["isbnc"], -$sp , $sp); // on complete a 10

    $key = $this->calculate_isbn_key($isbna.$isbnb.$isbnc);

    $isbn = $isbna.'-'.$isbnb.'-'.$isbnc.'-'.$key;

    $ean = '978'.$isbna.$isbnb.$isbnc;

    $ean = $ean . $this->calculate_ean_key($ean);

    $pages         = abs(trim($datas["pages"]));
    $px_feuillet   = str_replace(',','.',abs(trim($datas["px_feuillet"])));
    $px_couverture = str_replace(',','.',abs(trim($datas["px_couverture"])));

    $px_revient = $this->_calculate_prix_revient($pages, $px_couverture, $px_feuillet, $quant);

    $stock_loc     = trim($datas["stock_loc"]);
    $format        = trim($datas["format"]);

    $sql = "UPDATE ".MAIN_DB_PREFIX."product_cnv_livre ";
    $sql .= " SET isbn = '$isbn'";
    $sql .= " , ean = '$ean'";
    $sql .= " , pages         = '$pages'";
    $sql .= " , px_feuillet   = '$px_feuillet'";
    $sql .= " , px_revient    = '$px_revient'";
    $sql .= " , fk_couverture = '".$this->couverture->id."'";
    $sql .= " , fk_contrat    = '".$this->contrat->id."'";
    $sql .= " , stock_loc     = '$stock_loc'";
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
  function assign_values(&$smarty)
  {
    $smarty->assign('prod_canvas',  'livre');
    $smarty->assign('prod_id',      $this->id);

    $smarty->assign('prod_isbn',    $this->isbn);

    $isbn_parts = explode('-',$this->isbn);
    
    $smarty->assign('prod_isbna',     $isbn_parts[0]);
    $smarty->assign('prod_isbnb',     $isbn_parts[1]);
    $smarty->assign('prod_isbnc',     $isbn_parts[2]);

    $smarty->assign('prod_ean',       $this->ean);

    $smarty->assign('prod_isbn13',    '978-'.substr($this->isbn,0,12).substr($this->ean,-1,1));

    $smarty->assign('prod_ref',       $this->ref);
    $smarty->assign('prod_pages',     $this->pages);
    $smarty->assign('prod_format',    $this->format);
    $smarty->assign('prod_pxfeuil',   $this->px_feuillet);

    $smarty->assign('prod_pxcouv',    $this->couverture->price);

    $smarty->assign('prod_pxrevient', price($this->px_revient));
    $smarty->assign('prod_pxvente',   price($this->price));
    $smarty->assign('prod_label',     $this->libelle);

    $smarty->assign('prod_contrat_taux',     $this->contrat->taux);
    $smarty->assign('prod_contrat_duree',    $this->contrat_duree);
    $smarty->assign('prod_contrat_quant',    $this->contrat_quantite);

    $smarty->assign('prod_stock_reel',        $this->stock_reel);
    $smarty->assign('prod_stock_dispo',       ($this->stock_reel - $this->stock_in_command));
    $smarty->assign('prod_stock_in_command',  $this->stock_in_command);
    $smarty->assign('prod_stock_alert',       $this->seuil_stock_alerte);
  }

}
?>
