<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 */

/**
 *	\file			htdocs/lib/accountancy.lib.php
 *  \brief			Library of accountancy functions
 * 	\version		$Id$
 */


function get_ca_propal ($db, $year, $socid)
{

	$sql = "SELECT sum(f.price - f.remise) as sum FROM ".MAIN_DB_PREFIX."propal as f WHERE fk_statut in (1,2,4) AND date_format(f.datep, '%Y') = $year ";
	if ($socid)
	{
		$sql .= " AND f.fk_soc = $socid";
	}

	$result = $db->query($sql);

	if ($result)
	{
		$res = $db->fetch_object($result);
		return  $res->sum;
	}
	else
	{
		return 0;
	}

}

function get_ca ($db, $year, $socid)
{
	global $conf;
	
	$sql = "SELECT sum(f.amount) as sum FROM ".MAIN_DB_PREFIX."facture as f";
	$sql .= " WHERE f.fk_statut in (1,2)";
	if ($conf->compta->mode != 'CREANCES-DETTES') {
		$sql .= " AND f.paye = 1";
	}
	$sql .= " AND date_format(f.datef , '%Y') = $year ";
	if ($socid)
	{
		$sql .= " AND f.fk_soc = $socid";
	}

	$result = $db->query($sql);

	if ($result)
	{
		$res = $db->fetch_object($result);
		return  $res->sum;
	}
	else
	{
		return 0;
	}
}
