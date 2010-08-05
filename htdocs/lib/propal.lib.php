<?php
/* Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/lib/propal.lib.php
 *	\brief      Ensemble de fonctions de base pour le module propal
 *	\ingroup    propal
 *	\version    $Id$
 *
 * 	Ensemble de fonctions de base de dolibarr sous forme d'include
 */

function propal_prepare_head($propal)
{
	global $langs, $conf, $user;
	$langs->load("propal");
	$langs->load("compta");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/comm/propal.php?id='.$propal->id;
	$head[$h][1] = $langs->trans('CommercialCard');
	$head[$h][2] = 'comm';
	$h++;

	if ((!$conf->commande->enabled &&
	(($conf->expedition_bon->enabled && $user->rights->expedition->lire)
	|| ($conf->livraison_bon->enabled && $user->rights->expedition->livraison->lire))))
	{
		$langs->load("sendings");
		$head[$h][0] = DOL_URL_ROOT.'/expedition/propal.php?id='.$propal->id;
		if ($conf->expedition_bon->enabled) $text=$langs->trans("Sendings");
		if ($conf->livraison_bon->enabled)  $text.='/'.$langs->trans("Receivings");
		$head[$h][1] = $text;
		$head[$h][2] = 'shipping';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT.'/compta/propal.php?id='.$propal->id;
	$head[$h][1] = $langs->trans('AccountancyCard');
	$head[$h][2] = 'compta';
	$h++;

	if ($conf->use_preview_tabs)
	{
		$head[$h][0] = DOL_URL_ROOT.'/comm/propal/apercu.php?id='.$propal->id;
		$head[$h][1] = $langs->trans("Preview");
		$head[$h][2] = 'preview';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT.'/comm/propal/contact.php?id='.$propal->id;
	$head[$h][1] = $langs->trans('ProposalContact');
	$head[$h][2] = 'contact';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/comm/propal/note.php?id='.$propal->id;
	$head[$h][1] = $langs->trans('Notes');
	$head[$h][2] = 'note';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/comm/propal/document.php?id='.$propal->id;
	/*$filesdir = $conf->propale->dir_output . "/" . dol_sanitizeFileName($propal->ref);
	include_once(DOL_DOCUMENT_ROOT.'/lib/files.lib.php');
	$listoffiles=dol_dir_list($filesdir,'files',1);
	$head[$h][1] = (sizeof($listoffiles)?$langs->trans('DocumentsNb',sizeof($listoffiles)):$langs->trans('Documents'));*/
	$head[$h][1] = $langs->trans('Documents');
	$head[$h][2] = 'document';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/comm/propal/info.php?id='.$propal->id;
	$head[$h][1] = $langs->trans('Info');
	$head[$h][2] = 'info';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:MyModule:@mymodule:/mymodule/mypage.php?id=__ID__');
	if (is_array($conf->tabs_modules['propal']))
	{
		$i=0;
		foreach ($conf->tabs_modules['propal'] as $value)
		{
			$values=explode(':',$value);
			if ($values[2]) $langs->load($values[2]);
			$head[$h][0] = DOL_URL_ROOT . preg_replace('/__ID__/i',$propal->id,$values[3]);
			$head[$h][1] = $langs->trans($values[1]);
			$head[$h][2] = 'tab'.$values[1];
			$h++;
		}
	}

	return $head;
}

/**
 * 	\brief		Return HTML table with title list
 * 	\param		propal		Object propal
 * 	\param		lines		Array of propal lines
 */
function print_title_list()
{
	global $conf,$langs;
	
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans('Description').'</td>';
	if ($conf->global->PRODUIT_USE_MARKUP) print '<td align="right" width="80">'.$langs->trans('Markup').'</td>';
	print '<td align="right" width="50">'.$langs->trans('VAT').'</td>';
	print '<td align="right" width="80">'.$langs->trans('PriceUHT').'</td>';
	print '<td align="right" width="50">'.$langs->trans('Qty').'</td>';
	print '<td align="right" width="50">'.$langs->trans('ReductionShort').'</td>';
	print '<td align="right" width="50">'.$langs->trans('TotalHTShort').'</td>';
	print '<td width="48" colspan="3">&nbsp;</td>';
	print "</tr>\n";
}

/**
 * 	\brief		Return HTML with proposal lines
 * 	\param		propal		Object proposal
 * 	\param		lines		Proposal lines
 */
function print_lines_list($propal,$lines)
{
	$num = count($lines);
	$var = true;
	$i	 = 0;
	
	foreach ($lines as $line)
	{
		$var=!$var;
		
		print_line($propal,$line,$var,$num,$i);
		
		$i++;
	}
}

/**
 * 	\brief		Return HTML with selected proposal line
 * 	\param		propal		Object proposal
 * 	\param		line		Selected proposal line
 */
function print_line($propal,$line,$var=true,$num=0,$i=0)
{
	global $db;
	global $conf,$langs,$user;
	global $html,$bc;

	// Show product and description
	$type=$line->product_type?$line->product_type:$line->fk_product_type;
	// Try to enhance type detection using date_start and date_end for free lines where type
	// was not saved.
	if (! empty($line->date_start)) $type=1;
	if (! empty($line->date_end)) $type=1;

	// Ligne en mode visu
	if ($_GET['action'] != 'editline' || $_GET['lineid'] != $line->id)
	{
		print '<tr '.$bc[$var].'>';

		// Produit
		if ($line->fk_product > 0)
		{
			$product_static = new Product($db);
			
			print '<td>';
			print '<a name="'.$line->id.'"></a>'; // ancre pour retourner sur la ligne;

			// Show product and description
			$product_static->type=$line->fk_product_type;
			$product_static->id=$line->fk_product;
			$product_static->ref=$line->ref;
			$product_static->libelle=$line->product_label;
			$text=$product_static->getNomUrl(1);
			$text.= ' - '.$line->product_label;
			$description=($conf->global->PRODUIT_DESC_IN_FORM?'':dol_htmlentitiesbr($line->description));
			print $html->textwithtooltip($text,$description,3,'','',$i);

			// Show range
			print_date_range($line->date_start, $line->date_end);

			// Add description in form
			if ($conf->global->PRODUIT_DESC_IN_FORM)
			{
				print ($line->description && $line->description!=$line->product_label)?'<br>'.dol_htmlentitiesbr($line->description):'';
			}

			print '</td>';
		}
		else
		{
			print '<td>';
			print '<a name="'.$line->rowid.'"></a>'; // ancre pour retourner sur la ligne
			if (($line->info_bits & 2) == 2)
			{
				print '<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$propal->socid.'">';
				print img_object($langs->trans("ShowReduc"),'reduc').' '.$langs->trans("Discount");
				print '</a>';
				if ($line->description)
				{
					if ($line->description == '(CREDIT_NOTE)')
					{
						$discount=new DiscountAbsolute($db);
						$discount->fetch($line->fk_remise_except);
						print ' - '.$langs->transnoentities("DiscountFromCreditNote",$discount->getNomUrl(0));
					}
					else
					{
						print ' - '.nl2br($line->description);
					}
				}
			}
			else
			{
				if ($type==1) $text = img_object($langs->trans('Service'),'service');
				else $text = img_object($langs->trans('Product'),'product');
				print $text.' '.nl2br($line->description);
				// Show range
				print_date_range($line->date_start,$line->date_end);
			}
			print "</td>\n";
		}

		if ($conf->global->PRODUIT_USE_MARKUP && $conf->use_javascript_ajax)
		{
			// TODO a déplacer dans classe module marge
			$formMarkup = '<form id="formMarkup" action="'.$_SERVER["PHP_SELF"].'?id='.$propal->id.'" method="post">'."\n";
			$formMarkup.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
			$formMarkup.= '<table class="border" width="100%">'."\n";
			if ($objp->fk_product > 0)
			{
				$formMarkup.= '<tr><td align="left" colspan="2">&nbsp;</td></tr>'."\n";
				$formMarkup.= '<tr><td align="left" width="25%" height="19">&nbsp;'.$langs->trans('SupplierPrice').'</td>'."\n";
				$formMarkup.= '<td align="left">'.$html->select_product_fourn_price($line->fk_product,'productfournpriceid').'</td></tr>'."\n";
			}
			$formMarkup.= '<tr><td align="left" colspan="2">&nbsp;</td></tr>'."\n";
			$formMarkup.= '<tr><td align="left" width="25%" height="19">&nbsp;'.$langs->trans('PurchasePrice').' '.$langs->trans('HT').'</td>'."\n";
			$formMarkup.= '<td align="left"><input size="10" type="text" class="flat" name="purchaseprice_ht" value=""></td></tr>'."\n";
			$formMarkup.= '<tr><td align="left" width="25%" height="19">&nbsp;'.$langs->trans('MarkupRate').'</td>'."\n";
			$formMarkup.= '<td><input size="10" type="text" class="flat" id="markuprate'.$i.'" name="markuprate'.$i.'" value=""></td></tr>'."\n";
			$formMarkup.= '<tr><td align="left" width="25%" height="19">&nbsp;'.$langs->trans('SellingPrice').' '.$langs->trans('HT').'</td>'."\n";
			//$formMarkup.= '<td><div id="sellingprice_ht'.$i.'"><input size="10" type="text" class="flat" id="sellingdata_ht'.$i.'" name="sellingdata_ht'.$i.'" value=""></div></td></tr>'."\n";
			$formMarkup.= '<td nowrap="nowrap"><div id="sellingprice_ht'.$i.'"><div></td></tr>'."\n";
			$formMarkup.= '<tr><td align="left" width="25%" height="19">&nbsp;'.$langs->trans('CashFlow').' '.$langs->trans('HT').'</td>'."\n";
			$formMarkup.= '<td nowrap="nowrap"><div id="cashflow'.$i.'"></div></td></tr>'."\n";
			$formMarkup.= '<tr><td align="center" colspan="2">'."\n";
			$formMarkup.= '<input type="submit" class="button" name="validate" value="'.$langs->trans('Validate').'">'."\n";
			//$formMarkup.= ' &nbsp; <input onClick="Dialog.closeInfo()" type="button" class="button" name="cancel" value="'.$langs->trans('Cancel').'">'."\n";
			$formMarkup.= '</td></tr></table></form>'."\n";
			$formMarkup.= ajax_updaterWithID("rate".$i,"markup","sellingprice_ht".$i,DOL_URL_ROOT."/product/ajaxproducts.php","&count=".$i,"working")."\n";


			print '<td align="right">'."\n";

			print '<div id="calc_markup'.$i.'" style="display:none">'."\n";
			print $formMarkup."\n";
			print '</div>'."\n";

			print '<table class="nobordernopadding" width="100%"><tr class="nocellnopadd">';
			print '<td class="nobordernopadding" nowrap="nowrap" align="left">';
			if (($objp->info_bits & 2) == 2)
			{
				// Ligne remise predefinie, on ne permet pas modif
			}
			else
			{
				$picto = '<a href="#" onClick="dialogWindow($(\'calc_markup'.$i.'\').innerHTML,\''.$langs->trans('ToCalculateMarkup').'\')">';
				$picto.= img_picto($langs->trans("Calculate"),'calc.png');
				$picto.= '</a>';
				print $html->textwithtooltip($picto,$langs->trans("ToCalculateMarkup"),3,'','',$i);
			}
			print '</td>';
			print '<td class="nobordernopadding" nowrap="nowrap" align="right">'.vatrate($line->marge_tx).'% </td>';
			print '</tr></table>';
			print '</td>';
		}

		// VAT Rate
		print '<td align="right" nowrap="nowrap">'.vatrate($line->tva_tx,'%',$line->info_bits).'</td>';

		// U.P HT
		print '<td align="right" nowrap="nowrap">'.price($line->subprice)."</td>\n";

		// Qty
		print '<td align="right" nowrap="nowrap">';
		if ((($line->info_bits & 2) != 2) && $line->special_code != 3)
		{
			print $line->qty;
		}
		else print '&nbsp;';
		print '</td>';

		// Remise percent (negative or positive)
		if (!empty($line->remise_percent) && $line->special_code != 3)
		{
			print '<td align="right">'.dol_print_reduction($line->remise_percent,$langs)."</td>\n";
		}
		else
		{
			print '<td>&nbsp;</td>';
		}

		// Montant total HT
		if ($line->special_code == 3)
		{
			// Si ligne en option
			print '<td align="right" nowrap="nowrap">'.$langs->trans('Option').'</td>';
		}
		else
		{
			print '<td align="right" nowrap="nowrap">'.price($line->total_ht)."</td>\n";
		}

		// Icone d'edition et suppression
		if ($propal->statut == 0  && $user->rights->propale->creer)
		{
			print '<td align="center">';
			if (($line->info_bits & 2) == 2)
			{
				// Ligne remise predefinie, on permet pas modif
			}
			else
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$propal->id.'&amp;action=editline&amp;lineid='.$line->id.'#'.$line->id.'">';
				print img_edit();
				print '</a>';
			}
			print '</td>';
			print '<td align="center">';
			print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$propal->id.'&amp;action=ask_deleteline&amp;lineid='.$line->id.'">';
			print img_delete();
			print '</a></td>';
			if ($num > 1)
			{
				print '<td align="center">';
				if ($i > 0)
				{
					print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$propal->id.'&amp;action=up&amp;rowid='.$line->id.'">';
					print img_up();
					print '</a>';
				}
				if ($i < $num-1)
				{
					print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$propal->id.'&amp;action=down&amp;rowid='.$line->id.'">';
					print img_down();
					print '</a>';
				}
				print '</td>';
			}
		}
		else
		{
			print '<td colspan="3">&nbsp;</td>';
		}

		print '</tr>';
	}

	// Ligne en mode update
	if ($propal->statut == 0 && $_GET["action"] == 'editline' && $user->rights->propale->creer && $_GET["lineid"] == $line->id)
	{
		print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$propal->id.'#'.$line->id.'" method="POST">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="updateligne">';
		print '<input type="hidden" name="id" value="'.$propal->id.'">';
		print '<input type="hidden" name="lineid" value="'.$_GET["lineid"].'">';
		print '<tr '.$bc[$var].'>';
		print '<td>';
		print '<a name="'.$line->id.'"></a>'; // ancre pour retourner sur la ligne
		if ($line->fk_product > 0)
		{
			print '<input type="hidden" name="productid" value="'.$line->fk_product.'">';
			print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$line->fk_product.'">';
			if ($line->fk_product_type==1) print img_object($langs->trans('ShowService'),'service');
			else print img_object($langs->trans('ShowProduct'),'product');
			print ' '.$line->ref.'</a>';
			print ' - '.nl2br($line->product_label);
			print '<br>';
		}
		if ($_GET["action"] == 'editline')
		{
			// editeur wysiwyg
			if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS)
			{
				require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
				$doleditor=new DolEditor('desc',$line->description,164,'dolibarr_details');
				$doleditor->Create();
			}
			else
			{
				$nbrows=ROWS_2;
				if (! empty($conf->global->MAIN_INPUT_DESC_HEIGHT)) $nbrows=$conf->global->MAIN_INPUT_DESC_HEIGHT;
				print '<textarea name="desc" cols="70" class="flat" rows="'.$nbrows.'">'.dol_htmlentitiesbr_decode($line->description).'</textarea>';
			}
		}
		print '</td>';
		if ($conf->global->PRODUIT_USE_MARKUP)
		{
			print '<td align="right">'.vatrate($line->marge_tx).'%</td>';
		}
		print '<td align="right">';
		print $html->select_tva('tva_tx',$line->tva_tx,$mysoc,$societe,'',$line->info_bits);
		print '</td>';
		print '<td align="right"><input size="6" type="text" class="flat" name="subprice" value="'.price($line->subprice,0,'',0).'"></td>';
		print '<td align="right">';
		if (($line->info_bits & 2) != 2)
		{
			print '<input size="2" type="text" class="flat" name="qty" value="'.$line->qty.'">';
		}
		else print '&nbsp;';
		print '</td>';
		print '<td align="right" nowrap>';
		if (($line->info_bits & 2) != 2)
		{
			print '<input size="1" type="text" class="flat" name="remise_percent" value="'.$line->remise_percent.'">%';
		}
		else print '&nbsp;';
		print '</td>';
		print '<td align="center" colspan="5" valign="center"><input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
		print '<br><input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></td>';
		print '</tr>' . "\n";

		print "</form>\n";
	}
}

?>