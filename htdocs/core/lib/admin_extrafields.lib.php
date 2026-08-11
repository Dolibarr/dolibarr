<?php
/* Copyright (C) 2026  Frédéric France  <frederic.france@free.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file		htdocs/core/lib/admin_extrafields.lib.php
 *	\brief		Whitelist of elementtype values accepted by the unified
 *				extrafields admin page (htdocs/admin/extrafields.php).
 *
 *	This is a closed registry: only elementtype values explicitly listed
 *	here can ever be processed by the unified extrafields admin page.
 *	Do not make this dynamic or pattern-based — the whole point is that
 *	an attacker-controlled string can never reach ExtraFields::addExtraField(),
 *	ExtraFields::update()/delete(), or the raw SQL built in
 *	core/actions_extrafields.inc.php, without having first matched one of
 *	these hardcoded keys.
 */

/**
 * Return the whitelist of elementtype values accepted by
 * htdocs/admin/extrafields.php, and the page metadata needed to render
 * each one (which tab-bar function to call, which lang files to load, ...).
 *
 * @return array<string,array{headfunction:string,headfile:string,tabid:string,headlabel:string,headpicto:string,title:string|callable,helpurl:string,langs:string[]}>
 */
function getExtrafieldsAdminMap()
{
	return array(
		'societe' => array(
			'headfunction' => 'societe_admin_prepare_head',
			'headfile'     => 'core/lib/company.lib.php',
			'tabid'        => 'attributes',
			'headlabel'    => 'ThirdParty',
			'headpicto'    => 'company',
			'title'        => 'CompanySetup',
			'helpurl'      => 'EN:Module Third Parties setup|FR:Paramétrage_du_module_Tiers',
			'langs'        => array('companies', 'admin', 'members'),
		),
		'socpeople' => array(
			'headfunction' => 'societe_admin_prepare_head',
			'headfile'     => 'core/lib/company.lib.php',
			'tabid'        => 'attributes_contacts',
			'headlabel'    => 'ContactsAddresses',
			'headpicto'    => 'company',
			'title'        => 'CompanySetup',
			'helpurl'      => 'EN:Module Third Parties setup|FR:Paramétrage_du_module_Tiers',
			'langs'        => array('companies', 'admin'),
		),
	);
}
