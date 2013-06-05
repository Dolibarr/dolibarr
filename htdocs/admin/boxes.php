<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *   \file       htdocs/admin/boxes.php
 *   \brief      Page to setup boxes
 */

require '../main.inc.php';
include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/infobox.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

$langs->load("admin");
$langs->load("boxes");

if (! $user->admin) accessforbidden();

$rowid = GETPOST('rowid','int');
$action = GETPOST('action','alpha');
$errmesg='';

// Define possible position of boxes
$pos_name = getStaticMember('InfoBox','listOfPages');
$boxes = array();


/*
 * Actions
 */

if ($action == 'addconst')

{
    dolibarr_set_const($db, "MAIN_BOXES_MAXLINES",$_POST["MAIN_BOXES_MAXLINES"],'',0,'',$conf->entity);
}

if ($action == 'add')
{
    $error=0;

    $db->begin();

	// Initialize distinctfkuser with all already existing values of fk_user (user that use a personalized view of boxes for pos)
	$distinctfkuser=array();
	if (! $error)
	{
		$sql = "SELECT fk_user";
		$sql.= " FROM ".MAIN_DB_PREFIX."user_param";
		$sql.= " WHERE param = 'MAIN_BOXES_".$db->escape(GETPOST("pos","alpha"))."' AND value = '1'";
		$sql.= " AND entity = ".$conf->entity;
		$resql = $db->query($sql);
		dol_syslog("boxes.php search fk_user to activate box for sql=".$sql);
		if ($resql)
		{
		    $num = $db->num_rows($resql);
            $i=0;
		    while ($i < $num)
		    {
		        $obj=$db->fetch_object($resql);
		        $distinctfkuser[$obj->fk_user]=$obj->fk_user;
		        $i++;
		    }
		}
		else
		{
		    $errmesg=$db->lasterror();
		    $error++;
		}
	}

	foreach($distinctfkuser as $fk_user)
	{
	    if (! $error && $fk_user != 0)    // We will add fk_user = 0 later.
	    {
	        $sql = "INSERT INTO ".MAIN_DB_PREFIX."boxes (";
	        $sql.= "box_id, position, box_order, fk_user, entity";
	        $sql.= ") values (";
	        $sql.= GETPOST("boxid","int").", ".GETPOST("pos","alpha").", 'A01', ".$fk_user.", ".$conf->entity;
	        $sql.= ")";

	        dol_syslog("boxes.php activate box sql=".$sql);
	        $resql = $db->query($sql);
	        if (! $resql)
	        {
		        $errmesg=$db->lasterror();
	            $error++;
	        }
	    }
	}

	// If value 0 was not included, we add it.
	if (! $error)
	{
	    $sql = "INSERT INTO ".MAIN_DB_PREFIX."boxes (";
	    $sql.= "box_id, position, box_order, fk_user, entity";
	    $sql.= ") values (";
	    $sql.= GETPOST("boxid","int").", ".GETPOST("pos","alpha").", 'A01', 0, ".$conf->entity;
	    $sql.= ")";

	    dol_syslog("boxes.php activate box sql=".$sql);
	    $resql = $db->query($sql);
        if (! $resql)
        {
		    $errmesg=$db->lasterror();
            $error++;
        }
	}

	if (! $error)
	{
		header("Location: boxes.php");
	    $db->commit();
		exit;
	}
	else
	{
	    $db->rollback();
	}
}

if ($action == 'delete')
{
	$sql = "SELECT box_id FROM ".MAIN_DB_PREFIX."boxes";
	$sql.= " WHERE rowid=".$rowid;

	$resql = $db->query($sql);
	$obj=$db->fetch_object($resql);
    if (! empty($obj->box_id))
    {
	    $db->begin();

    	// Remove all personalized setup when a box is activated or disabled (why removing all ? We removed only removed boxes)
        //	$sql = "DELETE FROM ".MAIN_DB_PREFIX."user_param";
        //	$sql.= " WHERE param LIKE 'MAIN_BOXES_%'";
        //	$resql = $db->query($sql);

	    $sql = "DELETE FROM ".MAIN_DB_PREFIX."boxes";
	    $sql.= " WHERE entity = ".$conf->entity;
    	$sql.= " AND box_id=".$obj->box_id;

    	$resql = $db->query($sql);

    	$db->commit();
    }
}

if ($action == 'switch')
{
	// On permute les valeur du champ box_order des 2 lignes de la table boxes
	$db->begin();

	$objfrom=new ModeleBoxes($db);
	$objfrom->fetch($_GET["switchfrom"]);

	$objto=new ModeleBoxes($db);
	$objto->fetch($_GET["switchto"]);

	$resultupdatefrom=0;
	$resultupdateto=0;
	if (is_object($objfrom) && is_object($objto))
	{
	    $newfirst=$objto->box_order;
		$newsecond=$objfrom->box_order;
	    if ($newfirst == $newsecond)
	    {
	         $newsecondchar=preg_replace('/[0-9]+/','',$newsecond);
	         $newsecondnum=preg_replace('/[a-zA-Z]+/','',$newsecond);
	         $newsecond=sprintf("%s%02d",$newsecondchar?$newsecondchar:'A',$newsecondnum+1);
	    }
		$sql="UPDATE ".MAIN_DB_PREFIX."boxes SET box_order='".$newfirst."' WHERE rowid=".$objfrom->rowid;
		dol_syslog($sql);
		$resultupdatefrom = $db->query($sql);
		if (! $resultupdatefrom) { dol_print_error($db); }

		$sql="UPDATE ".MAIN_DB_PREFIX."boxes SET box_order='".$newsecond."' WHERE rowid=".$objto->rowid;
		dol_syslog($sql);
		$resultupdateto = $db->query($sql);
		if (! $resultupdateto) { dol_print_error($db); }
	}

	if ($resultupdatefrom && $resultupdateto)
	{
		$db->commit();
	}
	else
	{
		$db->rollback();
	}
}


/*
 * View
 */

$form=new Form($db);

llxHeader('',$langs->trans("Boxes"));

print_fiche_titre($langs->trans("Boxes"),'','setup');

print $langs->trans("BoxesDesc")." ".$langs->trans("OnlyActiveElementsAreShown")."<br>\n";

dol_htmloutput_errors($errmesg);


/*
 * Recherche des boites actives par defaut pour chaque position possible
 * On stocke les boites actives par defaut dans $boxes[position][id_boite]=1
 */

$actives = array();

$sql = "SELECT b.rowid, b.box_id, b.position, b.box_order,";
$sql.= " bd.rowid as boxid";
$sql.= " FROM ".MAIN_DB_PREFIX."boxes as b, ".MAIN_DB_PREFIX."boxes_def as bd";
$sql.= " WHERE b.entity = ".$conf->entity;
$sql.= " AND b.box_id = bd.rowid";
$sql.= " AND b.fk_user=0";
$sql.= " ORDER by b.position, b.box_order";

dol_syslog("Search available boxes sql=".$sql, LOG_DEBUG);
$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;
	$decalage=0;
	$var=false;
	while ($i < $num)
	{
		$var = ! $var;
		$obj = $db->fetch_object($resql);
		$boxes[$obj->position][$obj->box_id]=1;
		$i++;

		array_push($actives,$obj->box_id);

		if ($obj->box_order == '' || $obj->box_order == '0' || $decalage) $decalage++;
		// On renumerote l'ordre des boites si l'une d'elle est a ''
		// This occurs just after an insert.
		if ($decalage)
		{
			$sql="UPDATE ".MAIN_DB_PREFIX."boxes SET box_order='".$decalage."' WHERE rowid=".$obj->rowid;
			$db->query($sql);
		}
	}

	if ($decalage)
	{
	    // Si on a renumerote, on corrige champ box_order
		// This occurs just after an insert.
		$sql = "SELECT box_order";
		$sql.= " FROM ".MAIN_DB_PREFIX."boxes";
		$sql.= " WHERE entity = ".$conf->entity;
		$sql.= " AND LENGTH(box_order) <= 2";

		dol_syslog("Execute requests to renumber box order sql=".$sql);
		$result = $db->query($sql);
		if ($result)
		{
			while ($record = $db->fetch_array($result))
			{
				if (dol_strlen($record['box_order']) == 1)
				{
					if (preg_match("/[13579]{1}/",substr($record['box_order'],-1)))
					{
						$box_order = "A0".$record['box_order'];
						$sql="UPDATE ".MAIN_DB_PREFIX."boxes SET box_order = '".$box_order."' WHERE entity = ".$conf->entity." AND box_order = '".$record['box_order']."'";
						$resql = $db->query($sql);
					}
					else if (preg_match("/[02468]{1}/",substr($record['box_order'],-1)))
					{
						$box_order = "B0".$record['box_order'];
						$sql="UPDATE ".MAIN_DB_PREFIX."boxes SET box_order = '".$box_order."' WHERE entity = ".$conf->entity." AND box_order = '".$record['box_order']."'";
						$resql = $db->query($sql);
					}
				}
				else if (dol_strlen($record['box_order']) == 2)
				{
					if (preg_match("/[13579]{1}/",substr($record['box_order'],-1)))
					{
						$box_order = "A".$record['box_order'];
						$sql="UPDATE ".MAIN_DB_PREFIX."boxes SET box_order = '".$box_order."' WHERE entity = ".$conf->entity." AND box_order = '".$record['box_order']."'";
						$resql = $db->query($sql);
					}
					else if (preg_match("/[02468]{1}/",substr($record['box_order'],-1)))
					{
						$box_order = "B".$record['box_order'];
						$sql="UPDATE ".MAIN_DB_PREFIX."boxes SET box_order = '".$box_order."' WHERE entity = ".$conf->entity." AND box_order = '".$record['box_order']."'";
						$resql = $db->query($sql);
					}
				}
			}
		}
	}
	$db->free($resql);
}


// Available boxes to activate
$boxtoadd=InfoBox::listBoxes($db,'available',-1,null,$actives);

print "<br>\n";
print_titre($langs->trans("BoxesAvailable"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="300">'.$langs->trans("Box").'</td>';
print '<td>'.$langs->trans("Note").'/'.$langs->trans("Parameters").'</td>';
print '<td>'.$langs->trans("SourceFile").'</td>';
print '<td width="160">'.$langs->trans("ActivateOn").'</td>';
print "</tr>\n";
$var=true;
foreach($boxtoadd as $box)
{
    $var=!$var;

    if (preg_match('/^([^@]+)@([^@]+)$/i',$box->boximg))
    {
        $logo = $box->boximg;
    }
    else
    {
        $logo=preg_replace("/^object_/i","",$box->boximg);
    }

    print "\n".'<!-- Box '.$box->boxcode.' -->'."\n";
    print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<tr '.$bc[$var].'>';
    print '<td>'.img_object("",$logo).' '.$langs->transnoentitiesnoconv($box->boxlabel);
    if (! empty($box->class) && preg_match('/graph_/',$box->class)) print ' ('.$langs->trans("Graph").')';
    print '</td>';
    print '<td>';
    if ($box->note == '(WarningUsingThisBoxSlowDown)')
    {
    	$langs->load("errors");
    	print $langs->trans("WarningUsingThisBoxSlowDown");
    }
	else print ($box->note?$box->note:'&nbsp;');
    print '</td>';
    print '<td>' . $box->sourcefile . '</td>';

    // Pour chaque position possible, on affiche un lien d'activation si boite non deja active pour cette position
    print '<td>';
    print $form->selectarray("pos",$pos_name,0,0,0,0,'',1);
    print '<input type="hidden" name="action" value="add">';
    print '<input type="hidden" name="boxid" value="'.$box->box_id.'">';
    print ' <input type="submit" class="button" name="button" value="'.$langs->trans("Activate").'">';
    print '</td>';

    print '</tr>';
    print '</form>';
}

print '</table>';


// Activated boxes
$boxactivated=InfoBox::listBoxes($db,'activated',-1,null);

print "<br>\n\n";
print_titre($langs->trans("BoxesActivated"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="300">'.$langs->trans("Box").'</td>';
print '<td>'.$langs->trans("Note").'/'.$langs->trans("Parameters").'</td>';
print '<td align="center" width="160">'.$langs->trans("ActiveOn").'</td>';
print '<td align="center" width="60" colspan="2">'.$langs->trans("PositionByDefault").'</td>';
print '<td align="center" width="80">'.$langs->trans("Disable").'</td>';
print '</tr>'."\n";

$var=true;
$box_order=1;
$foundrupture=1;
foreach($boxactivated as $key => $box)
{
    $var = ! $var;

	if (preg_match('/^([^@]+)@([^@]+)$/i',$box->boximg))
	{
		$logo = $box->boximg;
	}
	else
	{
		$logo=preg_replace("/^object_/i","",$box->boximg);
	}

    print "\n".'<!-- Box '.$box->boxcode.' -->'."\n";
	print '<tr '.$bc[$var].'>';
	print '<td>'.img_object("",$logo).' '.$langs->transnoentitiesnoconv($box->boxlabel);
	if (! empty($box->class) && preg_match('/graph_/',$box->class)) print ' ('.$langs->trans("Graph").')';
	print '</td>';
	print '<td>';
	if ($box->note == '(WarningUsingThisBoxSlowDown)')
	{
		$langs->load("errors");
		print img_warning('',0).' '.$langs->trans("WarningUsingThisBoxSlowDown");
	}
	else print ($box->note?$box->note:'&nbsp;');
	print '</td>';
	print '<td align="center">' . (empty($pos_name[$box->position])?'':$langs->trans($pos_name[$box->position])) . '</td>';
	$hasnext=($key < (count($boxactivated)-1));
	$hasprevious=($key != 0);
	print '<td align="center">'.($key+1).'</td>';
	print '<td align="center">';
	print ($hasnext?'<a href="boxes.php?action=switch&switchfrom='.$box->rowid.'&switchto='.$boxactivated[$key+1]->rowid.'">'.img_down().'</a>&nbsp;':'');
	print ($hasprevious?'<a href="boxes.php?action=switch&switchfrom='.$box->rowid.'&switchto='.$boxactivated[$key-1]->rowid.'">'.img_up().'</a>':'');
	print '</td>';
	print '<td align="center">';
	print '<a href="boxes.php?rowid='.$box->rowid.'&amp;action=delete">'.img_delete().'</a>';
	print '</td>';

	print '</tr>'."\n";
}

print '</table><br>';


// Other parameters

print_titre($langs->trans("Other"));
print '<table class="noborder" width="100%">';

$var=false;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="addconst">';
print '<tr class="liste_titre">';
print '<td class="liste_titre">'.$langs->trans("Parameter").'</td>';
print '<td class="liste_titre">'.$langs->trans("Value").'</td>';
print '<td class="liste_titre"></td>';
print '</tr>';
print '<tr '.$bc[$var].'>';
print '<td>';
print $langs->trans("MaxNbOfLinesForBoxes");
print '</td>'."\n";
print '<td>';
print '<input type="text" class="flat" size="6" name="MAIN_BOXES_MAXLINES" value="'.$conf->global->MAIN_BOXES_MAXLINES.'">';
print '</td>';
print '<td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Save").'" name="Button">';
print '</td>'."\n";
print '</tr>';
print '</form>';

print '</table>';


llxFooter();

$db->close();
?>
