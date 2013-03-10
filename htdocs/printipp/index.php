<?php
/*
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
 *	\file       htdocs/printipp/index.php
 *	\ingroup    printipp
 *	\brief      Printipp
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolprintipp.class.php';

llxHeader("",$langs->trans("Printer"));

print_fiche_titre($langs->trans("Printer"));

$printer = new dolPrintIPP($db,$conf->global->PRINTIPP_HOST,$conf->global->PRINTIPP_PORT,$user->login,$conf->global->PRINTIPP_USER,$conf->global->PRINTIPP_PASSWORD);
$printer->list_jobs('commande');

llxFooter();

$db->close();
?>
