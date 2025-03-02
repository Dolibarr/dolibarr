<?php
/* Copyright (C) 2017      Open-DSI             <support@open-dsi.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/admin/openid_connect.php
 * 	\ingroup	openid_connect
 *	\brief      Functions for the module openid_connect
 */

/**
 * Prepare array with list of tabs
 *
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function openid_connect_prepare_head()
{
	global $langs, $conf, $user;
	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/admin/openid_connect.php", 1);
	$head[$h][1] = $langs->trans("Parameters");
	$head[$h][2] = 'settings';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'openid_connect_admin');

	return $head;
}


/**
 * return the current state
 *
 * @return  string				String containing the state
 */
function openid_connect_get_state()
{
	return hash('sha256', session_id());
}


/**
 * return the redirect url
 *
 * @return  string				Redirect url
 */
function openid_connect_get_redirect_url()
{
	return DOL_MAIN_URL_ROOT . '/core/modules/openid_connect/callback.php';
}


/**
 * Return authentication url
 *
 * @return  string				Authentication url
 */
function openid_connect_get_url()
{
	return getDolGlobalString('MAIN_AUTHENTICATION_OIDC_AUTHORIZE_URL') . '?client_id=' . getDolGlobalString('MAIN_AUTHENTICATION_OIDC_CLIENT_ID') . '&redirect_uri=' . openid_connect_get_redirect_url() . '&scope=' . getDolGlobalString('MAIN_AUTHENTICATION_OIDC_SCOPES') . '&response_type=code&state=' . openid_connect_get_state();
}
