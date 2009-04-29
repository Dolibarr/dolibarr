<?PHP
/* Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 \file       scripts/cron/product-graph.php
 \ingroup    product
 \brief      Cr�e les graphiques pour les produits
 \version	$Id$
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

$now = time();
$year = strftime('%Y',$now);

/*
 *
 */
$dir = $conf->produit->dir_output."/temp";
if (!is_dir($dir) )
{
	if (! create_exdir($dir,0755))
	{
		die ("Can't create $dir\n");
	}
}
/*
 *
 */
$sql  = "SELECT distinct(fk_product)";
$sql .= " FROM ".MAIN_DB_PREFIX."facturedet";

$resql = $db->query($sql) ;
$products = array();
if ($resql)
{
	while ($row = $db->fetch_row($resql))
	{
		$fdir = $dir.'/'.get_exdir($row[0],3);

		if ($verbose) print $fdir."\n";
		create_exdir($fdir);

		$products[$row[0]] = $fdir;
	}
	$db->free($resql);
}
else
{
	print $sql;
}
/*
 *
 */
foreach ( $products as $id => $fdir)
{
	$num = array();
	$ca = array();
	$legends = array();

	for ($i = 0 ; $i < 12 ; $i++)
	{
		$legends[$i] = strftime('%b',mktime(1,1,1,($i+1),1, $year) );
		$num[$i]  = 0;
		$ca[$i]  = 0;
	}

	$sql  = "SELECT date_format(f.datef,'%b'), sum(fd.qty), sum(fd.total_ht), date_format(f.datef,'%m')";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture as f,".MAIN_DB_PREFIX."facturedet as fd";
	$sql .= " WHERE f.rowid = fd.fk_facture AND date_format(f.datef,'%Y')='".$year."'";
	$sql .= " AND fd.fk_product ='".$id."'";
	$sql .= " GROUP BY date_format(f.datef,'%b')";
	$sql .= " ORDER BY date_format(f.datef,'%m') ASC ;";

	$resql = $db->query($sql) ;

	if ($resql)
	{
		$i = 0;
		while ($row = $db->fetch_row($resql))
		{
	  $legends[($row[3] - 1)] = $row[0];
	  $num[($row[3] - 1)]  = $row[1];
	  $ca[($row[3] - 1)]  = $row[2];
	  $i++;
		}
		$db->free($resql);
	}
	else
	{
		print $sql;
	}

	if ($i > 0)
	{
		$graph = new DolGraph();

		$file = $fdir ."ventes-".$year."-".$id.".png";
		$title = "Ventes";

		$graph->SetTitle($title);
		$graph->BarLineOneYearArtichow($file, $ca, $num, $legends);

		if ($verbose)
		print "$file\n";
	}
}
/*
 * Ventes annuelles
 *
 */
foreach ( $products as $id => $fdir)
{
	$num = array();
	$ca = array();
	$legends = array();
	$sql  = "SELECT date_format(f.datef,'%Y'), sum(fd.qty), sum(fd.total_ht)";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture as f,".MAIN_DB_PREFIX."facturedet as fd";
	$sql .= " WHERE f.rowid = fd.fk_facture";
	$sql .= " AND fd.fk_product ='".$id."'";
	$sql .= " GROUP BY date_format(f.datef,'%Y')";
	$sql .= " ORDER BY date_format(f.datef,'%Y') ASC ;";

	$resql = $db->query($sql) ;

	if ($resql)
	{
		$i = 0;
		while ($row = $db->fetch_row($resql))
		{
	  $legends[$i] = $row[0];
	  $num[$i]  = $row[1];
	  $ca[$i]  = $row[2];

	  $i++;
		}
		$db->free($resql);
	}
	else
	{
		print $sql;
	}

	if ($i > 0)
	{
		$graph = new DolGraph();

		$file = $fdir ."ventes-".$id.".png";
		$title = "Ventes";

		$graph->SetTitle($title);
		$graph->BarLineAnnualArtichow($file, $ca, $num, $legends);

		if ($verbose)
		print "$file\n";
	}
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
