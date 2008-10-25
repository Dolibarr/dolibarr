<?php
/* Copyright (C) 2006-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
   \file       htdocs/lib/fichinter.lib.php
   \brief      Ensemble de fonctions de base pour le module fichinter
   \ingroup    fichinter
   \version    $Revision$
   
   Ensemble de fonctions de base de dolibarr sous forme d'include
*/

function fichinter_prepare_head($fichinter)
{
  global $langs, $conf, $user;
  $langs->load("fichinter");
  
  $h = 0;
  $head = array();
  
  $head[$h][0] = DOL_URL_ROOT.'/fichinter/fiche.php?id='.$fichinter->id;
  $head[$h][1] = $langs->trans("Card");
  $head[$h][2] = 'card';
  $h++;
  
  $head[$h][0] = DOL_URL_ROOT.'/fichinter/contact.php?id='.$fichinter->id;
	$head[$h][1] = $langs->trans('InterventionContact');
	$head[$h][2] = 'contact';
	$h++;
	
	if ($conf->use_preview_tabs)
	{
		$head[$h][0] = DOL_URL_ROOT.'/fichinter/apercu.php?id='.$fichinter->id;
		$head[$h][1] = $langs->trans('Preview');
		$head[$h][2] = 'preview';
		$h++;
	}
	
	$head[$h][0] = DOL_URL_ROOT.'/fichinter/note.php?id='.$fichinter->id;
	$head[$h][1] = $langs->trans('Notes');
	$head[$h][2] = 'note';
	$h++;
	
	$head[$h][0] = DOL_URL_ROOT.'/fichinter/info.php?id='.$fichinter->id;
	$head[$h][1] = $langs->trans('Info');
	$head[$h][2] = 'info';
	$h++;
  
  return $head;
}

?>
