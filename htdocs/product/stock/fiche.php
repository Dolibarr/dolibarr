<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Simon Tosser         <simon@kornog-computing.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 *	\file       htdocs/product/stock/fiche.php
 *	\ingroup    stock
 *	\brief      Page fiche entrepot
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/stock.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

$langs->load("products");
$langs->load("stocks");
$langs->load("companies");

$action=GETPOST('action');

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
if (! $sortfield) $sortfield="p.ref";
if (! $sortorder) $sortorder="DESC";

$mesg = '';

// Security check
$result=restrictedArea($user,'stock');



/*
 * Actions
 */

// Ajout entrepot
if ($action == 'add' && $user->rights->stock->creer)
{
	$object = new Entrepot($db);

	$object->ref         = $_POST["ref"];
	$object->libelle     = $_POST["libelle"];
	$object->description = $_POST["desc"];
	$object->statut      = $_POST["statut"];
	$object->lieu        = $_POST["lieu"];
	$object->address     = $_POST["address"];
	$object->zip         = $_POST["zipcode"];
	$object->town        = $_POST["town"];
	$object->country_id  = $_POST["country_id"];

	if ($object->libelle) {
		$id = $object->create($user);
		if ($id > 0)
		{
			header("Location: fiche.php?id=".$id);
			exit;
		}

		$action = 'create';
		$mesg='<div class="error">'.$object->error.'</div>';
	}
	else {
		$mesg='<div class="error">'.$langs->trans("ErrorWarehouseRefRequired").'</div>';
		$action="create";   // Force retour sur page creation
	}
}

// Delete warehouse
if ($action == 'confirm_delete' && $_REQUEST["confirm"] == 'yes' && $user->rights->stock->supprimer)
{
	$object = new Entrepot($db);
	$object->fetch($_REQUEST["id"]);
	$result=$object->delete($user);
	if ($result > 0)
	{
		header("Location: ".DOL_URL_ROOT.'/product/stock/liste.php');
		exit;
	}
	else
	{
		$mesg='<div class="error">'.$object->error.'</div>';
		$action='';
	}
}

// Modification entrepot
if ($action == 'update' && $_POST["cancel"] <> $langs->trans("Cancel"))
{
	$object = new Entrepot($db);
	if ($object->fetch($_POST["id"]))
	{
		$object->libelle     = $_POST["libelle"];
		$object->description = $_POST["desc"];
		$object->statut      = $_POST["statut"];
		$object->lieu        = $_POST["lieu"];
		$object->address     = $_POST["address"];
		$object->zip         = $_POST["zipcode"];
		$object->town        = $_POST["town"];
		$object->country_id  = $_POST["country_id"];

		if ( $object->update($_POST["id"], $user) > 0)
		{
			$action = '';
			$_GET["id"] = $_POST["id"];
			//$mesg = '<div class="ok">Fiche mise a jour</div>';
		}
		else
		{
			$action = 'edit';
			$_GET["id"] = $_POST["id"];
			$mesg = '<div class="error">'.$object->error.'</div>';
		}
	}
	else
	{
		$action = 'edit';
		$_GET["id"] = $_POST["id"];
		$mesg = '<div class="error">'.$object->error.'</div>';
	}
}

if ($_POST["cancel"] == $langs->trans("Cancel"))
{
	$action = '';
	$_GET["id"] = $_POST["id"];
}



/*
 * View
 */

$productstatic=new Product($db);
$form=new Form($db);
$formcompany=new FormCompany($db);

$help_url='EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';
llxHeader("",$langs->trans("WarehouseCard"),$help_url);


if ($action == 'create')
{
	print_fiche_titre($langs->trans("NewWarehouse"));

	print "<form action=\"fiche.php\" method=\"post\">\n";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="type" value="'.$type.'">'."\n";

	dol_htmloutput_mesg($mesg);

	print '<table class="border" width="100%">';

	// Ref
	print '<tr><td width="25%" class="fieldrequired">'.$langs->trans("Ref").'</td><td colspan="3"><input name="libelle" size="20" value=""></td></tr>';

	print '<tr><td >'.$langs->trans("LocationSummary").'</td><td colspan="3"><input name="lieu" size="40" value="'.$object->lieu.'"></td></tr>';

	// Description
	print '<tr><td valign="top">'.$langs->trans("Description").'</td><td colspan="3">';
	// Editeur wysiwyg
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor=new DolEditor('desc',$object->description,'',180,'dolibarr_notes','In',false,true,$conf->fckeditor->enabled,5,70);
	$doleditor->Create();
	print '</td></tr>';

	print '<tr><td>'.$langs->trans('Address').'</td><td colspan="3"><textarea name="address" cols="60" rows="3" wrap="soft">';
	print $object->address;
	print '</textarea></td></tr>';

	// Zip / Town
	print '<tr><td>'.$langs->trans('Zip').'</td><td>';
	print $formcompany->select_ziptown($object->zip,'zipcode',array('town','selectcountry_id','departement_id'),6);
	print '</td><td>'.$langs->trans('Town').'</td><td>';
	print $formcompany->select_ziptown($object->town,'town',array('zipcode','selectcountry_id','departement_id'));
	print '</td></tr>';

	// Country
	print '<tr><td width="25%">'.$langs->trans('Country').'</td><td colspan="3">';
	print $form->select_country($object->country_id?$object->country_id:$mysoc->country_code,'country_id');
	if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("Status").'</td><td colspan="3">';
	print '<select name="statut" class="flat">';
	print '<option value="0">'.$langs->trans("WarehouseClosed").'</option>';
	print '<option value="1" selected="selected">'.$langs->trans("WarehouseOpened").'</option>';
	print '</select>';
	print '</td></tr>';

	print '</table>';

	print '<center><br><input type="submit" class="button" value="'.$langs->trans("Create").'"></center>';

	print '</form>';
}
else
{
	if ($_GET["id"])
	{
		dol_htmloutput_mesg($mesg);

		$object = new Entrepot($db);
		$result = $object->fetch($_GET["id"]);
		if ($result < 0)
		{
			dol_print_error($db);
		}

		/*
		 * Affichage fiche
		 */
		if ($action <> 'edit' && $action <> 're-edit')
		{
			$head = stock_prepare_head($object);

			dol_fiche_head($head, 'card', $langs->trans("Warehouse"), 0, 'stock');

			// Confirm delete third party
			if ($action == 'delete')
			{
				$form = new Form($db);
				$ret=$form->form_confirm($_SERVER["PHP_SELF"]."?id=".$object->id,$langs->trans("DeleteAWarehouse"),$langs->trans("ConfirmDeleteWarehouse",$object->libelle),"confirm_delete",'',0,2);
				if ($ret == 'html') print '<br>';
			}

			print '<table class="border" width="100%">';

			$linkback = '<a href="'.DOL_URL_ROOT.'/product/stock/liste.php">'.$langs->trans("BackToList").'</a>';

			// Ref
			print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td colspan="3">';
			print $form->showrefnav($object, 'id', $linkback, 1, 'rowid', 'libelle');
			print '</td>';

			print '<tr><td>'.$langs->trans("LocationSummary").'</td><td colspan="3">'.$object->lieu.'</td></tr>';

			// Description
			print '<tr><td valign="top">'.$langs->trans("Description").'</td><td colspan="3">'.nl2br($object->description).'</td></tr>';

			// Address
			print '<tr><td>'.$langs->trans('Address').'</td><td colspan="3">';
			print $object->address;
			print '</td></tr>';

			// Town
			print '<tr><td width="25%">'.$langs->trans('Zip').'</td><td width="25%">'.$object->zip.'</td>';
			print '<td width="25%">'.$langs->trans('Town').'</td><td width="25%">'.$object->town.'</td></tr>';

			// Country
			print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">';
			$img=picto_from_langcode($object->country_code);
			print ($img?$img.' ':'');
			print $object->country;
			print '</td></tr>';

			// Status
			print '<tr><td>'.$langs->trans("Status").'</td><td colspan="3">'.$object->getLibStatut(4).'</td></tr>';

			$calcproducts=$object->nb_products();

			// Nb of products
			print '<tr><td valign="top">'.$langs->trans("NumberOfProducts").'</td><td colspan="3">';
			print empty($calcproducts['nb'])?'0':$calcproducts['nb'];
			print "</td></tr>";

			// Value
			print '<tr><td valign="top">'.$langs->trans("EstimatedStockValueShort").'</td><td colspan="3">';
			print empty($calcproducts['value'])?'0':$calcproducts['value'];
			print "</td></tr>";

			// Last movement
			$sql = "SELECT max(m.datem) as datem";
			$sql .= " FROM ".MAIN_DB_PREFIX."stock_mouvement as m";
			$sql .= " WHERE m.fk_entrepot = '".$object->id."'";
			$resqlbis = $db->query($sql);
			if ($resqlbis)
			{
				$obj = $db->fetch_object($resqlbis);
				$lastmovementdate=$db->jdate($obj->datem);
			}
			else
			{
				dol_print_error($db);
			}
			print '<tr><td valign="top">'.$langs->trans("LastMovement").'</td><td colspan="3">';
			if ($lastmovementdate)
			{
			    print dol_print_date($lastmovementdate,'dayhour').' ';
			    print '(<a href="'.DOL_URL_ROOT.'/product/stock/mouvement.php?id='.$object->id.'">'.$langs->trans("FullList").'</a>)';
			}
			else
			{
			     print $langs->trans("None");
			}
			print "</td></tr>";

			print "</table>";

			print '</div>';


			/* ************************************************************************** */
			/*                                                                            */
			/* Barre d'action                                                             */
			/*                                                                            */
			/* ************************************************************************** */

			print "<div class=\"tabsAction\">\n";

			if ($action == '')
			{
				if ($user->rights->stock->creer)
				print "<a class=\"butAction\" href=\"fiche.php?action=edit&id=".$object->id."\">".$langs->trans("Modify")."</a>";
				else
				print "<a class=\"butActionRefused\" href=\"#\">".$langs->trans("Modify")."</a>";

				if ($user->rights->stock->supprimer)
				print "<a class=\"butActionDelete\" href=\"fiche.php?action=delete&id=".$object->id."\">".$langs->trans("Delete")."</a>";
				else
				print "<a class=\"butActionRefused\" href=\"#\">".$langs->trans("Delete")."</a>";
			}

			print "</div>";


			/* ************************************************************************** */
			/*                                                                            */
			/* Affichage de la liste des produits de l'entrepot                           */
			/*                                                                            */
			/* ************************************************************************** */
			print '<br>';

			print '<table class="noborder" width="100%">';
			print "<tr class=\"liste_titre\">";
			print_liste_field_titre($langs->trans("Product"),"", "p.ref","&amp;id=".$_GET['id'],"","",$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("Label"),"", "p.label","&amp;id=".$_GET['id'],"","",$sortfield,$sortorder);
            print_liste_field_titre($langs->trans("Units"),"", "ps.reel","&amp;id=".$_GET['id'],"",'align="right"',$sortfield,$sortorder);
            print_liste_field_titre($langs->trans("AverageUnitPricePMPShort"),"", "ps.pmp","&amp;id=".$_GET['id'],"",'align="right"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("EstimatedStockValueShort"),"", "","&amp;id=".$_GET['id'],"",'align="right"',$sortfield,$sortorder);
            if (empty($conf->global->PRODUIT_MULTIPRICES)) print_liste_field_titre($langs->trans("SellPriceMin"),"", "p.price","&amp;id=".$_GET['id'],"",'align="right"',$sortfield,$sortorder);
            if (empty($conf->global->PRODUIT_MULTIPRICES)) print_liste_field_titre($langs->trans("EstimatedStockValueSellShort"),"", "","&amp;id=".$_GET['id'],"",'align="right"',$sortfield,$sortorder);
			if ($user->rights->stock->mouvement->creer) print '<td>&nbsp;</td>';
			if ($user->rights->stock->creer)            print '<td>&nbsp;</td>';
			print "</tr>";

			$totalunit=0;
			$totalvalue=$totalvaluesell=0;

			$sql = "SELECT p.rowid as rowid, p.ref, p.label as produit, p.fk_product_type as type, p.pmp as ppmp, p.price, p.price_ttc,";
			$sql.= " ps.pmp, ps.reel as value";
			$sql.= " FROM ".MAIN_DB_PREFIX."product_stock ps, ".MAIN_DB_PREFIX."product p";
			$sql.= " WHERE ps.fk_product = p.rowid";
			$sql.= " AND ps.reel <> 0";	// We do not show if stock is 0 (no product in this warehouse)
			$sql.= " AND ps.fk_entrepot = ".$object->id;
			$sql.= $db->order($sortfield,$sortorder);

			dol_syslog('List products sql='.$sql);
			$resql = $db->query($sql);
			if ($resql)
			{
				$num = $db->num_rows($resql);
				$i = 0;
				$var=True;
				while ($i < $num)
				{
					$objp = $db->fetch_object($resql);

					// Multilangs
					if (! empty($conf->global->MAIN_MULTILANGS)) // si l'option est active
					{
						$sql = "SELECT label";
						$sql.= " FROM ".MAIN_DB_PREFIX."product_lang";
						$sql.= " WHERE fk_product=".$objp->rowid;
						$sql.= " AND lang='". $langs->getDefaultLang() ."'";
						$sql.= " LIMIT 1";

						$result = $db->query($sql);
						if ($result)
						{
							$objtp = $db->fetch_object($result);
							if ($objtp->label != '') $objp->produit = $objtp->label;
						}
					}

					$var=!$var;
					//print '<td>'.dol_print_date($objp->datem).'</td>';
					print "<tr ".$bc[$var].">";
					print "<td>";
					$productstatic->id=$objp->rowid;
					$productstatic->ref=$objp->ref;
					$productstatic->type=$objp->type;
					print $productstatic->getNomUrl(1,'stock',16);
					print '</td>';
					print '<td>'.$objp->produit.'</td>';

					print '<td align="right">'.$objp->value.'</td>';
					$totalunit+=$objp->value;

                    // Price buy PMP
					print '<td align="right">'.price(price2num($objp->pmp,'MU')).'</td>';
                    // Total PMP
					print '<td align="right">'.price(price2num($objp->pmp*$objp->value,'MT')).'</td>';
					$totalvalue+=price2num($objp->pmp*$objp->value,'MT');

                    // Price sell min
                    if (empty($conf->global->PRODUIT_MULTIPRICES))
                    {
                        $pricemin=$objp->price;
                        print '<td align="right">';
                        print price(price2num($pricemin,'MU'));
                        print '</td>';
                        // Total sell min
                        print '<td align="right">';
                        print price(price2num($pricemin*$objp->value,'MT'));
                        print '</td>';
                    }
                    $totalvaluesell+=price2num($pricemin*$objp->value,'MT');

                    if ($user->rights->stock->mouvement->creer)
					{
						print '<td align="center"><a href="'.DOL_URL_ROOT.'/product/stock/product.php?dwid='.$object->id.'&amp;id='.$objp->rowid.'&amp;action=transfert">';
						print img_picto($langs->trans("StockMovement"),'uparrow.png').' '.$langs->trans("StockMovement");
						print "</a></td>";
					}

					if ($user->rights->stock->creer)
					{
						print '<td align="center"><a href="'.DOL_URL_ROOT.'/product/stock/product.php?dwid='.$object->id.'&amp;id='.$objp->rowid.'&amp;action=correction">';
						print $langs->trans("StockCorrection");
						print "</a></td>";
					}

					print "</tr>";
					$i++;
				}
				$db->free($resql);

				print '<tr class="liste_total"><td class="liste_total" colspan="2">'.$langs->trans("Total").'</td>';
				print '<td class="liste_total" align="right">'.$totalunit.'</td>';
				print '<td class="liste_total">&nbsp;</td>';
                print '<td class="liste_total" align="right">'.price(price2num($totalvalue,'MT')).'</td>';
                if (empty($conf->global->PRODUIT_MULTIPRICES))
                {
                    print '<td class="liste_total">&nbsp;</td>';
                    print '<td class="liste_total" align="right">'.price(price2num($totalvaluesell,'MT')).'</td>';
                }
                print '<td class="liste_total">&nbsp;</td>';
				print '<td class="liste_total">&nbsp;</td>';
				print '</tr>';

			}
			else
			{
				dol_print_error($db);
			}
			print "</table>\n";
		}


		/*
		 * Edition fiche
		 */
		if (($action == 'edit' || $action == 're-edit') && 1)
		{
			print_fiche_titre($langs->trans("WarehouseEdit"), $mesg);

			print '<form action="fiche.php" method="POST">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="update">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';

			print '<table class="border" width="100%">';

			// Ref
			print '<tr><td width="20%" class="fieldrequired">'.$langs->trans("Ref").'</td><td colspan="3"><input name="libelle" size="20" value="'.$object->libelle.'"></td></tr>';

			print '<tr><td width="20%">'.$langs->trans("LocationSummary").'</td><td colspan="3"><input name="lieu" size="40" value="'.$object->lieu.'"></td></tr>';

			// Description
			print '<tr><td valign="top">'.$langs->trans("Description").'</td><td colspan="3">';
			// Editeur wysiwyg
			require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
			$doleditor=new DolEditor('desc',$object->description,'',180,'dolibarr_notes','In',false,true,$conf->fckeditor->enabled,5,70);
			$doleditor->Create();
			print '</td></tr>';

			print '<tr><td>'.$langs->trans('Address').'</td><td colspan="3"><textarea name="address" cols="60" rows="3" wrap="soft">';
			print $object->address;
			print '</textarea></td></tr>';

			// Zip / Town
			print '<tr><td>'.$langs->trans('Zip').'</td><td>';
			print $formcompany->select_ziptown($object->zip,'zipcode',array('town','selectcountry_id','departement_id'),6);
			print '</td><td>'.$langs->trans('Town').'</td><td>';
			print $formcompany->select_ziptown($object->town,'town',array('zipcode','selectcountry_id','departement_id'));
			print '</td></tr>';

			// Country
			print '<tr><td width="25%">'.$langs->trans('Country').'</td><td colspan="3">';
			print $form->select_country($object->country_id?$object->country_id:$mysoc->country_code,'country_id');
			if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
			print '</td></tr>';

			print '<tr><td width="20%">'.$langs->trans("Status").'</td><td colspan="3">';
			print '<select name="statut" class="flat">';
			print '<option value="0" '.($object->statut == 0?'selected="selected"':'').'>'.$langs->trans("WarehouseClosed").'</option>';
			print '<option value="1" '.($object->statut == 0?'':'selected="selected"').'>'.$langs->trans("WarehouseOpened").'</option>';
			print '</select>';
			print '</td></tr>';

			print '</table>';

			print '<center><br><input type="submit" class="button" value="'.$langs->trans("Save").'">&nbsp;';
			print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></center>';

			print '</form>';

		}
	}
}


llxFooter();

$db->close();
?>
