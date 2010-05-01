<?php
/* Copyright (C) 2010 Regis Houssin <regis@dolibarr.fr>
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
 */
?>

<!-- BEGIN PHP TEMPLATE -->

<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>">
<input type="hidden" name="action" value="add">
<input type="hidden" name="canvas" value="<?php echo $_GET['canvas']; ?>">


		else $title=$langs->trans("NewProduct");
		print_fiche_titre($title);

<table class="border" width="100%">

<tr>
<td class="fieldrequired" width="20%"><?php echo $langs->trans("Ref"); ?></td>
<td><input name="ref" size="40" maxlength="32" value="<?php echo $_POST["ref"]; ?>">
		if ($_error == 1)
		{
			print $langs->trans("RefAlreadyExists");
		}
</td></tr>

<tr>
<td class="fieldrequired"><?php echo $langs->trans("Label"); ?></td>
<td><input name="libelle" size="40" value="<?php echo $_POST["libelle"]; ?>"></td>
</tr>

<tr>
<td class="fieldrequired"><?php echo $langs->trans("Status"); ?></td><td>
		$statutarray=array('1' => $langs->trans("OnSell"), '0' => $langs->trans("NotOnSell"));
		$html->select_array('statut',$statutarray,$_POST["statut"]);
</td>
</tr>

		if ($_GET["type"] != 1 && $conf->stock->enabled)
		{
<tr><td><?php echo $langs->trans("StockLimit"); ?></td><td>
<input name="seuil_stock_alerte" size="4" value="<?php echo $_POST["seuil_stock_alerte"]; ?>">
</td></tr>
		}
		else
		{
<input name="seuil_stock_alerte" type="hidden" value="0">
		}

<tr><td valign="top"><?php echo $langs->trans("Description"); ?></td><td>

		if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC)
		{
			require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
			$doleditor=new DolEditor('desc',$_POST["desc"],160,'dolibarr_notes','',false);
			$doleditor->Create();
		}
		else
		{
			print '<textarea name="desc" rows="4" cols="90">';
			print $_POST["desc"];
			print '</textarea>';
		}

</td></tr>

<tr><td><?php echo $langs->trans("Nature"); ?></td><td>
			$statutarray=array('1' => $langs->trans("Finished"), '0' => $langs->trans("RowMaterial"));
			$html->select_array('finished',$statutarray,$_POST["finished"]);
</td></tr>

<tr><td><?php echo $langs->trans("Weight"); ?></td><td>
<input name="weight" size="4" value="<?php echo $_POST["weight"]; ?>">
			print $formproduct->select_measuring_units("weight_units","weight");
</td></tr>

<tr><td><?php echo $langs->trans("Length"); ?></td><td>
<input name="size" size="4" value="<?php echo $_POST["size"]; ?>">
			print $formproduct->select_measuring_units("size_units","size");
</td></tr>

<tr><td><?php echo $langs->trans("Surface"); ?></td><td>
<input name="surface" size="4" value="<?php echo $_POST["surface"]; ?>">
			print $formproduct->select_measuring_units("surface_units","surface");
</td></tr>

<tr><td><?php echo $langs->trans("Volume"); ?></td><td>
<input name="volume" size="4" value="<?php echo $_POST["volume"]; ?>">
			print $formproduct->select_measuring_units("volume_units","volume");
</td></tr>

		// Hidden
		if (($_GET["type"] != 1 && $user->rights->produit->hidden)
		|| ($_GET["type"] == 1 && $user->rights->service->hidden))
		{
<tr><td><?php echo $langs->trans("Hidden"); ?></td><td>
			print $html->selectyesno('hidden',$product->hidden);
</td></tr>
		}
		else
		{
			print yn("No");
		}

<tr><td valign="top"><?php echo $langs->trans("NoteNotVisibleOnBill"); ?></td><td>
		if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC)
		{
			require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
			$doleditor=new DolEditor('note',$_POST["note"],180,'dolibarr_notes','',false);
			$doleditor->Create();
		}
		else
		{
			print '<textarea name="note" rows="8" cols="70">';
			print $_POST["note"];
			print '</textarea>';
		}
</td></tr>
</table>

<br>

		if ($conf->global->PRODUIT_MULTIPRICES)
		{
			// We do no show price array on create when multiprices enabled.
			// We must set them on prices tab.
		}
		else
		{
			print '<table class="border" width="100%">';

			// PRIX
			print '<tr><td>'.$langs->trans("SellingPrice").'</td>';
			print '<td><input name="price" size="10" value="'.$product->price.'">';
			print $html->select_PriceBaseType($product->price_base_type, "price_base_type");
			print '</td></tr>';

			// MIN PRICE
			print '<tr><td>'.$langs->trans("MinPrice").'</td>';
			print '<td><input name="price_min" size="10" value="'.$product->price_min.'">';
			print '</td></tr>';

			// VAT
			print '<tr><td width="20%">'.$langs->trans("VATRate").'</td><td>';
			print $html->select_tva("tva_tx",$conf->defaulttx,$mysoc,'');
			print '</td></tr>';

			print '</table>';

			print '<br>';
		}

<center><input type="submit" class="button" value="<?php echo $langs->trans("Create"); ?>"></center>

</form>

<!-- END PHP TEMPLATE -->