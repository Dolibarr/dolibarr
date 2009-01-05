<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */
 
/**
       	\file       htdocs/public/adherents/index.php
		\ingroup    member
		\brief      Fichier de la page de l'espace publique adherent
		\author	    Laurent Destailleur
		\version    $Id$
*/

require("./pre.inc.php");



/*
 * View
 */
 
llxHeader();

print_fiche_titre($langs->trans("PublicMembersArea"));


print '<table border="0" width="100%" class="notopnoleftnoright">';

print '<tr><td valign="top" width="100%" class="notopnoleft">';

print $langs->trans('FollowingLinksArePublic').'<br>';
print '<br>';

print '<table class="border" cellspacing="0" cellpadding="3">';
print '<tr class="liste_titre"><td>'.$langs->trans("Description").'</td><td>'.$langs->trans("URL").'</td></tr>';
print '<tr><td>'.$langs->trans("BlankSubscriptionForm").'</td><td><a target="_blank" href="'.DOL_URL_ROOT.'/public/adherents/new.php'.'">'.$dolibarr_main_url_root.DOL_URL_ROOT.'/public/adherents/new.php'.'</a></td></tr>';
print '<tr><td>'.$langs->trans("PublicMemberList").'</td><td><a target="_blank" href="'.DOL_URL_ROOT.'/public/adherents/public_list.php'.'">'.$dolibarr_main_url_root.DOL_URL_ROOT.'/public/adherents/public_list.php'.'</a></td></tr>';
// Should work with DOL_URL_ROOT='' or DOL_URL_ROOT='/dolibarr'
$firstpart=$dolibarr_main_url_root;
$regex=DOL_URL_ROOT.'$';
$firstpart=eregi_replace($regex,'',$firstpart);
print '<tr><td>'.$langs->trans("PublicMemberCard").'</td><td>'.$firstpart.DOL_URL_ROOT.'/public/adherents/public_card.php?id=xxx'.'</td></tr>';
print '</table>';


print '</td></tr></table>';


$db->close();

llxFooter('$Date$ - $Revision$');
?>
