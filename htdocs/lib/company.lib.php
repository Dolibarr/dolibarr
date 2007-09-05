<?php
/* Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007 Patrick Raguin <patrick.raguin@gmail.com>
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
 * or see http://www.gnu.org/
 *
 * $Id$
 * $Source$
 */

/**
   \file       htdocs/lib/company.lib.php
   \brief      Ensemble de fonctions de base pour le module societe
   \ingroup    societe
   \version    $Revision$
   
   Ensemble de fonctions de base de dolibarr sous forme d'include
*/

function societe_prepare_head($objsoc)
{
	global $langs, $conf, $user;
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/soc.php?socid='.$objsoc->id;
	$head[$h][1] = $langs->trans("Company");
	$head[$h][2] = 'company';
	$h++;

  if ($objsoc->client==1)
  {
    $head[$h][0] = DOL_URL_ROOT.'/comm/fiche.php?socid='.$objsoc->id;
    $head[$h][1] = $langs->trans("Customer");;
    $head[$h][2] = 'customer';
    $h++;
  }
  if ($objsoc->client==2)
  {
    $head[$h][0] = DOL_URL_ROOT.'/comm/prospect/fiche.php?id='.$objsoc->id;
    $head[$h][1] = $langs->trans("Prospect");
    $head[$h][2] = 'prospect';
    $h++;
  }
  if ($objsoc->fournisseur)
  {
    $head[$h][0] = DOL_URL_ROOT.'/fourn/fiche.php?socid='.$objsoc->id;
    $head[$h][1] = $langs->trans("Supplier");
    $head[$h][2] = 'supplier';
    $h++;
  }  
  if ($conf->facture->enabled || $conf->compta->enabled || $conf->comptaexpert->enabled)
  {
    $langs->load("compta");
    $head[$h][0] = DOL_URL_ROOT.'/compta/fiche.php?socid='.$objsoc->id;
    $head[$h][1] = $langs->trans("Accountancy");
    $head[$h][2] = 'compta';
    $h++;
  }
  //affichage onglet catégorie
	if ($conf->categorie->enabled)
  {
		$head[$h][0] = DOL_URL_ROOT.'/categories/categorie.php?socid='.$objsoc->id;
		$head[$h][1] = $langs->trans('Categories');
		$head[$h][2] = 'category';
		$h++;   		
  }
  if ($user->societe_id == 0)
  {
    $head[$h][0] = DOL_URL_ROOT.'/socnote.php?socid='.$objsoc->id;
    $head[$h][1] = $langs->trans("Note");
    $head[$h][2] = 'note';
    $h++;
  }
  if ($user->societe_id == 0)
  {
    $head[$h][0] = DOL_URL_ROOT.'/docsoc.php?socid='.$objsoc->id;
    $head[$h][1] = $langs->trans("Documents");
    $head[$h][2] = 'document';
    $h++;
  }
  
  if ($conf->notification->enabled && $user->societe_id == 0)
  {
    $head[$h][0] = DOL_URL_ROOT.'/societe/notify/fiche.php?socid='.$objsoc->id;
    $head[$h][1] = $langs->trans("Notifications");
    $head[$h][2] = 'notify';
    $h++;
  }

  if ($objsoc->fournisseur)
  {
    $head[$h][0] = DOL_URL_ROOT.'/fourn/fiche-stats.php?socid='.$objsoc->id;
    $head[$h][1] = $langs->trans("Statistics");
    $head[$h][2] = 'supplierstat';
    $h++;
  }
  
  if ($user->societe_id == 0)
  {	
    $head[$h][0] = DOL_URL_ROOT.'/societe/info.php?socid='.$objsoc->id;
    $head[$h][1] = $langs->trans("Info");
    $head[$h][2] = 'info';
    $h++;
  }
  
  if ($conf->bookmark->enabled && $user->rights->bookmark->creer)
  {
    $head[$h][0] = DOL_URL_ROOT."/bookmarks/fiche.php?action=add&amp;socid=".$objsoc->id."&amp;urlsource=".$_SERVER["PHP_SELF"]."?socid=".$objsoc->id;
    $head[$h][1] = img_object($langs->trans("BookmarkThisPage"),'bookmark');
    $head[$h][2] = 'image';
    $h++;
  }
  
  return $head;
}

?>
