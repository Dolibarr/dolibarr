<?php
/* Copyright (C) 2011 Florian HENRY  <florian.henry.mail@gmail.com>
 *
 * Code of this page is mostly inspired from module category
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
 *      \file       htdocs/assortment/class/html.formassortement.class.php
 *      \ingroup    assortement
 *      \brief      Fichier de la classe des fonctions predefinie de composants html assotitments
 *		\version	$Id: html.formassortment.class.php,v 1.3 2010/12/13 13:16:02 eldy Exp $
 */
 
// Put here all includes required by your class file
require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
require_once(DOL_DOCUMENT_ROOT_ALT."/assortment/class/assortment_category.class.php");

/**
 *      \class      FormAsoortment
 *      \brief      Classe permettant la generation de composants html assortment
 */
class FormAssortment extends Form
{

  /**
     *  Return list of products for customer in Ajax if Ajax activated or go to select_produits_do
     *  @param		selected				Preselected products
     *  @param		htmlname				Name of HTML seletc field (must be unique in page)
     *  @param		filtertype				Filter on product type (''=nofilter, 0=product, 1=service)
     *  @param		limit					Limit on number of returned lines
     *  @param		price_level				Level of price to show
     *  @param		status					-1=Return all products, 0=Products not on sell, 1=Products on sell
     *  @param		finished				2=all, 1=finished, 0=raw material
     *  @param		$selected_input_value	Value of preselected input text (with ajax)
     */
    function select_produits_assort($selected='',$htmlname='productid',$filtertype='',$limit=20,$price_level=0,$status=1,$finished=2,$selected_input_value='',$hidelabel=0,$socid=0)
    {
        global $langs,$conf;

        $price_level = (! empty($price_level) ? $price_level : 0);

        if ($conf->global->PRODUIT_USE_SEARCH_TO_SELECT)
        {
        	if ($selected && empty($selected_input_value))
        	{
        		require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
        		$product = new Product($this->db);
        		$product->fetch($selected);
        		$selected_input_value=$product->ref;
        	}
            // mode=1 means customers products
            print ajax_autocompleter($selected, $htmlname, DOL_URL_ROOT.'/product/ajaxproducts.php', 'htmlname='.$htmlname.'&outjson=1&price_level='.$price_level.'&type='.$filtertype.'&mode=1&status='.$status.'&finished='.$finished.'&socid='.$socid, $conf->global->PRODUIT_USE_SEARCH_TO_SELECT);
            if (! $hidelabel) print $langs->trans("RefOrLabel").' : ';
            print '<input type="text" size="20" name="search_'.$htmlname.'" id="search_'.$htmlname.'" value="'.$selected_input_value.'" />';
        }
        else
        {
            $this->select_produits_do_assort($selected,$htmlname,$filtertype,$limit,$price_level,'',$status,$finished,0,$socid);
        }

        print '<br>';
    }

    /**
     *	Return list of products for a customer according Assortment of customer
     *	@param      selected        Preselected product
     *	@param      htmlname        Name of select html
     *  @param		filtertype      Filter on product type (''=nofilter, 0=product, 1=service)
     *	@param      limit           Limite sur le nombre de lignes retournees
     *	@param      price_level     Level of price to show
     * 	@param      filterkey       Filter on product
     *	@param		status          -1=Return all products, 0=Products not on sell, 1=Products on sell
     *  @param      finished        Filter on finished field: 2=No filter
     *  @param      disableout      Disable print output
     *  @param      socid      	    socid to filter assortment
     *  @return     array           Array of keys for json
     */
    function select_produits_do_assort($selected='',$htmlname='productid',$filtertype='',$limit=20,$price_level=0,$filterkey='',$status=1,$finished=2,$disableout=0,$socid=0)
    {
        global $langs,$conf,$user,$db;

        $sql = "SELECT ";
        $sql.= " p.rowid, p.label, p.ref, p.fk_product_type, p.price, p.price_ttc, p.price_base_type, p.duration, p.stock";
        // Multilang : we add translation
        if ($conf->global->MAIN_MULTILANGS)
        {
            $sql.= ", pl.label as label_translated";
        }
        $sql.= " FROM ".MAIN_DB_PREFIX."product as p";
        // Multilang : we add translation
        if ($conf->global->MAIN_MULTILANGS)
        {
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_lang as pl ON pl.fk_product = p.rowid AND pl.lang='". $langs->getDefaultLang() ."'";
        }

	    //ADD by F.Henry  : Module assortment
        if ($conf->global->MAIN_MODULE_ASSORTMENT==1 && $conf->global->ASSORTMENT_ON_ORDER==1 && $socid!=0)
        {
        	$sql .= " INNER JOIN ".MAIN_DB_PREFIX."assortment as assort ON assort.fk_prod=p.rowid and assort.fk_soc='".$socid."'";        	
        }

        $sql.= ' WHERE p.entity IN (0,'.(! empty($conf->entities['product']) ? $conf->entities['product'] : $conf->entity).')';
        if($finished == 0)
        {
            $sql.= " AND p.finished = ".$finished;
        }
        elseif($finished == 1)
        {
            $sql.= " AND p.finished = ".$finished;
            if ($status >= 0)  $sql.= " AND p.tosell = ".$status;
        }
        elseif($status >= 0)
        {
            $sql.= " AND p.tosell = ".$status;
        }
        if (strval($filtertype) != '') $sql.=" AND p.fk_product_type=".$filtertype;
        // Add criteria on ref/label
        if ($filterkey && $filterkey != '')
        {
	        if (! empty($conf->global->PRODUCT_DONOTSEARCH_ANYWHERE))   // Can use index
	        {
	            $sql.=" AND (p.ref LIKE '".$filterkey."%' OR p.label LIKE '".$filterkey."%'";
	            if ($conf->global->MAIN_MULTILANGS) $sql.=" OR pl.label LIKE '".$filterkey."%'";
	            $sql.=")";
	        }
	        else
	        {
	            $sql.=" AND (p.ref LIKE '%".$filterkey."%' OR p.label LIKE '%".$filterkey."%'";
	            if ($conf->global->MAIN_MULTILANGS) $sql.=" OR pl.label LIKE '%".$filterkey."%'";
	            $sql.=")";
	        }
        }
        $sql.= $db->order("p.ref");
        $sql.= $db->plimit($limit);

        // Build output string
        $outselect='';
        $outjson=array();

        dol_syslog("FormAssortment::select_produits_do_assort search product sql=".$sql, LOG_DEBUG);
        $result=$this->db->query($sql);
        if ($result)
        {
            $num = $this->db->num_rows($result);

            $outselect.='<select class="flat" name="'.$htmlname.'">';
			$outselect.='<option value="0" selected="selected">&nbsp;</option>';

            $i = 0;
            while ($num && $i < $num)
            {
                $outkey='';
                $outval='';
                $outref='';

                $objp = $this->db->fetch_object($result);

                $label=$objp->label;
                if (! empty($objp->label_translated)) $label=$objp->label_translated;
                if ($filterkey && $filterkey != '') $label=preg_replace('/('.preg_quote($filterkey).')/i','<strong>$1</strong>',$label,1);

                $outkey=$objp->rowid;
                $outref=$objp->ref;

                $opt = '<option value="'.$objp->rowid.'"';
                $opt.= ($objp->rowid == $selected)?' selected="selected"':'';
                if ($conf->stock->enabled && $objp->fk_product_type == 0 && isset($objp->stock))
                {
                    if ($objp->stock > 0)
                    {
                        $opt.= ' style="background-color:#32CD32; color:#F5F5F5;"';
                    }
                    else if ($objp->stock <= 0)
                    {
                        $opt.= ' style="background-color:#FF0000; color:#F5F5F5;"';
                    }
                }
                $opt.= '>';
                $opt.= $langs->convToOutputCharset($objp->ref).' - '.$langs->convToOutputCharset(dol_trunc($label,32)).' - ';

                $objRef = $objp->ref;
                if ($filterkey && $filterkey != '') $objRef=preg_replace('/('.preg_quote($filterkey).')/i','<strong>$1</strong>',$objRef,1);
                $outval.=$objRef.' - '.dol_trunc($label,32).' - ';

                $found=0;
                $currencytext=$langs->trans("Currency".$conf->monnaie);
                $currencytextnoent=$langs->transnoentities("Currency".$conf->monnaie);
                if (dol_strlen($currencytext) > 10) $currencytext=$conf->monnaie;	// If text is too long, we use the short code
                if (dol_strlen($currencytextnoent) > 10) $currencytextnoent=$conf->monnaie;   // If text is too long, we use the short code

                // Multiprice
                if ($price_level >= 1)		// If we need a particular price level (from 1 to 6)
                {
                    $sql= "SELECT price, price_ttc, price_base_type ";
                    $sql.= "FROM ".MAIN_DB_PREFIX."product_price ";
                    $sql.= "WHERE fk_product='".$objp->rowid."'";
                    $sql.= " AND price_level=".$price_level;
                    $sql.= " ORDER BY date_price";
                    $sql.= " DESC limit 1";

                    dol_syslog("FormAssortment::select_produits_do_assort search price for level '.$price_level.' sql=".$sql);
                    $result2 = $this->db->query($sql);
                    if ($result2)
                    {
                        $objp2 = $this->db->fetch_object($result2);
                        if ($objp2)
                        {
                            $found=1;
                            if ($objp2->price_base_type == 'HT')
                            {
                                $opt.= price($objp2->price,1).' '.$currencytext.' '.$langs->trans("HT");
                                $outval.= price($objp2->price,1).' '.$currencytextnoent.' '.$langs->transnoentities("HT");
                            }
                            else
                            {
                                $opt.= price($objp2->price_ttc,1).' '.$currencytext.' '.$langs->trans("TTC");
                                $outval.= price($objp2->price_ttc,1).' '.$currencytextnoent.' '.$langs->transnoentities("TTC");
                            }
                        }
                    }
                    else
                    {
                        dol_print_error($this->db);
                    }
                }

                // If level no defined or multiprice not found, we used the default price
                if (! $found)
                {
                    if ($objp->price_base_type == 'HT')
                    {
                        $opt.= price($objp->price,1).' '.$currencytext.' '.$langs->trans("HT");
                        $outval.= price($objp->price,1).' '.$currencytextnoent.' '.$langs->transnoentities("HT");
                    }
                    else
                    {
                        $opt.= price($objp->price_ttc,1).' '.$currencytext.' '.$langs->trans("TTC");
                        $outval.= price($objp->price_ttc,1).' '.$currencytextnoent.' '.$langs->transnoentities("TTC");
                    }
                }

                if ($conf->stock->enabled && isset($objp->stock) && $objp->fk_product_type == 0)
                {
                    $opt.= ' - '.$langs->trans("Stock").':'.$objp->stock;
                    $outval.=' - '.$langs->transnoentities("Stock").':'.$objp->stock;
                }

                if ($objp->duration)
                {
                    $duration_value = substr($objp->duration,0,dol_strlen($objp->duration)-1);
                    $duration_unit = substr($objp->duration,-1);
                    if ($duration_value > 1)
                    {
                        $dur=array("h"=>$langs->trans("Hours"),"d"=>$langs->trans("Days"),"w"=>$langs->trans("Weeks"),"m"=>$langs->trans("Months"),"y"=>$langs->trans("Years"));
                    }
                    else
                    {
                        $dur=array("h"=>$langs->trans("Hour"),"d"=>$langs->trans("Day"),"w"=>$langs->trans("Week"),"m"=>$langs->trans("Month"),"y"=>$langs->trans("Year"));
                    }
                    $opt.= ' - '.$duration_value.' '.$langs->trans($dur[$duration_unit]);
                    $outval.=' - '.$duration_value.' '.$langs->transnoentities($dur[$duration_unit]);
                }

                $opt.= "</option>\n";

                // Add new entry
                // "key" value of json key array is used by jQuery automatically as selected value
                // "label" value of json key array is used by jQuery automatically as text for combo box
                $outselect.=$opt;
                array_push($outjson,array('key'=>$outkey,'value'=>$outref,'label'=>$outval));

                $i++;
            }

            $outselect.='</select>';

            $this->db->free($result);

            if (empty($disableout)) print $outselect;
            return $outjson;
        }
        else
        {
            dol_print_error($db);
        }
    }

    /**
     *	Return list of products for customer in Ajax if Ajax activated or go to select_produits_fournisseurs_do_assort
     *	@param		socid			Id third party
     *	@param     	selected        Preselected product
     *	@param     	htmlname        Name of HTML Select
     *  @param		filtertype      Filter on product type (''=nofilter, 0=product, 1=service)
     *	@param     	filtre          For a SQL filter
     */
    function select_produits_fournisseurs_assort($socid,$selected='',$htmlname='productid',$filtertype='',$filtre)
    {
        global $langs,$conf;
        if ($conf->global->PRODUIT_USE_SEARCH_TO_SELECT)
        {
            // mode=2 means suppliers products
            print ajax_autocompleter('', $htmlname, DOL_URL_ROOT.'/product/ajaxproducts.php', 'htmlname='.$htmlname.'&outjson=1&price_level='.$price_level.'&type='.$filtertype.'&mode=2&status='.$status.'&finished='.$finished.'&socid='.$socid, $conf->global->PRODUIT_USE_SEARCH_TO_SELECT);
            print $langs->trans("RefOrLabel").' : <input type="text" size="16" name="search_'.$htmlname.'" id="search_'.$htmlname.'">';
            print '<br>';
        }
        else
        {
            $this->select_produits_fournisseurs_do_assort($socid,$selected,$htmlname,$filtertype,$filtre,'',-1,0);
        }
    }

    /**
     *	Retourne la liste des produits de fournisseurs for assortment
     *	@param		socid   		Id societe fournisseur (0 pour aucun filtre)
     *	@param      selected        Produit pre-selectionne
     *	@param      htmlname        Nom de la zone select
     *  @param		filtertype      Filter on product type (''=nofilter, 0=product, 1=service)
     *	@param      filtre          Pour filtre sql
     *	@param      filterkey       Filtre des produits
     *  @param      status          -1=Return all products, 0=Products not on sell, 1=Products on sell
     *  @param      disableout      Disable print output
     *  @param		socid			Id third party
     *  @return     array           Array of keys for json
     */
    function select_produits_fournisseurs_do_assort($socid,$selected='',$htmlname='productid',$filtertype='',$filtre='',$filterkey='',$statut=-1,$disableout=0)
    {
        global $langs,$conf;

        $langs->load('stocks');

        $sql = "SELECT p.rowid, p.label, p.ref, p.price, p.duration,";
        $sql.= " pf.ref_fourn,";
        $sql.= " pfp.rowid as idprodfournprice, pfp.price as fprice, pfp.quantity, pfp.unitprice,";
        $sql.= " s.nom";
        $sql.= " FROM ".MAIN_DB_PREFIX."product as p";
        
        //ADD by F.Henry  : Module assortment
        if ($conf->global->MAIN_MODULE_ASSORTMENT==1 && $conf->global->ASSORTMENT_ON_ORDER_FOUR==1 && $socid!=0)
        {
        	$sql .= " INNER JOIN ".MAIN_DB_PREFIX."assortment as assort ON assort.fk_prod=p.rowid and assort.fk_soc='".$socid."'";
        	
        }
        
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur as pf ON p.rowid = pf.fk_product";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON pf.fk_soc = s.rowid";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON pf.rowid = pfp.fk_product_fournisseur";
        $sql.= " WHERE p.entity = ".$conf->entity;
        $sql.= " AND p.tobuy = 1";
        if ($socid) $sql.= " AND pf.fk_soc = ".$socid;
        if (strval($filtertype) != '') $sql.=" AND p.fk_product_type=".$filtertype;
        if (! empty($filtre)) $sql.=" ".$filtre;
        // Add criteria on ref/label
        if ($filterkey && $filterkey != '')
        {
	        if (! empty($conf->global->PRODUCT_DONOTSEARCH_ANYWHERE))
	        {
	            $sql.=" AND (pf.ref_fourn LIKE '".$filterkey."%' OR p.ref LIKE '".$filterkey."%' OR p.label LIKE '".$filterkey."%')";
	        }
	        else
	        {
	            $sql.=" AND (pf.ref_fourn LIKE '%".$filterkey."%' OR p.ref LIKE '%".$filterkey."%' OR p.label LIKE '%".$filterkey."%')";
	        }
        }
        $sql.= " ORDER BY pf.ref_fourn DESC";

        // Build output string
        $outselect='';
        $outjson=array();

        dol_syslog("FormAssortment::select_produits_fournisseurs_do_assort sql=".$sql,LOG_DEBUG);
        $result=$this->db->query($sql);
        if ($result)
        {

            $num = $this->db->num_rows($result);

            $outselect.='<select class="flat" id="select'.$htmlname.'" name="'.$htmlname.'">';
            if (! $selected) $outselect.='<option value="0" selected="selected">&nbsp;</option>';
            else $outselect.='<option value="0">&nbsp;</option>';

            $i = 0;
            while ($i < $num)
            {
                $outkey='';
                $outval='';
                $outref='';

                $objp = $this->db->fetch_object($result);

                $outkey=$objp->idprodfournprice;
                $outref=$objp->ref;

                $opt = '<option value="'.$objp->idprodfournprice.'"';
                if ($selected == $objp->idprodfournprice) $opt.= ' selected="selected"';
                if ($objp->fprice == '') $opt.=' disabled="disabled"';
                $opt.= '>';

                $objRef = $objp->ref;
                if ($filterkey && $filterkey != '') $objRef=preg_replace('/('.preg_quote($filterkey).')/i','<strong>$1</strong>',$objRef,1);
                $objRefFourn = $objp->ref_fourn;
                if ($filterkey && $filterkey != '') $objRefFourn=preg_replace('/('.preg_quote($filterkey).')/i','<strong>$1</strong>',$objRefFourn,1);
                $label = $objp->label;
                if ($filterkey && $filterkey != '') $label=preg_replace('/('.preg_quote($filterkey).')/i','<strong>$1</strong>',$label,1);

                $opt.=$langs->convToOutputCharset($objp->ref).' ('.$langs->convToOutputCharset($objp->ref_fourn).') - ';
                $outval.=$objRef.' ('.$objRefFourn.') - ';
                $opt.=$langs->convToOutputCharset(dol_trunc($objp->label,18)).' - ';
                $outval.=dol_trunc($label,18).' - ';

                if ($objp->fprice != '') 	// Keep != ''
                {
                    $currencytext=$langs->trans("Currency".$conf->monnaie);
                    $currencytextnoent=$langs->transnoentities("Currency".$conf->monnaie);
                    if (dol_strlen($currencytext) > 10) $currencytext=$conf->monnaie;   // If text is too long, we use the short code
                    if (dol_strlen($currencytextnoent) > 10) $currencytextnoent=$conf->monnaie;   // If text is too long, we use the short code

                    $opt.= price($objp->fprice).' '.$currencytext."/".$objp->quantity;
                    $outval.= price($objp->fprice).' '.$currencytextnoent."/".$objp->quantity;
                    if ($objp->quantity == 1)
                    {
                        $opt.= strtolower($langs->trans("Unit"));
                        $outval.=strtolower($langs->transnoentities("Unit"));
                    }
                    else
                    {
                        $opt.= strtolower($langs->trans("Units"));
                        $outval.=strtolower($langs->transnoentities("Units"));
                    }
                    if ($objp->quantity >= 1)
                    {
                        $opt.=" (".price($objp->unitprice).' '.$currencytext."/".strtolower($langs->trans("Unit")).")";
                        $outval.=" (".price($objp->unitprice).' '.$currencytextnoent."/".strtolower($langs->transnoentities("Unit")).")";
                    }
                    if ($objp->duration)
                    {
                        $opt .= " - ".$objp->duration;
                        $outval.=" - ".$objp->duration;
                    }
                    if (! $socid)
                    {
                        $opt .= " - ".dol_trunc($objp->nom,8);
                        $outval.=" - ".dol_trunc($objp->nom,8);
                    }
                }
                else
                {
                    $opt.= $langs->trans("NoPriceDefinedForThisSupplier");
                    $outval.=$langs->transnoentities("NoPriceDefinedForThisSupplier");
                }
                $opt .= "</option>\n";

                // Add new entry
                // "key" value of json key array is used by jQuery automatically as selected value
                // "label" value of json key array is used by jQuery automatically as text for combo box
                $outselect.=$opt;
                array_push($outjson,array('key'=>$outkey,'value'=>$outref,'label'=>$outval));

                $i++;
            }
            $outselect.='</select>';

            $this->db->free($result);

            if (empty($disableout)) print $outselect;
            return $outjson;
        }
        else
        {
            dol_print_error($this->db);
        }
    }
    
    function form_manage_assortment($db,$object,$typeid)
    {
    	global $user,$langs,$html,$bc,$conf;
    	if ($user->rights->assortment->creer)
		{   
			//Manage Assortment from Customer or Supplier Card
			if ($typeid == 1) 
			{
				// If the setting is to set assortment by customer category
				if ($conf->global->ASSORTMENT_BY_CAT == 1)
				{
					// Add product by category to customer/supplier assortment
					$title = $langs->trans("ProductsCategoryAdd");
				
					print '<br>';
					print_fiche_titre($title,'','');
					print '<form method="post" action="'.DOL_URL_ROOT.'/assortment/assortment.php?socid='.$object->id.'">';
					print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
					print '<input type="hidden" name="type" value="'.$typeid.'">';
					print '<input type="hidden" name="socid" value="'.$object->id.'">';
					print '<input type="hidden" name="action" value="'.$langs->trans("Add").'">';
					print '<input type="hidden" name="typeaction" value="cat">';
					print '<table class="noborder" width="100%">';
					print '<tr class="liste_titre"><td width="40%">';
					
					print $langs->trans("Category").' :';
			
					//Display product Category
					print $html->select_all_categories(0);
							
					print '</td><td>';
					
					print '<input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
					print '</td>';
					print '</tr>';
					print '</table>';
					print '</form>';
					print '<br/>';
					
					if ($user->rights->assortment->supprimer) 
					{
						// remoce all product category for the customers
						$title = $langs->trans("DeleteProductsCategory");
					
						print '<br>';
						print_fiche_titre($title,'','');
						print '<form method="post" action="'.DOL_URL_ROOT.'/assortment/assortment.php?socid='.$object->id.'&action=remove">';
						print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
						print '<input type="hidden" name="type" value="'.$typeid.'">';
						print '<input type="hidden" name="socid" value="'.$object->id.'">';
						print '<input type="hidden" name="action" value="remove">';
						print '<input type="hidden" name="typeaction" value="RemoveCatProd">';
						print '<table class="noborder" width="100%">';
						print '<tr class="liste_titre"><td width="40%">';
						
						print $langs->trans("Category").' :';
				
						//Display product Category link to this customer/supplier
						print $this->select_all_categories_assortment(0,$object->id);
								
						print '</td><td>';
						
						print '<input type="submit" class="button" value="'.$langs->trans("RemoveCat").'"></td>';
						print '</td>';
						print '</tr>';
						print '</table>';
						print '</form>';
						print '<br/>';
					}
				}
				
				// Add product to customer/supplier assortment
				print '<br>';
				$title = $langs->trans("AddProductAssortment");
				print_fiche_titre($title,'','');
				print '<form method="post" action="'.DOL_URL_ROOT.'/assortment/assortment.php?socid='.$object->id.'">';
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<input type="hidden" name="type" value="'.$typeid.'">';
				print '<input type="hidden" name="socid" value="'.$object->id.'">';
				print '<input type="hidden" name="action" value="'.$langs->trans("Add").'">';
				print '<input type="hidden" name="typeaction" value="nocat">';
				print '<table class="noborder" width="100%">';
				print '<tr class="liste_titre"><td width="40%">';
		
				//Display all product
				print $html->select_produits('','idprod','',$conf->product->limit_size);
				if (! $conf->global->PRODUIT_USE_SEARCH_TO_SELECT) print '<br>';
									
				print '</td><td>';
				
				print '<input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
				print '</td>';
				print '</tr>';
				print '</table>';
				print '</form>';
				print '<br/>';
			}
			
			//Manage Assortment from Product Card -> Display Customer and supplier
			if ($typeid == 0) 
			{
				// If the setting is to set assortment by customer category
				if ($conf->global->ASSORTMENT_BY_CAT == 1)
				{
					// Add customer by category to product assortment
	
					$title = $langs->trans("CustomersCategoryAssort");
	
					print '<br>';
					print_fiche_titre($title,'','');
					print '<form method="post" action="'.DOL_URL_ROOT.'/assortment/assortment.php">';
					print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
					print '<input type="hidden" name="type" value="'.$typeid.'">';
					print '<input type="hidden" name="prodid" value="'.$object->id.'">';
					print '<input type="hidden" name="action" value="'.$langs->trans("Add").'">';
					print '<input type="hidden" name="typeaction" value="cat">';
					print '<table class="noborder" width="100%">';
					print '<tr class="liste_titre"><td width="40%">';
					
					print $langs->trans("Category").' :';
			
					//Display customer Category
					print $html->select_all_categories(2);
							
					print '</td><td>';
					
					print '<input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
					print '</td>';
					print '</tr>';
					print '</table>';
					print '</form>';
					print '<br/>';
					
					if ($user->rights->assortment->supprimer) 
					{
						// remoce all product category for the customers
						$title = $langs->trans("DeleteCustomerCategory");
					
						print '<br>';
						print_fiche_titre($title,'','');
						print '<form method="post" action="'.DOL_URL_ROOT.'/assortment/assortment.php?action=remove">';
						print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
						print '<input type="hidden" name="type" value="'.$typeid.'">';
						print '<input type="hidden" name="prodid" value="'.$object->id.'">';
						print '<input type="hidden" name="action" value="remove">';
						print '<input type="hidden" name="typeaction" value="RemoveCatCustomer">';
						print '<table class="noborder" width="100%">';
						print '<tr class="liste_titre"><td width="40%">';
						
						print $langs->trans("Category").' :';
				
						//Display product Category of customers link to this product
						print $this->select_all_categories_assortment(2,$object->id);
								
						print '</td><td>';
						
						print '<input type="submit" class="button" value="'.$langs->trans("RemoveCat").'"></td>';
						print '</td>';
						print '</tr>';
						print '</table>';
						print '</form>';
						print '<br/>';
					}
					
					$title = $langs->trans("SuppliersCategoryAssort");		
					// Add supplier by category to product assortment
					print '<br>';
					print_fiche_titre($title,'','');
					print '<form method="post" action="'.DOL_URL_ROOT.'/assortment/assortment.php">';
					print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
					print '<input type="hidden" name="type" value="'.$typeid.'">';
					print '<input type="hidden" name="prodid" value="'.$object->id.'">';
					print '<input type="hidden" name="action" value="'.$langs->trans("Add").'">';
					print '<input type="hidden" name="typeaction" value="cat">';
					print '<table class="noborder" width="100%">';
					print '<tr class="liste_titre"><td width="40%">';
					
					print $langs->trans("Category").' :';
			
					//Display supplier Category
					print $html->select_all_categories(1);
							
					print '</td><td>';
					
					print '<input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
					print '</td>';
					print '</tr>';
					print '</table>';
					print '</form>';
					print '<br/>';
					
					if ($user->rights->assortment->supprimer) 
					{
						$title = $langs->trans("DeletesSupplierCategory");
					
						print '<br>';
						print_fiche_titre($title,'','');
						print '<form method="post" action="'.DOL_URL_ROOT.'/assortment/assortment.php?action=remove">';
						print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
						print '<input type="hidden" name="type" value="'.$typeid.'">';
						print '<input type="hidden" name="prodid" value="'.$object->id.'">';
						print '<input type="hidden" name="action" value="remove">';
						print '<input type="hidden" name="typeaction" value="RemoveCatSupplier">';
						print '<table class="noborder" width="100%">';
						print '<tr class="liste_titre"><td width="40%">';
						
						print $langs->trans("Category").' :';
				
						//Display product Category of supplier link to this product
						print $this->select_all_categories_assortment(1,$object->id);
								
						print '</td><td>';
						
						print '<input type="submit" class="button" value="'.$langs->trans("RemoveCat").'"></td>';
						print '</td>';
						print '</tr>';
						print '</table>';
						print '</form>';
						print '<br/>';
					}
			
				}
	
				// Add customer/supplier to product assortment
				print '<br>';
				print_fiche_titre($langs->trans("AddProdAssortCustSup"),'','');
				print '<form method="post" action="'.DOL_URL_ROOT.'/assortment/assortment.php">';
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<input type="hidden" name="type" value="'.$typeid.'">';
				print '<input type="hidden" name="prodid" value="'.$object->id.'">';
				print '<input type="hidden" name="action" value="'.$langs->trans("Add").'">';
				print '<input type="hidden" name="typeaction" value="nocat">';
				print '<table class="noborder" width="100%">';
				print '<tr class="liste_titre"><td width="40%">';
				
				print $langs->trans("Customer").'/'.$langs->trans("Supplier").' :';
		
				//Display customers
				print $html->select_company('','socid','',1);
									
				print '</td><td>';
				
				print '<input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
				print '</td>';
				print '</tr>';
				print '</table>';
				print '</form>';
				print '<br/>';
				
			}
		}
    }
    
    function list_assortment($objectid,$type)
    {
    	global $db,$bc,$langs,$conf,$user;
	
		//display assortment for Customer/Supllier
		if ($type==1)
		{
			$varbg=true;
			
	    	print_titre($langs->trans("ProductsAssortmentShort"));
		    print '<table class="noborder" width="100%">';
			
			$object= new Assortment($db);
			$assorts = $object->get_assortment_for_thirdparty($objectid);
			
			if (sizeof($assorts)>0)
			{
				print '<tr class="liste_titre">';
				print '<td>'.$langs->trans("Product").'</td>';
				
				if ($conf->global->ASSORTMENT_BY_CAT == 1) print '<td>'.$langs->trans("Category").'</td>';
				
				if ($user->rights->assortment->supprimer) print '<td>&nbsp;</td>';
				
				print '</tr>';
				
				foreach($assorts as $assort)
				{
					$varbg = !$varbg;
					
					print '<tr '.$bc[$varbg].'>';
					print '<td>'.$assort->getProdNomUrl().' - '.$assort->s_prod_name.'</td>';
					if ($conf->global->ASSORTMENT_BY_CAT == 1) print '<td>'.$assort->s_pathCateg.'</td>';
					
					if ($user->rights->assortment->supprimer) 
					{
						print '<td align="right">';
						print "<a href= '".DOL_URL_ROOT."/assortment/assortment.php?action=remove&id=".$assort->id."&socid=".$objectid."&type=".$type."'>";
						print img_delete($langs->trans("RemoveProdFromAssort")).' ';
						print $langs->trans("RemoveProdFromAssort")."</a>";
						print '</td>';
					}
					print '</tr>';
				}
			}
			else
			{
				print '<tr><td>'.$langs->trans("NoAssortmentForCustomer").'</td></tr>';
			}
			print '</table>';
		}
		
		//display assortment for Product
		if ($type==0)
		{
			$varbg=true;
			
	    	print_titre($langs->trans("Customer").' :');
		    print '<table class="noborder" width="100%">';
			
			$object= new Assortment($db);
			$assorts = $object->get_assortment_for_product($objectid,'customer');
			
			if (sizeof($assorts)>0)
			{
				print '<tr class="liste_titre">';
				print '<td>'.$langs->trans("Customer").'</td>';
				
				if ($conf->global->ASSORTMENT_BY_CAT == 1) print '<td>'.$langs->trans("Category").'</td>';
				
				if ($user->rights->assortment->supprimer) print '<td>&nbsp;</td>';
				
				print '</tr>';
				
				foreach($assorts as $assort)
				{
					$varbg = !$varbg;
					
					print '<tr '.$bc[$varbg].'>';
					print '<td>'.$assort->getSocNomUrl().'</td>';
					if ($conf->global->ASSORTMENT_BY_CAT == 1) print '<td>'.$assort->s_pathCateg.'</td>';
					
					if ($user->rights->assortment->supprimer) 
					{
						print '<td align="right">';
						print "<a href= '".DOL_URL_ROOT."/assortment/assortment.php?action=remove&id=".$assort->id."&prodid=".$objectid."&type=".$type."'>";
						print img_delete($langs->trans("RemoveCustomerFromAssort")).' ';
						print $langs->trans("RemoveCustomerFromAssort")."</a>";
						print '</td>';
					}
					print '</tr>';
				}
			}
			else
			{
				print '<tr><td>'.$langs->trans("NoAssortmentForProduct").'</td></tr>';
			}
			print '</table>';
			
			print '</br>';
			
			$varbg=true;
			
	    	print_titre($langs->trans("Supplier") .' :');
		    print '<table class="noborder" width="100%">';
			
			$object= new Assortment($db);
			$assorts = $object->get_assortment_for_product($objectid,'supplier');
			
			if (sizeof($assorts)>0)
			{
				print '<tr class="liste_titre">';
				print '<td>'.$langs->trans("Supplier").'</td>';
				
				if ($conf->global->ASSORTMENT_BY_CAT == 1) print '<td>'.$langs->trans("Category").'</td>';
				
				if ($user->rights->assortment->supprimer) print '<td>&nbsp;</td>';
				
				print '</tr>';
				
				foreach($assorts as $assort)
				{
					$varbg = !$varbg;
					
								
					
					print '<tr '.$bc[$varbg].'>';
					print '<td>'.$assort->getSocNomUrl().'</td>';
					if ($conf->global->ASSORTMENT_BY_CAT == 1) print '<td>'.$assort->s_pathCateg.'</td>';
					
					if ($user->rights->assortment->supprimer) 
					{
						print '<td align="right">';
						print "<a href= '".DOL_URL_ROOT."/assortment/assortment.php?action=remove&id=".$assort->id."&prodid=".$objectid."&type=".$type."'>";
						print img_delete($langs->trans("RemoveSupplierFromAssort")).' ';
						print $langs->trans("RemoveSupplierFromAssort")."</a>";
						print '</td>';
					}
					print '</tr>';
				}
			}
			else
			{
				print '<tr><td>'.$langs->trans("NoAssortmentForProduct").'</td></tr>';
			}
			print '</table>';
		} 	
    }
	
	function select_all_categories_assortment($type, $objectid=0, $selected='', $maxlength=64)
    {
        global $langs;
        $langs->load("categories");

		$select_name="catMereRem";
		if ($type==0){$select_name="catMereRemProd";}
		if ($type==1){$select_name="catMereRemSupp";}
		if ($type==2){$select_name="catMereRemCust";}
		
        

        $assortcat = new Assortment_Category($this->db);
        $cate_arbo = $assortcat->get_full_arbo_assort($type,$objectid);

        $output = '<select class="flat" name="'.$select_name.'">';
        if (is_array($cate_arbo))
        {
            if (! sizeof($cate_arbo)) $output.= '<option value="-1" disabled="true">'.$langs->trans("NoCategoriesDefined").'</option>';
            else
            {
                $output.= '<option value="-1">&nbsp;</option>';
                foreach($cate_arbo as $key => $value)
                {
                    if ($cate_arbo[$key]['id'] == $selected)
                    {
                        $add = 'selected="selected" ';
                    }
                    else
                    {
                        $add = '';
                    }
                    $output.= '<option '.$add.'value="'.$cate_arbo[$key]['id'].'">'.dol_trunc($cate_arbo[$key]['fulllabel'],$maxlength,'middle').'</option>';
                }
            }
        }
        $output.= '</select>';
        $output.= "\n";
        return $output;
    }
    
       /**
     *    	Output html form to select a third party link to an assortment
     *		@param      selected        Preselected type
     *		@param      htmlname        Name of field in form
     *    	@param      filter          Optionnal filters criteras
     *		@param		showempty		Add an empty field
     * 		@param		showtype		Show third party type in combolist (customer, prospect or supplier)
     * 		@param		forcecombo		Force to use combo box
     */
    function select_societes_assort($selected='',$htmlname='socid',$filter='',$showempty=0, $showtype=0, $forcecombo=0,$prodid=0)
    {
    	print $this->select_company_assort($selected,$htmlname,$filter,$showempty,$showtype,$forcecombo,$prodid);
    }
    
   /**
     *    	Output html form to select a third party link to an assortment
     *		@param      selected        Preselected type
     *		@param      htmlname        Name of field in form
     *    	@param      filter          Optionnal filters criteras
     *		@param		showempty		Add an empty field
     * 		@param		showtype		Show third party type in combolist (customer, prospect or supplier)
     * 		@param		forcecombo		Force to use combo box
     */
    function select_company_assort($selected='',$htmlname='socid',$filter='',$showempty=0, $showtype=0, $forcecombo=0,$prodid=0)
    {
        global $conf,$user,$langs;

        $out='';

        // On recherche les societes
        $sql = "SELECT s.rowid, s.nom, s.client, s.fournisseur, s.code_client, s.code_fournisseur";
        $sql.= " FROM ".MAIN_DB_PREFIX ."societe as s";
        if ($prodid!=0)
        {
        	$sql.= " INNER JOIN ".MAIN_DB_PREFIX ."assortment as assort ON assort.fk_prod='".$prodid."' AND assort.fk_soc=s.rowid";
        }
        if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        $sql.= " WHERE s.entity = ".$conf->entity;
        if ($filter) $sql.= " AND ".$filter;
        if (is_numeric($selected) && $conf->use_javascript_ajax && $conf->global->COMPANY_USE_SEARCH_TO_SELECT)	$sql.= " AND s.rowid = ".$selected;
        if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
        $sql.= " ORDER BY nom ASC";

        dol_syslog("FormAssort::select_societes_assort sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($conf->use_javascript_ajax && $conf->global->COMPANY_USE_SEARCH_TO_SELECT && ! $forcecombo)
            {
                $minLength = (is_numeric($conf->global->COMPANY_USE_SEARCH_TO_SELECT)?$conf->global->COMPANY_USE_SEARCH_TO_SELECT:2);

            	$socid = 0;
                if ($selected)
                {
                    $obj = $this->db->fetch_object($resql);
                    $socid = $obj->rowid?$obj->rowid:'';
                }

                $out.= "\n".'<!-- Input text for third party with Ajax.Autocompleter (select_societes) -->'."\n";
                $out.= '<table class="nobordernopadding"><tr class="nocellnopadd">';
                $out.= '<td class="nobordernopadding">';
                if ($socid == 0)
                {
                	$out.= '<input type="text" size="30" id="search_'.$htmlname.'" name="search_'.$htmlname.'" value="" />';
                }
                else
                {
                    $out.= '<input type="text" size="30" id="search_'.$htmlname.'" name="search_'.$htmlname.'" value="'.$obj->nom.'" />';
                }
                $out.= ajax_autocompleter(($socid?$socid:-1),$htmlname,DOL_URL_ROOT.'/societe/ajaxcompanies.php?filter='.urlencode($filter), '', $minLength);
                $out.= '</td>';
                $out.= '</tr>';
                $out.= '</table>';
            }
            else
            {
                $out.= '<select id="select'.$htmlname.'" class="flat" name="'.$htmlname.'">';
                if ($showempty) $out.= '<option value="-1">&nbsp;</option>';
                $num = $this->db->num_rows($resql);
                $i = 0;
                if ($num)
                {
                    while ($i < $num)
                    {
                        $obj = $this->db->fetch_object($resql);
                        $label=$obj->nom;
                        if ($showtype)
                        {
                            if ($obj->client || $obj->fournisseur) $label.=' (';
                            if ($obj->client == 1 || $obj->client == 3) $label.=$langs->trans("Customer");
                            if ($obj->client == 2 || $obj->client == 3) $label.=($obj->client==3?', ':'').$langs->trans("Prospect");
                            if ($obj->fournisseur) $label.=($obj->client?', ':'').$langs->trans("Supplier");
                            if ($obj->client || $obj->fournisseur) $label.=')';
                        }
                        if ($selected > 0 && $selected == $obj->rowid)
                        {
                            $out.= '<option value="'.$obj->rowid.'" selected="selected">'.$label.'</option>';
                        }
                        else
                        {
                            $out.= '<option value="'.$obj->rowid.'">'.$label.'</option>';
                        }
                        $i++;
                    }
                }
                $out.= '</select>';
            }
        }
        else
        {
            dol_print_error($this->db);
        }

        return $out;
    }

}
?>
