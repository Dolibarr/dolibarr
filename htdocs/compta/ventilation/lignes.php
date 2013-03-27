<?php
/* Copyright (C) 2002-2005	Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006		Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 */

/**
 * 		\file       htdocs/compta/ventilation/lignes.php
 * 		\ingroup    facture
 * 		\brief      Page de detail des lignes de ventilation d'une facture
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

$langs->load("bills");
$langs->load("compta");

if (!$user->rights->facture->lire) accessforbidden();
if (!$user->rights->compta->ventilation->creer) accessforbidden();

// Securite acces client
if ($user->societe_id > 0) accessforbidden();


llxHeader('');

/*
* Lignes de factures
*
*/
$page = $_GET["page"];
if ($page < 0) $page = 0;
$limit = $conf->liste_limit;
$offset = $limit * $page ;

$sql = "SELECT f.facnumber, f.rowid as facid, l.fk_product, l.description, f.total_ttc as price, l.qty, l.rowid, l.tva_tx, l.fk_code_ventilation, c.intitule, c.numero,";
$sql.= " p.rowid as product_id, p.ref as product_ref, p.label as product_label, p.fk_product_type as type";
$sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
$sql.= " , ".MAIN_DB_PREFIX."compta_compte_generaux as c";
$sql.= " , ".MAIN_DB_PREFIX."facturedet as l";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = l.fk_product";
$sql.= " WHERE f.rowid = l.fk_facture AND f.fk_statut = 1 AND l.fk_code_ventilation <> 0 ";
$sql.= " AND f.entity = ".$conf->entity;
$sql.= " AND c.rowid = l.fk_code_ventilation";
if (dol_strlen(trim($_GET["search_facture"])))
{
	$sql .= " AND f.facnumber LIKE '%".$_GET["search_facture"]."%'";
}

$sql .= " ORDER BY l.rowid DESC";
$sql .= $db->plimit($limit+1,$offset);

$result = $db->query($sql);

if ($result)
{
	$num_lignes = $db->num_rows($result);
	$i = 0;

	print_barre_liste($langs->trans("InvoiceDispatched"),$page,"lignes.php","",$sortfield,$sortorder,'',$num_lignes);

	print '<form method="GET" action="lignes.php">';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td>'.$langs->trans("Invoice").'</td>';
	print '<td>'.$langs->trans("Ref").'</td>';
	print '<td>'.$langs->trans("Label").'</td>';
	print '<td>'.$langs->trans("Description").'</td>';
	print '<td align="left">'.$langs->trans("Montant").'</td>';
	print '<td colspan="2" align="left">'.$langs->trans("Compte").'</td>';
	print "</tr>\n";

	print '<tr class="liste_titre"><td><input name="search_facture" size="8" value="'.$_GET["search_facture"].'"></td>';
	print '<td>&nbsp;</td>';
	print '<td align="right">&nbsp;</td>';
	print '<td align="right">&nbsp;</td>';
	print '<td align="center">&nbsp;</td>';
	print '<td align="right">';
	print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '</td>';
	print "</tr>\n";

	$facture_static=new Facture($db);
	$product_static=new Product($db);

	$var=True;
	while ($i < min($num_lignes, $limit))
	{
		$objp = $db->fetch_object($result);
		$var=!$var;
		$codeCompta = $objp->numero.' '.$objp->intitule;

		print "<tr $bc[$var]>";

		// Ref facture
		$facture_static->ref=$objp->facnumber;
		$facture_static->id=$objp->facid;
		print '<td>'.$facture_static->getNomUrl(1).'</td>';

		// Ref produit
		$product_static->ref=$objp->product_ref;
		$product_static->id=$objp->product_id;
		$product_static->type=$objp->type;
		print '<td>';
		if ($product_static->id) print $product_static->getNomUrl(1);
		else print '&nbsp;';
		print '</td>';

		print '<td>'.dol_trunc($objp->product_label,24).'</td>';
		print '<td>'.nl2br(dol_trunc($objp->description,32)).'</td>';
		print '<td align="left">'.price($objp->price).'</td>';
		print '<td align="left">'.$codeCompta.'</td>';

		print "</tr>";
		$i++;
	}
}
else
{
	print $db->error();
}

print "</table></form>";


llxFooter();
$db->close();
?>
