<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * $Source$
 *
 */

/*!
        \file       htdocs/compta/index.php
        \ingroup    compta
		\brief      Page accueil zone comptabilité
		\version    $Revision$
*/

require("./pre.inc.php");

llxHeader();

print "Export Comptable";

if ($_GET["action"] == 'export')
{
  include_once DOL_DOCUMENT_ROOT.'/compta/export/modules/compta.export.class.php';

  $exc = new ComptaExport($db, $user, 'Poivre');
  $exc->Export();
}

print '<a href="index.php?action=export">Export</a>';

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
