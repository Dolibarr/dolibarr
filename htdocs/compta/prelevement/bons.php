<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
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

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

// Load translation files required by the page
$langs->loadLangs(array('banks', 'categories', 'widthdrawals'));

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'prelevement','','','bons');

$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="p.datec";

// Get supervariables
$statut = GETPOST('statut','int');
$search_ref = GETPOST('search_ref','alpha');
$search_amount = GETPOST('search_amount','alpha');

$bon=new BonPrelevement($db,"");


/*
 * Actions
 */

if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
{
    $search_ref="";
    $search_amount="";
}


/*
 * View
 */

llxHeader('',$langs->trans("WithdrawalsReceipts"));

$sql = "SELECT p.rowid, p.ref, p.amount, p.statut, p.datec";
$sql.= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
$sql.= " WHERE p.entity IN (".getEntity('facture').")";
if ($search_ref) $sql.=natural_search("p.ref", $search_ref);
if ($search_amount) $sql.=natural_search("p.amount", $search_amount, 1);

$sql.= $db->order($sortfield,$sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
    if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
    {
    	$page = 0;
    	$offset = 0;
    }
}

$sql.= $db->plimit($limit + 1,$offset);

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows($result);
  $i = 0;

  $urladd= "&amp;statut=".$statut;

  $selectedfields='';

  $newcardbutton='';
  if ($user->rights->prelevement->bons->creer)
  {
  	$newcardbutton = '<a class="butActionNew" href="'.DOL_URL_ROOT.'/compta/prelevement/create.php"><span class="valignmiddle">'.$langs->trans('NewStandingOrder').'</span>';
  	$newcardbutton.= '<span class="fa fa-plus-circle valignmiddle"></span>';
  	$newcardbutton.= '</a>';
  }

  // Lines of title fields
  print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
  if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
  print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
  print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
  print '<input type="hidden" name="action" value="list">';
  print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
  print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
  print '<input type="hidden" name="page" value="'.$page.'">';
  print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

  print_barre_liste($langs->trans("WithdrawalsReceipts"), $page, $_SERVER["PHP_SELF"], $urladd, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_generic', 0, $newcardbutton, '', $limit);

  $moreforfilter='';

  print '<div class="div-table-responsive">';
  print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

  print '<tr class="liste_titre">';
  print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="search_ref" value="'. dol_escape_htmltag($search_ref).'"></td>';
  print '<td class="liste_titre">&nbsp;</td>';
  print '<td class="liste_titre right"><input type="text" class="flat maxwidth100" name="search_amount" value="'. dol_escape_htmltag($search_amount).'"></td>';
  print '<td class="liste_titre">&nbsp;</td>';
  print '<td class="liste_titre" align="right">';
  $searchpicto=$form->showFilterButtons();
  print $searchpicto;
  print '</td>';
  print '</tr>';

  print '<tr class="liste_titre">';
  print_liste_field_titre("WithdrawalsReceipts",$_SERVER["PHP_SELF"],"p.ref",'','','class="liste_titre"',$sortfield,$sortorder);
  print_liste_field_titre("Date",$_SERVER["PHP_SELF"],"p.datec","","",'class="liste_titre" align="center"',$sortfield,$sortorder);
  print_liste_field_titre("Amount",$_SERVER["PHP_SELF"],"p.amount","","",'align="right"',$sortfield,$sortorder);
  print_liste_field_titre("Status",$_SERVER["PHP_SELF"],"","","",'align="right"',$sortfield,$sortorder);
  print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"],"",'','','align="center"',$sortfield,$sortorder,'maxwidthsearch ')."\n";
  print "</tr>\n";

  while ($i < min($num,$limit))
  {
      $obj = $db->fetch_object($result);

      print '<tr class="oddeven"><td>';

      print '<a href="card.php?id='.$obj->rowid.'">'.$obj->ref."</a></td>\n";

      print '<td align="center">'.dol_print_date($db->jdate($obj->datec),'day')."</td>\n";

      print '<td align="right">'.price($obj->amount)."</td>\n";

      print '<td align="right">';
      print $bon->LibStatut($obj->statut, 3);
      print '</td>';

      print '<td align="right"></td>'."\n";

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

// End of page
llxFooter();
$db->close();
