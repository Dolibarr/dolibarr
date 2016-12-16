<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier <jeremie.o@laposte.net>
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
 *	\file       htdocs/cashdesk/validation_ticket.php
 *	\ingroup    cashdesk
 *	\brief      validation_ticket.php
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/cashdesk/include/environnement.php';
require_once DOL_DOCUMENT_ROOT.'/cashdesk/class/Facturation.class.php';
include_once(DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php');

$obj_facturation = unserialize($_SESSION['serObjFacturation']);
unset($_SESSION['serObjFacturation']);

$hookmanager->initHooks(array('cashdeskTplTicket'));

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$obj_facturation);
if (empty($reshook))
{
    require ('tpl/ticket.tpl.php');
}


$_SESSION['serObjFacturation'] = serialize($obj_facturation);

