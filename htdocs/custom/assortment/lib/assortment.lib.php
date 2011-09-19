<?php
/* Copyright (C) 2011 Florian HENRY  <florian.henry.mail@gmail.com>
 *
 * Code of this page is mostly inspired from module category
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
 *  \file       htdocs/assortment/lib/assortment.lib.php
 *  \ingroup    crm
 *  \brief      Assortment Library pages
 *  \version    $Id: assortment.php,v 1.0 2011/01/01 eldy Exp $
 */
require_once(DOL_DOCUMENT_ROOT_ALT."/assortment/class/html.formassortment.class.php"); 
 
/**
 *		\brief		Construct the form to manage assortment
 *		\param      db     		database identifier
 *		\param      object     	object identifier
 *		\param      typeid     	1=manage assortment for third party, 2 or 3 manage assortment from product
 */	
function ManageAssortment($db,$object,$typeid)
{
	$htmlassort = new FormAssortment($db);
	$htmlassort->form_manage_assortment($db,$object,$typeid);
}

/**
 *		\brief		Construct the display to manage assortment
 *		\param      object     	object identifier
 *		\param      type     	1=manage assortment for third party, 2 or 3 manage assortment from product
 */	
function DisplayAssortment($db,$objectid,$type)
{
	$htmlassort = new FormAssortment($db);
	$htmlassort->list_assortment($objectid,$type);
}

?>
