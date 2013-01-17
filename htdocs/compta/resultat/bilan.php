<?php
/* Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * 		\file   	htdocs/compta/resultat/bilan.php
 * 		\ingroup    compta
 * 		\brief  	Fichier page bilan compta
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id > 0) $socid = $user->societe_id;
if (! empty($conf->comptabilite->enabled)) $result=restrictedArea($user,'compta','','','resultat');
if (! empty($conf->accounting->enabled)) $result=restrictedArea($user,'accounting','','','comptarapport');


/*
 *	Views
 */

llxHeader();

$year=$_GET["year"];
$month=$_GET["month"];
if (! $year) { $year = strftime("%Y", time()); }


/* Le compte de r�sultat est un document officiel requis par l'administration selon le status ou activit� */

print_titre("Bilan".($year?" annee $year":""));

print '<br>';

print $langs->trans("FeatureNotYetAvailable");


llxFooter();

$db->close();
?>
