<?PHP
/* Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 \file       	scripts/cron/fournisseur-graph.php
 \ingroup    	fournisseur
 \brief      	Script de generation graph ca fournisseur depuis tables fournisseur_ca
 \deprecated		Ces graph ne sont pas utilises.
 \version		$Id$
 */

// Test si mode CLI
$sapi_type = php_sapi_name();
$script_file=__FILE__;
if (eregi('([^\\\/]+)$',$script_file,$reg)) $script_file=$reg[1];

if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Erreur: Vous utilisez l'interpreteur PHP pour le mode CGI. Pour executer $script_file en ligne de commande, vous devez utiliser l'interpreteur PHP pour le mode CLI.\n";
	exit;
}

// Recupere env dolibarr
$version='$Revision$';
$path=eregi_replace($script_file,'',$_SERVER["PHP_SELF"]);

require_once($path."../../htdocs/master.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/dolgraph.class.php");
require_once(DOL_DOCUMENT_ROOT."/cron/functions_cron.lib.php");

print '***** '.$script_file.' ('.$version.') *****'."\n";
print '--- start'."\n";


$error=0;
$verbose = 0;

for ($i = 1 ; $i < sizeof($argv) ; $i++)
{
	if ($argv[$i] == "-v")
	{
		$verbose = 1;
	}
	if ($argv[$i] == "-vv")
	{
		$verbose = 2;
	}
	if ($argv[$i] == "-vvv")
	{
		$verbose = 3;
	}
}

$dir = $conf->fournisseur->dir_temp;
$result=create_exdir($dir);
if ($result < 0)
{
	dol_print_error('','Failed to create dir '.$dir);
	exit;
}



$sql  = "SELECT distinct(fk_societe)";
$sql .= " FROM ".MAIN_DB_PREFIX."fournisseur_ca";

$resql = $db->query($sql) ;
$fournisseurs = array();
if ($resql)
{
	while ($row = $db->fetch_row($resql))
	{
		$fdir = $dir.'/'.get_exdir($row[0],3);

		if ($verbose) print $fdir."\n";
		
		//print 'Create fdir='.$fdir;
		$result=create_exdir($fdir);

		$fournisseurs[$row[0]] = $fdir;
	}
	$db->free($resql);
}
else
{
	print $sql;
}



foreach ($fournisseurs as $id => $fdir)
{
	$values_gen = array();
	$values_ach = array();
	$legends = array();
	$sql  = "SELECT year, ca_genere, ca_achat";
	$sql .= " FROM ".MAIN_DB_PREFIX."fournisseur_ca";
	$sql .= " WHERE fk_societe = $id";
	$sql .= " ORDER BY year ASC";

	$resql = $db->query($sql) ;

	if ($resql)
	{
		$i = 0;
		while ($row = $db->fetch_row($resql))
		{
		  $values_gen[$i]  = $row[1];
		  $values_ach[$i]  = $row[2];
		  $legends[$i] = $row[0];
		   
		  $i++;
		}
		$db->free($resql);
	}
	else
	{
		print $sql;
	}

	$graph = new DolGraph();

	$file = $fdir ."ca_genere-".$id.".png";
	$title = "CA genere par ce fournisseur (euros HT)";

	$graph->SetTitle($title);
	$graph->BarAnnualArtichow($file, $values_ach, $legends);

	if ($verbose)
	print "$file\n";

	$file = $fdir ."ca_achat-".$id.".png";
	$title = "Charges pour ce fournisseur (euros HT)";

	$graph->SetTitle($title);
	$graph->BarAnnualArtichow($file, $values_ach, $legends);

	if ($verbose)
	print "$file\n";
}


if (! $error)
{
	print '--- end ok'."\n";
}
else
{
	print '--- end error code='.$error."\n";
}

?>
