<?php
/* Copyright (C) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Xavier Dutoit        <doli@sydesy.com>
 * Copyright (C) 2004-2025 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2005 	   Simon Tosser         <simon@kornog-computing.com>
 * Copyright (C) 2006 	   Andre Cianfarani     <andre.cianfarani@acdeveloppement.net>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015      Bahfir Abbes         <bafbes@gmail.com>
 * Copyright (C) 2024-2025 MDW					<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024      Frédéric France      <frederic.france@free.fr>
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
 *	\file       htdocs/version.inc.php
 * 	\ingroup	core
 *  \brief      File that include the conf.php file and commons lib like functions.lib.php
 */

if (!defined('DOL_APPLICATION_TITLE')) {
	define('DOL_APPLICATION_TITLE', 'Dolibarr');
}

// The major version of Dolibarr
define('DOL_MAJOR_VERSION', '23');

define('DOL_VERSION', constant('DOL_MAJOR_VERSION').'.'.constant('DOL_MINOR_VERSION'));
// DOL_VERSION is now a.b.c-alpha, a.b.c-beta, a.b.c-rcX or a.b.c

// Set to 1 if the beta version is a just a candidate for certification (not yet certified) or if the stable version has been certified.
// Use 2 to force LNE featuresfro debug purposes
define('CERTIF_LNE', '1');
