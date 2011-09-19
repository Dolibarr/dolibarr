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
 *  \file       htdocs/assortment/assortment.php
 *  \ingroup    crm
 *  \brief      Set Assortment by product or customer pages
 *  \version    $Id: assortment.php,v 1.0 2011/01/01 eldy Exp $
 */


error_reporting(E_ALL);
ini_set('display_errors', true);
ini_set('html_errors', false);

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT_ALT."/assortment/class/assortment.class.php");
require_once(DOL_DOCUMENT_ROOT_ALT."/assortment/lib/assortment.lib.php");



// Load traductions files requiredby by page
$langs->load("assortment@assortment");

if ($conf->global->ASSORTMENT_BY_CAT == 1)
{
	$langs->load("categorie");
}

//All page variable
$mesgErr='';
$mesg='';

// Get parameters
$b_isAssortProd = false;
$b_isAssortThirdParty=false;

if ((isset($_GET["type"]) && (isset($_GET["prodid"]) || isset($_GET["ref"]) || isset($_GET["socid"]))) || 
	(isset($_POST["type"]) && (isset($_POST["prodid"]) || isset($_POST["ref"]) || isset($_POST["socid"]))))
{
	(! isset($_GET["type"])?$type = $_POST["type"]:$type = $_GET["type"]);
	
	switch ($type)
	{
		case 0:
	    	$b_isAssortProd = true;
    	break;
		case 1:
    		$b_isAssortThirdParty = true;
    	break;
	}	
}



// Security check
if ($user->right->assortment->lire) $socid=$user->societe_id;
$result = restrictedArea($user,'assortment',$user->societe_id);



/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/
if ($_POST["action"] == $langs->trans("Add"))
{
	if ($user->rights->assortment->creer)
	{	
		if ($conf->global->ASSORTMENT_BY_CAT == 1 && $_POST["typeaction"]=='cat')
		{
			require_once(DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php");
			
			$b_err=false;	
			
			$ObjectByCateg=new Assortment($db);
			
			if ($b_isAssortThirdParty)
			{
				require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
				
				//Retreive all product link to category
				$prods=$ObjectByCateg->get_all_prod_by_categ($_POST["catMere"]);
				
				foreach($prods as &$val)
				{
					$assort=new Assortment($db);
					$assort->label=$_POST["socid"].'_Cat_'.$_POST["catMere"];
					$assort->fk_soc=$_POST["socid"];
					$assort->fk_prod=$val;
					$result=$assort->create($user);
	
					if ($result <= 0)
					{
						// Creation KO
						$b_err=true;
						//Retreive product name
						$prod = new Product($db);
						$prod->fetch($val);
						$mesgErr.=$langs->trans("ProdWasNotAddedSuccessfully",$prod->ref.' '.$prod->libelle).'</BR>';
					}
				}		
				if (!$b_err)
				{
					//Retreive category name
					$cat = new Categorie($db);
					$cat->fetch($_POST["catMere"]);
				
					$mesg='<div class="ok">'.$langs->trans("ProdWasAddedSuccessfullyForCat",$cat->label).'</div>';
				}
			}
			if ($b_isAssortProd)
			{
				require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
				
				//Retreive all product link to category
				$customers=$ObjectByCateg->get_all_customer_by_categ($_POST["catMere"]);
				
				foreach($customers as &$val)
				{
					$assort=new Assortment($db);
					$assort->label=$val.'_Cat_'.$_POST["catMere"];
					$assort->fk_soc=$val;
					$assort->fk_prod=$_POST['prodid'];
					$result=$assort->create($user);
	
					if ($result <= 0)
					{
						// Creation KO
						$b_err=true;
						//Retreive thirdparty name
						$Customer = new Societe($db);
						$Customer->fetch($val);
						$mesgErr.=$langs->trans("CustomerWasNotAddedSuccessfully",$Customer->nom).'</BR>';
					}
				}		
				if (!$b_err)
				{
					//Retreive category name
					$cat = new Categorie($db);
					$cat->fetch($_POST["catMere"]);
				
					$mesg='<div class="ok">'.$langs->trans("CustWasAddedSuccessfullyForCat",$cat->label).'</div>';
				}
				
			}
		}
		else if ($_POST["typeaction"]=='nocat')
		{
			if ($b_isAssortThirdParty)
			{
				require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
				
				$assort=new Assortment($db);
				$assort->label=$_POST["socid"].'_NoCat_'.$_POST["productid"];
				$assort->fk_soc=$_POST["socid"];
				$assort->fk_prod=$_POST["idprod"];
				$result=$assort->create($user);
				
				//Retreive product name
				$prod = new Product($db);
				$prod->fetch($_POST["idprod"]);
				
				if ($result > 0)
				{
					//creation OK
					$mesg='<div class="ok">'.$langs->trans("ProdWasAddedSuccessfully",$prod->ref.' '.$prod->libelle).'</div>';
				}
				else
				{
					// Creation KO
					$mesgErr.=$langs->trans("ProdWasNotAddedSuccessfully",$prod->ref.' '.$prod->libelle);
				}
			}
			if ($b_isAssortProd)
			{
				require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
				
				$assort=new Assortment($db);
				$assort->label=$_POST["socid"].'_NoCat_'.$_POST["prodid"];
				$assort->fk_soc=$_POST["socid"];
				$assort->fk_prod=$_POST["prodid"];
				$result=$assort->create($user);
				
				//Retreive thirdparty name
				$Customer = new Societe($db);
				$Customer->fetch($_POST["socid"]);
				
				if ($result > 0)
				{
					//creation OK
					$mesg='<div class="ok">'.$langs->trans("ProdWasAddedSuccessfullyToCust",$Customer->nom).'</div>';
				}
				else
				{
					// Creation KO
					$mesgErr.=$langs->trans("CustomerWasNotAddedSuccessfully",$Customer->nom);
				}
			}
		}
	}
	else
	{
		$mesgErr=$lang->trans("UserNotAllowAdd");
	}	
}
if ($_GET["action"] == "remove")
{
	if ($user->rights->assortment->supprimer) 
	{
		// Remove all link by category
		if (isset($_POST["typeaction"]) && ($conf->global->ASSORTMENT_BY_CAT == 1))
		{ 
			if (($_POST["typeaction"]=="RemoveCatProd") && (isset($_POST["catMereRemProd"])) && (isset($_POST["type"])) && (isset($_POST["socid"])))
			{
				$assort = new Assortment($db);
				$catToRemove=$_POST["catMereRemProd"];
				
				$assort->fk_soc=$_POST["socid"];
				
				//Remove all item of the category
				$result=$assort->remove_category($user,$catToRemove,$_POST["typeaction"]);
				
				//Retreive category name
				require_once(DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php");
				$cat = new Categorie($db);
				$cat->fetch($catToRemove);
				
				if ($result==1)
				{
					$mesg='<div class="ok">'.$langs->trans("CatWasRemove",$cat->label).'</div>';
				}
				else
				{
					$mesgErr.=$langs->trans("CatWasNotRemove",$cat->label);
				}
			}
			if (($_POST["typeaction"]=="RemoveCatCustomer") && (isset($_POST["catMereRemCust"])) && (isset($_POST["type"])) && (isset($_POST["prodid"])))
			{
				$assort = new Assortment($db);
				$catToRemove=$_POST["catMereRemCust"];
				
				$assort->fk_prod=$_POST["prodid"];
				
				//Remove all item of the category
				$result=$assort->remove_category($user,$catToRemove,$_POST["typeaction"]);
				
				//Retreive category name
				require_once(DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php");
				$cat = new Categorie($db);
				$cat->fetch($catToRemove);
				
				if ($result==1)
				{
					$mesg='<div class="ok">'.$langs->trans("CatWasRemove",$cat->label).'</div>';
				}
				else
				{
					$mesgErr.=$langs->trans("CatWasNotRemove",$cat->label);
				}
			}
			if (($_POST["typeaction"]=="RemoveCatSupplier") && (isset($_POST["catMereRemSupp"])) && (isset($_POST["type"])) && (isset($_POST["prodid"])))
			{
				$assort = new Assortment($db);
				$catToRemove=$_POST["catMereRemSupp"];
				
				$assort->fk_prod=$_POST["prodid"];
				
				//Remove all item of the category
				$result=$assort->remove_category($user,$catToRemove,$_POST["typeaction"]);
				
				//Retreive category name
				require_once(DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php");
				$cat = new Categorie($db);
				$cat->fetch($catToRemove);
				
				if ($result==1)
				{
					$mesg='<div class="ok">'.$langs->trans("CatWasRemove",$cat->label).'</div>';
				}
				else
				{
					$mesgErr.=$langs->trans("CatWasNotRemove",$cat->label);
				}
			}
		}
		else // Remove only one assortment link
		{
			if (isset($_GET["id"]))
			{
				$assort = new Assortment($db);
				$result=$assort->fetch($_GET["id"]);
				// if id still exits
				if ($result!=0)
				{
					$prodassortDelId=$assort->fk_prod;
					$custassortDelId=$assort->fk_soc;
					
					$result=$assort->delete($user);
					if ($result==1)
					{
						if ($b_isAssortThirdParty)
						{
							require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
							
							//Retreive product name
							$prod = new Product($db);
							$prod->fetch($prodassortDelId);
				
							$mesg='<div class="ok">'.$langs->trans("ProdWasRemove",$prod->ref.' '.$prod->libelle).'</div>';
						}
						if ($b_isAssortProd)
						{
							require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
							
							//Retreive product name
							$cust = new Societe($db);
							$cust->fetch($custassortDelId);
				
							$mesg='<div class="ok">'.$langs->trans("ProdWasRemoveCust",$cust->nom).'</div>';
						}
					}
					else
					{
						$mesgErr.=$langs->trans("ProdWasNotRemove",$prod->ref.' '.$prod->libelle);
					}
				}
			}
		}
	}
	else
	{
		$mesgErr=$lang->trans("UserNotAllowRemove");
	}
	
}

/***************************************************
* PAGE
*
* Put here all code to build page
****************************************************/

$html = new Form($db);

if ($b_isAssortThirdParty)
{
	$langs->load("companies");
    require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
	require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
	
	$soc = new Societe($db);
	$result = $soc->fetch($_GET["socid"]);
	
	llxHeader("","",$langs->trans("Assortment"));

	/*
	 * Affichage onglets
	 */
	$head = societe_prepare_head($soc);

	dol_fiche_head($head, 'tabAssortment', $langs->trans("ThirdParty"),0,'company');

	print '<table class="border" width="100%">';

	print '<tr><td width="25%">'.$langs->trans("Name").'</td><td colspan="3">';
	print $html->showrefnav($soc,'socid','',($user->societe_id?0:1),'rowid','nom','','&type='.$type);
	print '</td></tr>';

	print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$soc->prefix_comm.'</td></tr>';

	if ($soc->client)
	{
		print '<tr><td>';
		print $langs->trans('CustomerCode').'</td><td colspan="3">';
		print $soc->code_client;
		if ($soc->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
		print '</td></tr>';
	}

	if ($soc->fournisseur)
	{
		print '<tr><td>';
		print $langs->trans('SupplierCode').'</td><td colspan="3">';
		print $soc->code_fournisseur;
		if ($soc->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
		print '</td></tr>';
	}

	if ($conf->global->MAIN_MODULE_BARCODE)
	{
		print '<tr><td>'.$langs->trans('Gencod').'</td><td colspan="3">'.$soc->gencod.'</td></tr>';
	}

	// Address
	print "<tr><td valign=\"top\">".$langs->trans('Address')."</td><td colspan=\"3\">".nl2br($soc->address)."</td></tr>";

	// Zip / Town
	print '<tr><td width="25%">'.$langs->trans('Zip').'</td><td width="25%">'.$soc->cp."</td>";
	print '<td width="25%">'.$langs->trans('Town').'</td><td width="25%">'.$soc->ville."</td></tr>";

	// Country
	if ($soc->pays) {
		print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">';
		$img=picto_from_langcode($soc->pays_code);
		print ($img?$img.' ':'');
		print $soc->pays;
		print '</td></tr>';
	}

	// Phone
	print '<tr><td>'.$langs->trans('Phone').'</td><td>'.dol_print_phone($soc->tel,$soc->pays_code,0,$soc->id,'AC_TEL').'</td>';
	print '<td>'.$langs->trans('Fax').'</td><td>'.dol_print_phone($soc->fax,$soc->pays_code,0,$soc->id,'AC_FAX').'</td></tr>';

	// EMail
	print '<tr><td>'.$langs->trans('EMail').'</td><td>';
	print dol_print_email($soc->email,0,$soc->id,'AC_EMAIL');
	print '</td>';

	// Web
	print '<td>'.$langs->trans('Web').'</td><td>';
	print dol_print_url($soc->url);
	print '</td></tr>';

	// Assujeti a TVA ou pas
	print '<tr>';
	print '<td nowrap="nowrap">'.$langs->trans('VATIsUsed').'</td><td colspan="3">';
	print yn($soc->tva_assuj);
	print '</td>';
	print '</tr>';

	print '</table>';

	print '</div>';

	if ($mesg) print($mesg);

	ManageAssortment($db,$soc,1);
	
	if ($mesgErr!='')
	{
		print '<div class="error">';
		print $mesgErr;
		print '</div>';
	}
		
	DisplayAssortment($db,$soc->id,1);
	
	
}
if ($b_isAssortProd)
{
	$langs->load("product");

	/*
	 * card Assortment of Product
	 */

	require_once(DOL_DOCUMENT_ROOT."/lib/product.lib.php");
	require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");

	//Product
	$product = new Product($db);
	
	if ($_GET["prodid"] || $_GET["ref"]) 
	{
		$result = $product->fetch($_GET["prodid"],$_GET["ref"]);
	}
	else
	{
		$result = $product->fetch($_POST["prodid"],$_POST["ref"]);
	}

	llxHeader("","",$langs->trans("CardProduct".$product->type));

	$head=product_prepare_head($product, $user);
	$titre=$langs->trans("CardProduct".$product->type);
	$picto=($product->type==1?'service':'product');
	dol_fiche_head($head, 'tabAssortment', $titre,0,$picto);


	print '<table class="border" width="100%">';

	// Ref
	print "<tr>";
	print '<td width="15%">'.$langs->trans("Ref").'</td><td>';
	print $html->showrefnav($product,'ref','',1,'ref','ref','','&type='.$type);
	print '</td>';
	print '</tr>';

	// Label
	print '<tr><td>'.$langs->trans("Label").'</td><td>'.$product->libelle.'</td>';
	print '</tr>';

	// Status (to sell)
	print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Sell").')'.'</td><td>';
	print $product->getLibStatut(2,0);
	print '</td></tr>';

	// Status (to buy)
	print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Buy").')'.'</td><td>';
	print $product->getLibStatut(2,1);
	print '</td></tr>';

	print '</table>';

	print '</div>';

	if ($mesg) print($mesg);

	ManageAssortment($db,$product,0);
	ManageAssortment($db,$product,2);

	if ($mesgErr!='')
	{
		print '<div class="error">';
		print $mesgErr;
		print '</div>';
	}
	
	DisplayAssortment($db,$product->id,0);

}

// End of page
$db->close();
llxFooter('$Date: 2011/01/16 13:24:29 $ - $Revision: 1.16 $');
?>
