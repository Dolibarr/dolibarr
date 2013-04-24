<?php
/*
 * Copyright (C) 2013   Florian Henry      <florian.henry@open-concept.pro>
* Copyright (C) 2012-2013	Charles-Fr BENKE		<charles.fr@benke.fr>
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
* along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

/**
 *      \file      core/class/html.formcontract.class.php
 *      \brief      Fichier de la classe des fonctions predefinie de composants html cron
 */


/**
 *      Class to manage building of HTML components
*/
class FormContract extends Form
{
	var $db;
	var $error;

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
		return 1;
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
		global $user,$conf,$langs;

		$hideunselectables = false;
		if (! empty($conf->global->PROJECT_HIDE_UNSELECTABLES)) $hideunselectables = true;

		// Search all contacts
		$sql = 'SELECT c.rowid, c.ref, c.fk_soc, c.statut, c.note_private';
		$sql.= ' FROM '.MAIN_DB_PREFIX .'contrat as c';
		$sql.= " WHERE c.entity = ".$conf->entity;
		if ($socid == 0)
			$sql.= " AND (c.fk_soc=0 OR c.fk_soc IS NULL)";
		else
			$sql.= " AND c.fk_soc=".$socid;
		$sql.= " ORDER BY c.note_private ASC";

		dol_syslog(get_class($this).'::select_contrats sql='.$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			print '<select class="flat" name="'.$htmlname.'">';
			print '<option value="0">&nbsp;</option>';
			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num)
			{
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($resql);
					// If we ask to filter on a company and user has no permission to see all companies and project is linked to another company, we hide project.
					if ($socid > 0 && (empty($obj->fk_soc) || $obj->fk_soc == $socid) && ! $user->rights->societe->lire)
					{
						// Do nothing
					}
					else
					{
						$labeltoshow=dol_trunc($obj->ref,18);
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
			$this->db->free($resql);
			return $num;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}
}
