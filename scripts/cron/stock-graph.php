<?PHP
/* Copyright (C) 2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 \file       scripts/cron/stock-graph.php
 \ingroup    stock
 \brief      Créé le graph de valorisation du stock
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


/*
 * Main
 */

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


$dir = DOL_DATA_ROOT."/entrepot/temp";
$result=create_exdir($dir);


$sql  = "SELECT distinct(fk_entrepot)";
$sql .= " FROM ".MAIN_DB_PREFIX."entrepot_valorisation";

$resql = $db->query($sql) ;
$entrepots = array();
if ($resql)
{
	$i = 0;
	while ($row = $db->fetch_row($resql))
	{
		$entrepots[$row[0]] = $row[0];
	}
	$db->free($resql);
}
else
{
	dolibarr_print_error($db,$sql);
}

$now = time();
$year = strftime('%Y',$now);
$day = strftime('%j', $now);
for ($i = 0 ; $i < strftime('%j',$now) ; $i++)
{
	foreach ($entrepots as $key => $ent)
	{
		$values[$key][$i] = 0;
	}
	$values[0][$i] = 0;
	$legends[$i] = strftime('%b',dolibarr_mktime(12,0,0,1,1,2006) + ($i * 3600 * 24));
	//print $legends[$i].strftime('%j',$now);
}

/*
 * Read values
 */
$sql  = "SELECT date_format(date_calcul,'%j'), valo_pmp, fk_entrepot";
$sql .= " FROM ".MAIN_DB_PREFIX."entrepot_valorisation as e";
$sql .= " WHERE date_format(date_calcul, '%Y') = '".$year."'";
$sql .= " ORDER BY date_calcul ASC";

$resql = $db->query($sql) ;

if ($resql)
{
	$i = 0;
	$last_day = 0;
	while ($row = $db->fetch_row($resql))
	{
		if ($last_day > 0)
		{
			for ($j = $last_day + 1 ; $j < $row[0] ; $j++)
			{
				foreach ($entrepots as $key => $ent)
				{
					$values[$key][$j] = $values[$key][$last_day];
				}
				$values[0][$j] = $values[0][$last_day];
			}
		}
		$last_day = $row[0];

		$max_day = $row[0];
		$values[$row[2]][$row[0]] = $row[1];
		$values[0][$row[0]] += $row[1];

		$total[$row[2]] += abs($row[1]);
		$total[0] += abs($row[1]);
		$i++;
	}
	$db->free($resql);
}
else
{
	dolibarr_print_error($db,$sql);
}

for ($i = $max_day + 1 ; $i < ($day + 1) ; $i++)
{
	foreach ($entrepots as $key => $ent)
	{
		$values[$key][$i] = $values[$key][$max_day];
	}
	$values[0][$i] = $values[0][$max_day];
}


// PMP = (quantités en stock x pmp ancien + nouvelles quantités x prix d'acquisition)/ (anciennes quantités + nouvelles quantités)

/*
 * For each warehouse
 */
foreach ($entrepots as $key => $ent)
{
	$file = $dir ."/entrepot-".$key."-".$year.".png";
	$title = "Valorisation PMP du stock de l'entrepôt (euros HT) sur l'année ".$year;

	if ($total[$key] > 0)
	graph_datas($file, $title, $values[$key], $legends);

	if ($verbose)
	print "$file\n";
}

/*
 * For all warehouses
 */
$file = $dir."/entrepot-".$year.".png";
$title = "Valorisation PMP (Prix Moyen Pondéré) du stock global (euros HT) sur l'année ".$year;

if ($total[$key] > 0)
graph_datas($file, $title, $values[0], $legends);

if ($verbose)
print "$file\n";



/**	\brief	Build graph
 *	\param	file		File
 *	\param	title		Title
 *	\param	values		Value
 *	\param	legends		Legend
 */
function graph_datas($file, $title, $values, $legends)
{
	$width=800;
	$height=230;

	print "Build graph ".$file."\n";
	
	$newvalues=array();
	foreach ($values as $abs=>$ord)
	{
		$newvalues[]=array($legends[$abs],$ord);
	}

	$px = new DolGraph();
	$px->SetData($newvalues);
	//$px->SetLegend('');
	$px->SetMaxValue($px->GetCeilMaxValue());
	$px->SetMinValue($px->GetFloorMinValue());
	$px->SetTitle($title);
	$px->SetWidth($width);
	$px->SetHeight($height);
	$px->SetType('lines');
	$px->setBgColor('default');
	$px->setBgColorGrid(array(255,255,255));
	$px->SetHideXGrid(true);
	$px->SetLabelInterval(31);
	$px->draw($file);
}
?>
