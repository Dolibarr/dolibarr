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

$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if ($page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="p.datec";

// Get supervariables
$statut = GETPOST('statut','int');
$search_ref = GETPOST('search_ref','alpha');


/*
 * Actions
 */

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter")) // All tests are required to be compatible with all browsers
{
    $search_ref="";
}


/*
 * View
 */

llxHeader('',$langs->trans("WithdrawalsReceipts"));

$bon=new BonPrelevement($db,"");

$sql = "SELECT p.rowid, p.ref, p.amount, p.statut";
$sql.= ", p.datec";
$sql.= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
$sql.= " WHERE p.entity = ".$conf->entity;
if ($search_ref) $sql.=natural_search("p.ref", $search_ref);

$sql.= $db->order($sortfield,$sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
}

$sql.= $db->plimit($limit + 1,$offset);

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows($result);
  $i = 0;

  $urladd= "&amp;statut=".$statut;

  // Lines of title fields
  print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
  if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
  print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
  print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
  print '<input type="hidden" name="action" value="list">';
  print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
  print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
  print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
  
  print_barre_liste($langs->trans("WithdrawalsReceipts"), $page, $_SERVER["PHP_SELF"], $urladd, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_generic', 0, '', '', $limit);

  $moreforfilter='';
    
  print '<div class="div-table-responsive">';
  print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

  print '<tr class="liste_titre">';
  print_liste_field_titre($langs->trans("WithdrawalsReceipts"),$_SERVER["PHP_SELF"],"p.ref",'','','class="liste_titre"');
  print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"p.datec","","",'class="liste_titre" align="center"');
  print_liste_field_titre($langs->trans("Amount"),$_SERVER["PHP_SELF"],"","","",'align="center"');
  print "</tr>\n";

  print '<tr class="liste_titre">';
  print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="search_ref" value="'. $db->escape($search_ref).'"></td>';
  print '<td class="liste_titre">&nbsp;</td>';
  print '<td class="liste_titre" align="right">';
  $searchpitco=$form->showFilterAndCheckAddButtons(0);
  print $searchpitco;
  print '</td>';
  print '</tr>';

  $var=True;

  while ($i < min($num,$limit))
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
  print '</div>';
  
  print '</form>';
  
  $db->free($result);
}
else
{
  dol_print_error($db);
}


llxFooter();

$db->close();
