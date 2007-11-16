<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Éric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 */

/**
        \file       htdocs/fourn/fiche.php
        \ingroup    fournisseur, facture
        \brief      Page de fiche fournisseur
        \version    $Revision$
*/

require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");

$user->getrights();

$langs->load('suppliers');
$langs->load('products');
$langs->load('bills');
$langs->load('orders');
$langs->load('companies');
$langs->load('commercial');

// Sécurité accés client
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}
if (! $socid) accessforbidden();



/*
 *  Actions
 */
 
// Protection restriction commercial
if (!$user->rights->commercial->client->voir && $socid && !$user->societe_id > 0)
{
  $sql = "SELECT sc.rowid";
  $sql .= " FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc, ".MAIN_DB_PREFIX."societe as s";
  $sql .= " WHERE sc.fk_soc = ".$socid." AND sc.fk_soc = s.rowid AND sc.fk_user = ".$user->id." AND s.fournisseur = 1";
  
  if ( $db->query($sql) )
    {
      if ( $db->num_rows() == 0) accessforbidden();
    }
}


/*
 * Mode fiche
 */  
$societe = new Fournisseur($db);

if ( $societe->fetch($socid) )
{
  $addons[0][0] = DOL_URL_ROOT.'/fourn/fiche.php?socid='.$socid;
  $addons[0][1] = $societe->nom;

  llxHeader('',$langs->trans('SupplierCard').' : '.$societe->nom, $addons);

  /*
   * Affichage onglets
   */
  $head = societe_prepare_head($societe);

  dolibarr_fiche_head($head, 'supplierstat', $societe->nom);

  print '<table class="border" width="100%">';
  print '<tr><td width="20%">'.$langs->trans("Name").'</td><td width="80%" colspan="3">'.$societe->nom.'</td></tr>';

  print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$societe->prefix_comm.'</td></tr>';

  print '<tr><td nowrap="nowrap">';
  print $langs->trans('SupplierCode').'</td><td colspan="3">';
  print $societe->code_fournisseur;
  if ($societe->check_codefournisseur() <> 0) print ' '.$langs->trans("WrongSupplierCode");
  print '</td></tr>';
  
  print "</table><br />";

  print '<table class="border" width="100%">';
  print '<tr><td valign="top" width="50%">';

  $file = get_exdir($societe->id, 3) . "ca_genere-".$societe->id.".png";

  $url=DOL_URL_ROOT.'/viewimage.php?modulepart=graph_fourn&amp;file='.$file;
  print '<img src="'.$url.'" alt="CA genere">';

  print '</td><td valign="top" width="50%">';

  $file = get_exdir($societe->id, 3) . "ca_achat-".$societe->id.".png";

  $url=DOL_URL_ROOT.'/viewimage.php?modulepart=graph_fourn&amp;file='.$file;
  print '<img src="'.$url.'" alt="CA">';

  print '</td></tr>';
  print '</table>' . "\n";
  print '</div>';
}
else
{
  dolibarr_print_error($db);
}
$db->close();

llxFooter('$Date$ - $Revision$');
?>
