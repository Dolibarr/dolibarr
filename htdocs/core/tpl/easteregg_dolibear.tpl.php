<?php
/* Copyright (C) 2026		iProspective			<contact@iprospective.fr>
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
 */

/**
 * Load the DoliBear easter egg when it is searched.
 * Include it just before llxFooter() of a list page.
 *
 * @var ?string	$search_all		Value of the global search field
 * @var ?string	$search_nom		Value of the name search field
 */

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit(1);
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/easteregg.lib.php';

if (dolibearIsTriggered(array($search_all ?? '', $search_nom ?? ''))) {
	print '<link rel="stylesheet" href="'.DOL_URL_ROOT.'/theme/common/dolibear/dolibear.css?v='.urlencode(DOL_VERSION).'">'."\n";
	print '<script src="'.DOL_URL_ROOT.'/core/js/dolibear.js?v='.urlencode(DOL_VERSION).'" data-dolibear-autostart="1"></script>'."\n";
}
