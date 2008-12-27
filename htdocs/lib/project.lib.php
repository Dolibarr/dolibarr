<?php
/* Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/lib/project.lib.php
		\brief      Ensemble de fonctions de base pour le module projet
        \ingroup    societe
        \version    $Id$
*/

function project_prepare_head($objsoc)
{
	global $langs, $conf, $user;
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/projet/fiche.php?id='.$objsoc->id;
	$head[$h][1] = $langs->trans("Project");
    $head[$h][2] = 'project';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/fiche.php?id='.$objsoc->id;
	$head[$h][1] = $langs->trans("Tasks");
    $head[$h][2] = 'tasks';
	$h++;

	if ($conf->propal->enabled || $conf->commande->enabled || $conf->facture->enabled)
	{
		$head[$h][0] = DOL_URL_ROOT.'/projet/element.php?id='.$objsoc->id;
		$head[$h][1] = $langs->trans("Referers");
	    $head[$h][2] = 'element';
		$h++;
	}

	return $head;
}


/**
		\brief      Affiche la liste d�roulante des projets d'une soci�t� donn�e
		\param      socid       Id soci�t�
		\param      selected    Id projet pr�-s�lectionn�
		\param      htmlname    Nom de la zone html
		\return     int         Nbre de projet si ok, <0 si ko
*/
function select_projects($socid, $selected='', $htmlname='projectid')
{
	global $db;

	// On recherche les projets
	$sql = 'SELECT p.rowid, p.title FROM ';
	$sql.= MAIN_DB_PREFIX .'projet as p';
	$sql.= " WHERE (fk_soc='".$socid."' or fk_soc IS NULL)";
	$sql.= " ORDER BY p.title ASC";

	dolibarr_syslog("project.lib::select_projects sql=".$sql);
	$resql=$db->query($sql);
	if ($resql)
	{
		print '<select class="flat" name="'.$htmlname.'">';
		print '<option value="0">&nbsp;</option>';
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num)
		{
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				if (!empty($selected) && $selected == $obj->rowid)
				{
					print '<option value="'.$obj->rowid.'" selected="true">'.$obj->title.'</option>';
				}
				else
				{
					print '<option value="'.$obj->rowid.'">'.$obj->title.'</option>';
				}
				$i++;
			}
		}
		print '</select>';
		$db->free($resql);
		return $num;
	}
	else
	{
		dolibarr_print_error($this->db);
		return -1;
	}
}
  
?>