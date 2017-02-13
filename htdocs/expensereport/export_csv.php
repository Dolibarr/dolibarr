<?php
/* Copyright (C) 2004-2011	Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \file       htdocs/expensereport/index.php
 *  \ingroup    expensereport
 *  \brief      Page list of expenses
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/expensereport/class/expensereport.class.php';

$langs->load("users");
$langs->load("trips");

if(!$user->rights->expensereport->export_csv) {
   accessforbidden();
   exit();
}

// Security check
$socid = $_GET["socid"]?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'expensereport','','');

$req = "SELECT * FROM ".MAIN_DB_PREFIX."rights_def WHERE id = '178'";
$result = $db->query($req);
$num = $db->num_rows($result);

if($num < 1) {

   $insert = "INSERT INTO ".MAIN_DB_PREFIX."rights_def (";
   $insert.= "`id` ,";
   $insert.= "`libelle` ,";
   $insert.= "`module` ,";
   $insert.= "`entity` ,";
   $insert.= "`perms` ,";
   $insert.= "`subperms` ,";
   $insert.= "`type` ,";
   $insert.= "`bydefault`";
   $insert.= ")";
   $insert.= "VALUES (";
   $insert.= "'178', 'Exporter les notes de frais au format CSV', 'expensereport', '1', 'export_csv', NULL , 'r', '0'";
   $insert.= ")";

   $req = $db->query($insert);

}


/*
 * View
 */

llxHeader();

print load_fiche_titre($langs->trans("ExportTripCSV"));

print '<div class="tabBar">';

print '<form method="post" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="action" value="export"/>';
print '<p>Choisir le mois à exporter : ';

$year = date('Y', time());
$month = date('m', time());

print '<select name="mois">';

for($i=1;$i<13;$i++) {
   $mois = str_pad($i, 2, "0", STR_PAD_LEFT);
   if($month == $mois) {
     print '<option value="'.$mois.'" selected>'.$mois.'</option>';
   } else {
      print '<option value="'.$mois.'">'.$mois.'</option>';
   }
}

print '</select> ';

print '<select name="annee">';

for($i=2009;$i<$year+1;$i++) {
   if($year == $i) {
     print '<option value="'.$i.'" selected>'.$i.'</option>';
   } else {
      print '<option value="'.$i.'">'.$i.'</option>';
   }
}

print '</select> ';

print '<input type="submit" class="button" value="Exporter" />';
print '</p>';
print '</form>'."\n";

// Si c'est une action
if (isset($_POST['action']))
{
	if($_POST['action'] == 'export')
	{
		$select_date = $_POST['annee'].'-'.$_POST['mois'];

		//var_dump($conf->expensereport->dir_output.'/export/');
		if (!file_exists($conf->expensereport->dir_output.'/export/'))
		{
			dol_mkdir($conf->expensereport->dir_output.'/export/');
		}

		$dir = $conf->expensereport->dir_output.'/export/expensereport-'.$select_date.'.csv';
		$outputlangs = $langs;
		$outputlangs->charset_output = 'UTF-8';

		$sql = "SELECT d.rowid, d.ref, d.total_ht, d.total_tva, d.total_ttc";
		$sql.= " FROM ".MAIN_DB_PREFIX."expensereport as d";
        $sql.= ' AND d.entity IN ('.getEntity('expensereport', 1).')';
		$sql.= " ORDER BY d.rowid";

		$result = $db->query($sql);
		$num = $db->num_rows($result);
		if ($num)
		{
			$open = fopen($dir,"w+");

			$ligne = "ID, Référence, ----, Date paiement, Montant HT, TVA, Montant TTC\n";
			for ($i = 0; $i < $num; $i++)
			{
				$ligne.= "----, ----, ----, ----, ----, ----, ----\n";
				$objet = $db->fetch_object($result);
				$objet->total_ht = number_format($objet->total_ht,2);
				$objet->total_tva = number_format($objet->total_tva,2);
				$objet->total_ttc = number_format($objet->total_ttc,2);
				$objet->ref = trim($objet->ref);
				$ligne.= "{$objet->rowid}, {$objet->ref}, ----, {$objet->total_ht}, {$objet->total_tva}, {$objet->total_ttc}\n";

				$ligne.= "--->, Ligne, Type, Description, ----, ----, ----\n";


				$sql2 = "SELECT de.rowid, t.label as libelle, de.comments, de.total_ht, de.total_tva, de.total_ttc";
				$sql2.= " FROM ".MAIN_DB_PREFIX."expensereport_det as de,";
				$sql2.= " ".MAIN_DB_PREFIX."c_type_fees as t";
				$sql2.= " WHERE de.fk_c_type_fees = t.id";
				$sql2.= " AND de.fk_expensereport = '".$objet->rowid."'";
				$sql2.= " ORDER BY de.date";

				$result2 = $db->query($sql2);
				$num2 = $db->num_rows($result2);

				if($num2) {
					for ($a = 0; $a < $num2; $a++)
					{
						$objet2 = $db->fetch_object($result2);
						$objet2->total_ht = number_format($objet2->total_ht,2);
						$objet2->total_tva = number_format($objet2->total_tva,2);
						$objet2->total_ttc = number_format($objet2->total_ttc,2);
						$objet2->comments = str_replace(',',';',$objet2->comments);
						$objet2->comments = str_replace("\r\n",' ',$objet2->comments);
						$objet2->comments = str_replace("\n",' ',$objet2->comments);

						$ligne.= "--->, {$objet2->rowid}, {$objet2->libelle}, {$objet2->comments}, {$objet2->total_ht}, {$objet2->total_tva}, {$objet2->total_ttc}\n";
					}
				}

			}

			$ligne = $outputlangs->convToOutputCharset($ligne);

			fwrite($open,$ligne);
			fclose($open);

			print '<a href="'.DOL_URL_ROOT.'/document.php?modulepart=expensereport&file=export%2Fexpensereport-'.$select_date.'.csv" target="_blank">Télécharger le fichier expensereport-'.$select_date.'.csv</a>';

		} else {

			print '<b>'.$langs->trans('NoTripsToExportCSV').'</b>';

		}
	}
}

print '</div>';

llxFooter();

$db->close();
