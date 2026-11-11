<?php
/* Copyright (C) 2002-2003  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003  Jean-Louis Bergamo      <jlb@j1b.org>
 * Copyright (C) 2004       Sebastien Di Cintio     <sdicintio@ressource-toi.org>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2009-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2013       Florian Henry           <forian.henry@open-concept.pro>
 * Copyright (C) 2015       Charles-Fr BENKE        <charles.fr@benke.fr>
 * Copyright (C) 2016       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2017       Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2018-2022  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2022 		Antonin MARCHAL         <antonin@letempledujeu.fr>
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
 *    \file        htdocs/core/class/webportalfieldsmanager.class.php
 *    \ingroup    core
 *    \brief      File of class to manage fields
 */

require_once DOL_DOCUMENT_ROOT . '/webportal/class/context.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/fieldsmanager.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/fields/commonsellistfield.class.php';


/**
 *    Class to manage fields
 */
class WebPortalFieldsManager extends FieldsManager
{
	/**
	 *    Constructor
	 *
	 * @param DoliDB		$db 		Database handler
	 * @param Form|null		$form		Specific form handler
	 */
	public function __construct($db, $form = null)
	{
		parent::__construct($db, $form);

		CommonSellistField::$ajaxUrl = Context::getRootConfigUrl() . '/ajax/ajaxfield.php';
	}
}
