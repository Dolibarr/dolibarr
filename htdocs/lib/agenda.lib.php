<?php
/* Copyright (C) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 */

/**
   \file		htdocs/lib/agenda.lib.php
   \brief		Ensemble de fonctions de base de dolibarr sous forme d'include
   \version		$Id$
*/


/**
   \brief      	Show actions to do array
   \param		max		Max nb of records
*/
function show_array_actions_to_do($max=5)
{
	global $langs, $conf, $user, $db, $bc, $socid;
	
	include_once(DOL_DOCUMENT_ROOT.'/actioncomm.class.php');
	include_once(DOL_DOCUMENT_ROOT.'/client.class.php');

	$sql = "SELECT a.id, a.label, ".$db->pdate("a.datep")." as dp, a.fk_user_author, a.percent,";
	$sql.= " c.code, c.libelle,";
	$sql.= " s.nom as sname, s.rowid, s.client";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
	$sql.= " FROM ".MAIN_DB_PREFIX."actioncomm as a, ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE c.id=a.fk_action AND a.percent < 100 AND s.rowid = a.fk_soc";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid)
	{
	    $sql .= " AND s.rowid = ".$socid;
	}
	$sql .= " ORDER BY a.datep DESC, a.id DESC";
	$sql .= $db->plimit($max, 0);

	$resql=$db->query($sql);
	if ($resql)
	{
	    $num = $db->num_rows($resql);
	    if ($num > 0)
	    {
	        print '<table class="noborder" width="100%">';
	        print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("LastActionsToDo",$max).'</td>';
			print '<td colspan="2" align="right"><a href="'.DOL_URL_ROOT.'/comm/action/listactions.php?status=todo">'.$langs->trans("FullList").'</a>';
			print '</tr>';
	        $var = true;
	        $i = 0;

		    $staticaction=new ActionComm($db);
	        $customerstatic=new Client($db);

	        while ($i < $num)
	        {
	            $obj = $db->fetch_object($resql);
	            $var=!$var;

	            print "<tr $bc[$var]>";

	            $staticaction->code=$obj->code;
	            $staticaction->libelle=$obj->libelle;
	            $staticaction->id=$obj->id;
	            print '<td>'.$staticaction->getNomUrl(1,12).'</td>';

	            print '<td>'.dolibarr_trunc($obj->label,24).'</td>';

	            $customerstatic->id=$obj->rowid;
	            $customerstatic->nom=$obj->sname;
	            $customerstatic->client=$obj->client;
	            print '<td>'.$customerstatic->getNomUrl(1,'',16).'</td>';

				// Date
				print '<td width="100" alig="right">'.dolibarr_print_date($obj->dp,'day').'&nbsp;';
				$late=0;
				if ($obj->percent == 0 && $obj->dp && date("U",$obj->dp) < time()) $late=1;
				if ($obj->percent == 0 && ! $obj->dp && $obj->dp2 && date("U",$obj->dp) < time()) $late=1;
				if ($obj->percent > 0 && $obj->percent < 100 && $obj->dp2 && date("U",$obj->dp2) < time()) $late=1;
				if ($obj->percent > 0 && $obj->percent < 100 && ! $obj->dp2 && $obj->dp && date("U",$obj->dp) < time()) $late=1;
				if ($late) print img_warning($langs->trans("Late"));
				print "</td>";	

				// Statut
				print "<td align=\"center\" width=\"14\">".$staticaction->LibStatut($obj->percent,3)."</td>\n";

				print "</tr>\n";
				
	            $i++;
	        }
	        print "</table><br>";
	    }
	    $db->free($resql);
	}
	else
	{
	    dolibarr_print_error($db);
	}
}


/**
   \brief      	Show last actions array
   \param		max		Max nb of records
*/
function show_array_last_actions_done($max=5)
{
	global $langs, $conf, $user, $db, $bc, $socid;
	
	$sql = "SELECT a.id, a.percent, ".$db->pdate("a.datep")." as da, ".$db->pdate("a.datep2")." as da2, a.fk_user_author, a.label,";
	$sql.= " c.code, c.libelle,";
	$sql.= " s.rowid, s.nom as sname, s.client";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
	$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a, ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql .= " WHERE c.id = a.fk_action AND a.percent >= 100 AND s.rowid = a.fk_soc";
	if ($socid)
	{
		$sql .= " AND s.rowid = ".$socid;
	}
	if (!$user->rights->societe->client->voir && !$socid) //restriction
	{
		$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	}
	$sql .= " ORDER BY a.datep2 DESC";
	$sql .= $db->plimit($max, 0);

	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("LastDoneTasks",$max).'</td>';
		print '<td colspan="2" align="right"><a href="'.DOL_URL_ROOT.'/comm/action/listactions.php?status=done">'.$langs->trans("FullList").'</a>';
		print '</tr>';
		$var = true;
		$i = 0;

	    $staticaction=new ActionComm($db);
	    $customerstatic=new Societe($db);

		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);
			$var=!$var;

			print "<tr $bc[$var]>";
			
			$staticaction->code=$obj->code;
			$staticaction->libelle=$obj->libelle;
			$staticaction->id=$obj->id;
			print '<td>'.$staticaction->getNomUrl(1,12).'</td>';

            print '<td>'.dolibarr_trunc($obj->label,24).'</td>';

			$customerstatic->id=$obj->rowid;
			$customerstatic->nom=$obj->sname;
			$customerstatic->client=$obj->client;
			print '<td>'.$customerstatic->getNomUrl(1,'',24).'</td>';

			// Date
			print '<td width="100" align="right">'.dolibarr_print_date($obj->da2,'day');
			print "</td>";	

			// Statut
			print "<td align=\"center\" width=\"14\">".$staticaction->LibStatut($obj->percent,3)."</td>\n";

			print "</tr>\n";
			$i++;
		}
		// TODO Ajouter rappel pour "il y a des contrats à mettre en service"
		// TODO Ajouter rappel pour "il y a des contrats qui arrivent à expiration"
		print "</table><br>";

		$db->free($resql);
	}
	else
	{
		dolibarr_print_error($db);
	}
}


/**
   \brief      	Define head array for tabs of agenda setup pages
   \return		Array of head
   \version    	$Id$
*/
function agenda_prepare_head()
{
	global $langs, $conf, $user;
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/admin/agenda.php";
	$head[$h][1] = $langs->trans("AutoActions");
	$head[$h][2] = 'autoactions';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/admin/agenda_xcal.php";
	$head[$h][1] = $langs->trans("Other");
	$head[$h][2] = 'xcal';
	$h++;

	return $head;
}

?>