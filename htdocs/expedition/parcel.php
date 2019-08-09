<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin         <regis.houssin@inodbox.com>
 * Copyright (C) 2013      Cédric Salvador       <csalvador@gpcsolutions.fr>
 * Copyright (C) 2017      Ferran Marcet       	 <fmarcet@2byte.es>
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
 *	\file       htdocs/expedition/parcel.php
 *	\ingroup    expedition
 *	\brief      Parcel management page of an expedition.
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/sendings.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/expedition/modules_expedition.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'other'));

// Check GET variables
$action		= GETPOST('action', 'aZ09');
$confirm	= GETPOST('confirm');
$id			= GETPOST('id', 'int');
$ref		= GETPOST('ref');

// Security check
if ($user->societe_id)
{
	$socid = $user->societe_id;
}
$result=restrictedArea($user, 'expedition', $id, '');

$object = new Expedition($db);
if ($id > 0 || ! empty($ref))
{
    $object->fetch($id, $ref);   
}

/*
 * Actions
 */

//CRUD

/*
 * View
 */
llxHeader('', $langs->trans('Order'), 'EN:Customers_Orders|FR:expeditions_Clients|ES:Pedidos de clientes');

// End of page
llxFooter();
$db->close();
