<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2012 Juanjo Menent        <jmenent@2byte.es>
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
 * 	\file       htdocs/compta/prelevement/bons.php
 * 	\ingroup    prelevement
 * 	\brief      Page liste des bons de prelevements
 */

require('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$langs->load("banks");
$langs->load("categories");
$langs->load("widthdrawals");

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'prelevement','','','bons');

// Get supervariables
$page = GETPOST('page','int');
$sortorder = ((GETPOST('sortorder','alpha')=="")) ? "DESC" : GETPOST('sortorder','alpha');
$sortfield = ((GETPOST('sortfield','alpha')=="")) ? "p.datec" : GETPOST('sortfield','alpha');
$statut = GETPOST('statut','int');
$search_line = GETPOST('search_ligne','int');

llxHeader('',$langs->trans("WithdrawalsReceipts"));

$bon=new BonPrelevement($db,"");

if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;


/*
 * Mode List
 *
 */
$sql = "SELECT p.rowid, p.ref, p.amount, p.statut";
$sql.= ", p.datec";
$sql.= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
$sql.= " WHERE p.entity = ".$conf->entity;
$sql.= " ORDER BY $sortfield $sortorder ";
$sql.= $db->plimit($conf->liste_limit+1, $offset);

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows($result);
  $i = 0;

  $urladd= "&amp;statut=".$statut;

  print_barre_liste($langs->trans("WithdrawalsReceipts"), $page, $_SERVER["PHP_SELF"], $urladd, $sortfield, $sortorder, '', $num);

  print '<table class="liste" width="100%">';

  print '<tr class="liste_titre">';
  print_liste_field_titre($langs->trans("WithdrawalsReceipts"),$_SERVER["PHP_SELF"],"p.ref",'','','class="liste_titre"');
  print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"p.datec","","",'class="liste_titre" align="center"');
  print_liste_field_titre($langs->trans("Amount"),$_SERVER["PHP_SELF"],"","","",'align="center"');
  print "</tr>\n";

  print '<tr class="liste_titre">';
  print '<form action="'.$_SERVER["PHP_SELF"].'" method="GET">';
  print '<td class="liste_titre"><input type="text" class="flat" name="search_ligne" value="'. $search_line.'" size="10"></td>';
  print '<td class="liste_titre">&nbsp;</td>';
  print '<td class="liste_titre" align="right"><input type="image" class="liste_titre" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" name="button_search" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'"></td>';
  print '</form>';
  print '</tr>';

  $var=True;

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object($result);
      $var=!$var;

      print "<tr ".$bc[$var]."><td>";

      print $bon->LibStatut($obj->statut,2);
      print "&nbsp;";

      print '<a href="card.php?id='.$obj->rowid.'">'.$obj->ref."</a></td>\n";

      print '<td align="center">'.dol_print_date($db->jdate($obj->datec),'day')."</td>\n";

      print '<td align="right">'.price($obj->amount)."</td>\n";

      print "</tr>\n";
      $i++;
    }
  print "</table>";
  $db->free($result);
}
else
{
  dol_print_error($db);
}


llxFooter();

$db->close();
