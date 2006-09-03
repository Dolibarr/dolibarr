<?PHP
/* Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
        \file       htdocs/boutique/master.inc.php
        \brief      Fichier de preparation de l'environnement Dolibarr pour OSCommerce
        \version    $Revision$
*/

require_once(DOL_DOCUMENT_ROOT ."/lib/databases/".$conf->db->type.".lib.php");


/*
 * Creation objet $dbosc
 */
$dbosc = new DoliDb($conf->db->type,$conf->global->OSC_DB_HOST,$conf->global->OSC_DB_USER,$conf->global->OSC_DB_PASS,$conf->global->OSC_DB_NAME);
if (! $dbosc->connected)
{
    dolibarr_print_error($dbosc,"host=".$conf->global->OSC_DB_HOST.", user=".$conf->global->OSC_DB_USER.", databasename=".$conf->global->OSC_DB_NAME.", ".$db->error);
    exit;   
}

?>
