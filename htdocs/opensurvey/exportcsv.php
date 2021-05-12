<?php
/* Copyright (C) 2013      Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014 Marcos García				<marcosgdf@gmail.com>
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
 *	\file       htdocs/opensurvey/exportcsv.php
 *	\ingroup    opensurvey
 *	\brief      Page to list surveys
 */


<<<<<<< HEAD
require_once('../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/opensurvey/class/opensurveysondage.class.php");

$action=GETPOST('action','aZ09');
$numsondage = '';
if (GETPOST('id'))
{
	$numsondage=GETPOST("id",'alpha');
}

$object=new Opensurveysondage($db);
$result=$object->fetch(0,$numsondage);
if ($result <= 0) dol_print_error('','Failed to get survey id '.$numsondage);
=======
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/files.lib.php";
require_once DOL_DOCUMENT_ROOT."/opensurvey/class/opensurveysondage.class.php";

$action=GETPOST('action', 'aZ09');
$numsondage = '';
if (GETPOST('id'))
{
	$numsondage=GETPOST("id", 'alpha');
}

$object=new Opensurveysondage($db);
$result=$object->fetch(0, $numsondage);
if ($result <= 0) dol_print_error('', 'Failed to get survey id '.$numsondage);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9


/*
 * Actions
 */



/*
 * View
 */

$now=dol_now();

<<<<<<< HEAD
$nbcolonnes=substr_count($object->sujet,',')+1;
$toutsujet=explode(",",$object->sujet);
=======
$nbcolonnes=substr_count($object->sujet, ',')+1;
$toutsujet=explode(",", $object->sujet);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

// affichage des sujets du sondage
$input.=$langs->trans("Name").";";
for ($i=0;$toutsujet[$i];$i++)
{
	if ($object->format=="D")
	{
<<<<<<< HEAD
		$input.=''.dol_print_date($toutsujet[$i],'dayhour').';';
=======
		$input.=''.dol_print_date($toutsujet[$i], 'dayhour').';';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	} else {
		$input.=''.$toutsujet[$i].';';
	}
}

$input.="\r\n";

<<<<<<< HEAD
if (strpos($object->sujet,'@') !== false)
=======
if (strpos($object->sujet, '@') !== false)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
{
	$input.=";";
	for ($i=0;$toutsujet[$i];$i++)
	{
<<<<<<< HEAD
		$heures=explode("@",$toutsujet[$i]);
=======
		$heures=explode("@", $toutsujet[$i]);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$input.=''.$heures[1].';';
	}

	$input.="\r\n";
}


$sql ='SELECT nom as name, reponses';
$sql.=' FROM '.MAIN_DB_PREFIX."opensurvey_user_studs";
$sql.=" WHERE id_sondage='" . $db->escape($numsondage) . "'";
$sql.=" ORDER BY id_users";
$resql=$db->query($sql);
if ($resql)
{
	$num=$db->num_rows($resql);
	$i=0;
	while ($i < $num)
	{
		$obj=$db->fetch_object($resql);

		// Le name de l'utilisateur
<<<<<<< HEAD
		$nombase=str_replace("°","'",$obj->name);
=======
		$nombase=str_replace("°", "'", $obj->name);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$input.=$nombase.';';

		//affichage des resultats
		$ensemblereponses=$obj->reponses;
		for ($k=0;$k<$nbcolonnes;$k++)
		{
<<<<<<< HEAD
			$car=substr($ensemblereponses,$k,1);
=======
			$car=substr($ensemblereponses, $k, 1);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			if ($car == "1")
			{
				$input.='OK;';
				$somme[$k]++;
			}
<<<<<<< HEAD
			else if ($car == "2")
=======
			elseif ($car == "2")
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD
$filename=$numsondage."_".dol_print_date($now,'%Y%m%d%H%M').".csv";
=======
$filename=$numsondage."_".dol_print_date($now, '%Y%m%d%H%M').".csv";
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9



header('Content-Type: text/csv; charset=utf-8');
header('Content-Length: '.$filesize);
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Cache-Control: max-age=10');
echo $input;

exit;
