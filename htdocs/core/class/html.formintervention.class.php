<?php
/* Copyright (C) 2012-2013  Charles-Fr BENKE		<charles.fr@benke.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 * \file       htdocs/core/class/html.formintervention.class.php
 * \ingroup    core
 * \brief      File of class with all html predefined components
 */

/**
 *	Class to manage generation of HTML components for contract module
 */
class FormIntervention
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';


	/**
	 * Constructor
	 *
	 * @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Show a combo list with contracts qualified for a third party
	 *
	 *	@param	int		$socid      Id third party (-1=all, 0=only interventions not linked to a third party, id=intervention not linked or linked to third party id)
	 *	@param  int		$selected   Id intervention preselected
	 *	@param  string	$htmlname   Nom de la zone html
	 *	@param	int		$maxlength	Maximum length of label
	 *	@param	int		$showempty	Show empty line ('1' or string to show for empty line)
	 *	@param	int		$draftonly	Show only drafts intervention
	 *	@return int         		Nbre of project if OK, <0 if KO
	 */
	public function select_interventions($socid = -1, $selected = '', $htmlname = 'interventionid', $maxlength = 16, $showempty = 1, $draftonly = false)
	{
		// phpcs:enable
		global $user, $conf, $langs;

		$out = '';

		$hideunselectables = false;

		// Search all contacts
		$sql = "SELECT f.rowid, f.ref, f.fk_soc, f.fk_statut";
		$sql .= " FROM ".$this->db->prefix()."fichinter as f";
		$sql .= " WHERE f.entity = ".$conf->entity;
		if ($socid != '') {
			if ($socid == '0') {
				$sql .= " AND (f.fk_soc = 0 OR f.fk_soc IS NULL)";
			} else {
				$sql .= " AND f.fk_soc = ".((int) $socid);
			}
		}
		if ($draftonly) $sql .= " AND f.fk_statut = 0";

		dol_syslog(get_class($this)."::select_intervention", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$out .= '<select id="interventionid" class="flat" name="'.dol_escape_htmltag($htmlname).'">';
			if ($showempty) {
				$out .= '<option value="0">';
				if (!is_numeric($showempty)) $out .= $showempty;
				else $out .= '&nbsp;';
				$out .= '</option>';
			}
			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num) {
				while ($i < $num) {
					$obj = $this->db->fetch_object($resql);
					// If we ask to filter on a company and user has no permission to see all companies and project is linked to another company, we hide project.
					if ($socid > 0 && (empty($obj->fk_soc) || $obj->fk_soc == $socid) && empty($user->rights->societe->lire)) {
						// Do nothing
					} else {
						$labeltoshow = dol_trunc($obj->ref, 18);
						if (!empty($selected) && $selected == $obj->rowid && $obj->statut > 0) {
							$out .= '<option value="'.$obj->rowid.'" selected>'.$labeltoshow.'</option>';
						} else {
							$disabled = 0;
							if (!$obj->fk_statut > 0 && ! $draftonly) {
								$disabled = 1;
								$labeltoshow .= ' ('.$langs->trans("Draft").')';
							}
							if ($socid > 0 && (!empty($obj->fk_soc) && $obj->fk_soc != $socid)) {
								$disabled = 1;
								$labeltoshow .= ' - '.$langs->trans("LinkedToAnotherCompany");
							}

							if ($hideunselectables && $disabled) {
								$resultat = '';
							} else {
								$resultat = '<option value="'.$obj->rowid.'"';
								if ($disabled) {
									$resultat .= ' disabled';
								}
								$resultat .= '>'.$labeltoshow;
								$resultat .= '</option>';
							}
							$out .= $resultat;
						}
					}
					$i++;
				}
			}
			$out .= '</select>';
			$this->db->free($resql);
			return $out;
		} else {
			dol_print_error($this->db);
			return '';
		}
	}
}
