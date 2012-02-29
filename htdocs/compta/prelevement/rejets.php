<?PHP
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2010-2012 Juanjo Menent 		<jmenent@2byte.es>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 
 */

require("../bank/pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/compta/prelevement/class/rejet-prelevement.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/paiement/class/paiement.class.php");

$langs->load("withdrawals");
$langs->load("companies");
$langs->load("categories");

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'prelevement','','','bons');

// Get supervariables
$page = GETPOST('page','int');
$sortorder = GETPOST('sortorder','alpha');
$sortfield = GETPOST('sortfield','alpha');

/*
 * View
 */

llxHeader('',$langs->trans("WithdrawsRefused"));

$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

if ($sortorder == "") $sortorder="DESC";
if ($sortfield == "") $sortfield="p.datec";

$rej = new RejetPrelevement($db, $user);

/*
 * Liste des factures
 *
 */
$sql = "SELECT pl.rowid, pr.motif, p.ref, pl.statut";
$sql.= " , s.rowid as socid, s.nom";
$sql.= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
$sql.= " , ".MAIN_DB_PREFIX."prelevement_rejet as pr";
$sql.= " , ".MAIN_DB_PREFIX."prelevement_lignes as pl";
$sql.= " , ".MAIN_DB_PREFIX."societe as s";
$sql.= " WHERE pr.fk_prelevement_lignes = pl.rowid";
$sql.= " AND pl.fk_prelevement_bons = p.rowid";
$sql.= " AND pl.fk_soc = s.rowid";
$sql.= " AND p.entity = ".$conf->entity;
if ($socid) $sql.= " AND s.rowid = ".$socid;
$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);

$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;

	print_barre_liste($langs->trans("WithdrawsRefused"), $page, "rejets.php", $urladd, $sortfield, $sortorder, '', $num);
	print"\n<!-- debut table -->\n";
	print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Nb"),"rejets.php","p.ref",'',$urladd);
	print_liste_field_titre($langs->trans("ThirdParty"),"rejets.php","s.nom",'',$urladd);
	print_liste_field_titre($langs->trans("Reason"),"rejets.php","pr.motif","",$urladd);
	print '</tr>';

	$var=True;

	$total = 0;

	while ($i < min($num,$conf->liste_limit))
	{
		$obj = $db->fetch_object($result);

		print "<tr $bc[$var]><td>";
		print '<img border="0" src="./img/statut'.$obj->statut.'.png"></a>&nbsp;';
		print '<a href="'.DOL_URL_ROOT.'/compta/prelevement/ligne.php?id='.$obj->rowid.'">';

		print substr('000000'.$obj->rowid, -6)."</a></td>";

		print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->socid.'">'.stripslashes($obj->nom)."</a></td>\n";

		print '<td>'.$rej->motifs[$obj->motif].'</td>';
		print "</tr>\n";
		$var=!$var;
		$i++;
	}

	print "</table>";
	$db->free($result);
}
else
{
	dol_print_error($db);
}

$db->close();

llxFooter();
?>
