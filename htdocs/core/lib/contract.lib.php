<?php
/* Copyright (C) 2006-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012	Regis Houssin		<regis.houssin@capnetworks.com>
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
 * or see http://www.gnu.org/
 */

/**
 * \file       htdocs/core/lib/contract.lib.php
 * \brief      Ensemble de fonctions de base pour le module contrat
 */

/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to shoc
 */
function contract_prepare_head($object)
{
	global $langs, $conf;
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/contrat/fiche.php?id='.$object->id;
	$head[$h][1] = $langs->trans("ContractCard");
	$head[$h][2] = 'card';
	$h++;

	if (empty($conf->global->MAIN_DISABLE_CONTACTS_TAB))
	{
		$head[$h][0] = DOL_URL_ROOT.'/contrat/contact.php?id='.$object->id;
		$head[$h][1] = $langs->trans("ContactsAddresses");
		$head[$h][2] = 'contact';
		$h++;
	}

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'contract');

    if (empty($conf->global->MAIN_DISABLE_NOTES_TAB))
    {
    	$head[$h][0] = DOL_URL_ROOT.'/contrat/note.php?id='.$object->id;
    	$head[$h][1] = $langs->trans("Note");
    	$head[$h][2] = 'note';
    	$h++;
    }

	$head[$h][0] = DOL_URL_ROOT.'/contrat/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Documents");
	$head[$h][2] = 'documents';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/contrat/info.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

    complete_head_from_modules($conf,$langs,$object,$head,$h,'contract','remove');

	return $head;
}

/**
 *	Show a combo list with contracts qualified for a third party
 *
 *	@param	int		$socid      Id third party (-1=all, 0=only contracts not linked to a third party, id=contracts not linked or linked to third party id)
 *	@param  int		$selected   Id contract preselected
 *	@param  string	$htmlname   Nom de la zone html
 *	@param	int		$maxlength	Maximum length of label
 *	@return int         		Nbre of project if OK, <0 if KO
 */
function select_contrats($socid=-1, $selected='', $htmlname='contrattid', $maxlength=16)
{
	global $db,$user,$conf,$langs;

	$hideunselectables = false;
	if (! empty($conf->global->PROJECT_HIDE_UNSELECTABLES)) $hideunselectables = true;

	// Search all contacts
	$sql = 'SELECT c.rowid, c.ref, c.note, c.fk_soc, c.statut';
	$sql.= ' FROM '.MAIN_DB_PREFIX .'contrat as c';
	$sql.= " WHERE c.entity = ".$conf->entity;
	//if ($contratListId) $sql.= " AND c.rowid IN (".$contratListId.")";
	if ($socid == 0) 
		$sql.= " AND (c.fk_soc=0 OR c.fk_soc IS NULL)";
	else
		$sql.= " AND c.fk_soc=".$socid;
	$sql.= " ORDER BY c.note ASC";

	dol_syslog("contact.lib::select_contrats sql=".$sql);
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
				// If we ask to filter on a company and user has no permission to see all companies and project is linked to another company, we hide project.
				if ($socid > 0 && (empty($obj->fk_soc) || $obj->fk_soc == $socid) && ! $user->rights->societe->lire)
				{
					// Do nothing
				}
				else
				{
					$labeltoshow=dol_trunc($obj->ref,18);
					//if ($obj->public) $labeltoshow.=' ('.$langs->trans("SharedProject").')';
					//else $labeltoshow.=' ('.$langs->trans("Private").')';
					if (!empty($selected) && $selected == $obj->rowid && $obj->statut > 0)
					{
						print '<option value="'.$obj->rowid.'" selected="selected">'.$labeltoshow.'</option>';
					}
					else
					{
						$disabled=0;
						if (! $obj->statut > 0)
						{
							$disabled=1;
							$labeltoshow.=' - '.$langs->trans("Draft");
						}
						if ($socid > 0 && (! empty($obj->fk_soc) && $obj->fk_soc != $socid))
						{
							$disabled=1;
							$labeltoshow.=' - '.$langs->trans("LinkedToAnotherCompany");
						}

						if ($hideunselectables && $disabled)
						{
							$resultat='';
						}
						else
						{
							$resultat='<option value="'.$obj->rowid.'"';
							if ($disabled) $resultat.=' disabled="disabled"';
							//if ($obj->public) $labeltoshow.=' ('.$langs->trans("Public").')';
							//else $labeltoshow.=' ('.$langs->trans("Private").')';
							$resultat.='>'.$labeltoshow;
							if (! $disabled) $resultat.=' - '.dol_trunc($obj->title,$maxlength);
							$resultat.='</option>';
						}
						print $resultat;
					}
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
		dol_print_error($db);
		return -1;
	}
}

?>
