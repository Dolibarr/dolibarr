<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *	\file       scripts/emailing/mailing-prepare.php
 *	\ingroup    mailing
 *	\brief      Script pour preparer les destinataires d'un mailing
 *	\version	$Id$
 */

require_once("../../htdocs/master.inc.php");

$error = 0;

$sql = "SELECT m.rowid, m.cible";
$sql .= " FROM ".MAIN_DB_PREFIX."mailing as m";
$sql .= " WHERE m.statut in (0,1)";

if ( $db->query($sql) )
{
	$num = $db->num_rows();
	$i = 0;

	while ($i < $num)
	{
		$row = $db->fetch_row();

		dol_syslog("mailing-prepare: mailing $row[0]");
		dol_syslog("mailing-prepare: mailing module $row[1]");

		require_once(DOL_DOCUMENT_ROOT.'/includes/modules/mailings/'.$row[1].'.modules.php');

		$classname = "mailing_".$row[1];

		$obj = new $classname($db);
		$obj->add_to_target($row[0]);

		$i++;

	}
}

?>
