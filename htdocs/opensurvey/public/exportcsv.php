<?php
/* Copyright (C) 2013      Laurent Destailleur <eldy@users.sourceforge.net>
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
 *	\file       htdocs/opensurvey/public/exportcsv.php
 *	\ingroup    opensurvey
 *	\brief      Page to list surveys
 */


define("NOLOGIN",1);		// This means this output page does not require to be logged.
define("NOCSRFCHECK",1);	// We accept to go on this page from external web site.
require_once('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/opensurvey/class/opensurveysondage.class.php");

$action=GETPOST('action');
$numsondage = $numsondageadmin = '';
if (GETPOST('sondage'))
{
	if (strlen(GETPOST('sondage')) == 24)	// recuperation du numero de sondage admin (24 car.) dans l'URL
	{
		$numsondageadmin=GETPOST("sondage",'alpha');
		$numsondage=substr($numsondageadmin, 0, 16);
	}
	else
	{
		$numsondageadmin='';
		$numsondage=GETPOST("sondage",'alpha');
	}
}

$object=new Opensurveysondage($db);
$result=$object->fetch(0,$numsondage);
if ($result <= 0) dol_print_error('','Failed to get survey id '.$numsondage);


/*
 * Actions
 */



/*
 * View
 */

$now=dol_now();

$nbcolonnes=substr_count($object->sujet,',')+1;
$toutsujet=explode(",",$object->sujet);

// affichage des sujets du sondage
$input.=$langs->trans("Name").";";
for ($i=0;$toutsujet[$i];$i++)
{
	if ($object->format=="D"||$object->format=="D+")
	{
		$input.=''.dol_print_date($toutsujet[$i],'dayhour').';';
	} else {
		$input.=''.$toutsujet[$i].';';
	}
}

$input.="\r\n";

if (strpos($object->sujet,'@') !== false)
{
	$input.=";";
	for ($i=0;$toutsujet[$i];$i++)
	{
		$heures=explode("@",$toutsujet[$i]);
		$input.=''.$heures[1].';';
	}

	$input.="\r\n";
}


$sql ='SELECT nom, reponses';
$sql.=' FROM '.MAIN_DB_PREFIX."opensurvey_user_studs";
$sql.=" WHERE id_sondage='" . $db->escape($numsondage) . "'";
$sql.=" ORDER BY id_users";
dol_syslog("sql=".$sql);
$resql=$db->query($sql);
if ($resql)
{
	$num=$db->num_rows($resql);
	$i=0;
	while ($i < $num)
	{
		$obj=$db->fetch_object($resql);

		// Le nom de l'utilisateur
		$nombase=str_replace("°","'",$obj->nom);
		$input.=$nombase.';';

		//affichage des resultats
		$ensemblereponses=$obj->reponses;
		for ($k=0;$k<$nbcolonnes;$k++)
		{
			$car=substr($ensemblereponses,$k,1);
			if ($car == "1")
			{
				$input.='OK;';
				$somme[$k]++;
			}
			else if ($car == "2")
			{
				$input.='KO;';
				$somme[$k]++;
			}
			else
			{
				$input.=';';
			}
		}

		$input.="\r\n";
		$i++;
	}
}
else dol_print_error($db);


$filesize = strlen($input);
$filename=$numsondage."_".dol_print_date($now,'%Y%m%d%H%M').".csv";



header('Content-Type: text/csv; charset=utf-8');
header('Content-Length: '.$filesize);
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Cache-Control: max-age=10');
echo $input;

exit;
?>