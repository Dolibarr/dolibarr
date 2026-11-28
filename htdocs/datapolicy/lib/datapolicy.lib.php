<?php
/* Copyright (C) 2018       Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/datapolicy/lib/datapolicy.lib.php
 * \ingroup datapolicy
 * \brief   Library files with common functions for datapolicy
 */

/**
 * Prepare admin pages header
 *
 * @return array<array{0:string,1:string,2:string}>
 */
function datapolicyAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("datapolicy");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/datapolicy/admin/setup.php";

	if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
		$head[$h][1] = $langs->trans("DataAnonymizationOrDeletion");
	} else {
		$head[$h][1] = $langs->trans("DataAnonymization");
	}
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/datapolicy/admin/dpo.php";
	$head[$h][1] = $langs->trans("DPO");
	$head[$h][2] = 'dpo';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'datapolicy');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'datapolicy', 'remove');

	return $head;
}

/**
 * Return the active DPO record for the current entity (or null if none).
 *
 * @param  DoliDB        $db Database handler
 * @return stdClass|null     Active DPO row or null
 */
function datapolicyGetActiveDpo(DoliDB $db)
{
	global $conf;

	$today = $db->idate(dol_now(), 'date');
	$sql = "SELECT rowid, dpo_type, fk_user, fk_soc, fk_socpeople, lastname, firstname, job, email, phone, contact_form_url, gdpr_prerequisites_validated, gdpr_skills_certificate_file, gdpr_authority_declaration_file, date_start, date_end"
		." FROM ".$db->prefix()."datapolicy_dpo"
		." WHERE entity = ".((int) $conf->entity)
		." AND date_start <= '".$today."'"
		." AND (date_end IS NULL OR date_end >= '".$today."')"
		." ORDER BY date_start DESC, rowid DESC LIMIT 1";
	$res = $db->query($sql);
	if ($res && $db->num_rows($res) > 0) {
		return $db->fetch_object($res);
	}

	return null;
}
