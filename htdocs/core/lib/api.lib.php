<?php
/* Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2024		MDW					<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2026		Frédéric France             <frederic.france@free.fr>
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
 * or see https://www.gnu.org/
 */

/**
 * \file       htdocs/core/lib/api.lib.php
 * \brief      Ensemble de functions de base pour le module propal
 * \ingroup    propal
 */

/**
 *  Return array head with list of tabs to view object information.
 *
 *  @return	array<array{0:string,1:string,2:string}>	head array with tabs
 */
function api_admin_prepare_head()
{
	global $langs;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/api/admin/index.php';
	$head[$h][1] = $langs->trans("Parameter");
	$head[$h][2] = 'parameter';
	$h++;

	if (getDolGlobalString('API_IN_TOKEN_TABLE')) {
		$head[$h][0] = DOL_URL_ROOT.'/api/admin/token_list.php';
		$head[$h][1] = $langs->trans("ListOfTokens");
		$head[$h][2] = 'token_list';
		$h++;
	}

	return $head;
}


/**
 * Return every REST API endpoint class of the application.
 *
 * This is the single description of what an endpoint is: a module descriptor
 * names a module, getModuleDirForApiClass() maps it to the directory holding
 * its API classes, and every api_<key>.class.php there is one endpoint. The
 * class name is derived the way the REST entry point derives it, with
 * str_replace('_', '', ucwords()), and resolved through class_exists(), which
 * is case insensitive: api_supplier_invoices.class.php yields the candidate
 * "Supplierinvoices" and matches the declared SupplierInvoices. The computed
 * spelling is kept, not the declared one, because it is the string that is
 * registered as the API class and that ends up in the routing table.
 *
 * Module enablement is off by default: a caller serving HTTP requests wants only
 * the endpoints of the enabled modules, while a caller describing the API surface
 * needs the whole surface, independently of the local configuration.
 *
 * @param	bool	$includeExternalModules		Include the endpoints provided by external modules
 * @param	bool	$onlyEnabledModules			Keep only the endpoints of the enabled modules
 * @return	array<string,array{module:string,path:string,class:string}>	Endpoints keyed by lowercase class name, sorted by key
 */
function dolGetApiEndpoints($includeExternalModules = false, $onlyEnabledModules = false)
{
	// dolGetModulesDirs() and getModuleDirForApiClass() live there, and main.inc.php
	// only loads that library conditionally.
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

	$endpoints = [];

	foreach (dolGetModulesDirs() as $dir) {
		$handle = @opendir(dol_osencode($dir));
		if (!is_resource($handle)) {
			continue;
		}

		while (($file = readdir($handle)) !== false) {
			$regmod = [];
			if (!is_readable($dir.$file) || !preg_match("/^mod(.*)\.class\.php$/i", $file, $regmod)) {
				continue;
			}

			$module = strtolower($regmod[1]);
			if ($onlyEnabledModules && !isModEnabled(dolGetModuleNameForEnabled($module))) {
				continue;
			}

			$dirpart = dol_buildpath('/'.getModuleDirForApiClass($module).'/class/');
			if (!$includeExternalModules && !dolIsUnderMainDocumentRoot($dirpart)) {
				continue;
			}

			$handlepart = @opendir(dol_osencode($dirpart));
			if (!is_resource($handlepart)) {
				continue;
			}

			while (($filesearched = readdir($handlepart)) !== false) {
				$regapi = [];
				if ($filesearched == 'api_access.class.php') {
					continue;
				}
				if (!is_readable($dirpart.$filesearched) || !preg_match("/^api_(.*)\.class\.php$/i", $filesearched, $regapi)) {
					continue;
				}

				$classname = str_replace('_', '', ucwords($regapi[1]));
				$declared = dolGetDeclaredClasses($dirpart.$filesearched);

				if (in_array(strtolower($classname.'Api'), $declared, true)) {
					$classname .= 'Api';
				} elseif (!in_array(strtolower($classname), $declared, true)) {
					// Reading the file did not settle it. That happens when the endpoint class
					// is declared in a sibling file the endpoint file includes, or when the
					// file declares a namespace, in which case the short name the reader finds
					// is not the name to register. Fall back on loading the file, which is what
					// the REST entry point always did; only the two core files that pull
					// main.inc.php cannot stand it, and those resolve above.
					require_once $dirpart.$filesearched;
					if (class_exists($classname.'Api')) {
						$classname .= 'Api';
					} elseif (!class_exists($classname)) {
						dol_syslog("dolGetApiEndpoints found ".$filesearched." but no class ".$classname." after loading it", LOG_WARNING);
						continue;
					}
				}

				$endpoints[strtolower($classname)] = [
					'module' => $module,
					'path' => $dirpart.$filesearched,
					'class' => $classname,
				];
			}
			closedir($handlepart);
		}
		closedir($handle);
	}

	ksort($endpoints);

	return $endpoints;
}

/**
 * Return the module name isModEnabled() answers for, given a module descriptor name.
 *
 * A handful of modules are named one way in their descriptor and another way in the
 * configuration. The exceptions are the ones htdocs/api/index.php has always applied.
 *
 * @param	string	$module		Lowercase module name, as the mod<Name>.class.php file gives it
 * @return	string				Name to pass to isModEnabled()
 */
function dolGetModuleNameForEnabled($module)
{
	if ($module == 'propale') {
		return 'propal';
	}
	if ($module == 'supplierproposal') {
		return 'supplier_proposal';
	}
	if ($module == 'ficheinter') {
		return 'intervention';
	}
	if ($module == 'product' && !isModEnabled('product') && isModEnabled('service')) {
		return 'service';
	}

	return $module;
}

/**
 * Tell whether a path sits under the main document root rather than an alternate one.
 *
 * The alternate roots hold the external modules. Their location is whatever
 * $dolibarr_main_document_root_alt says, so testing for "/custom/" only works on the
 * installs that happen to use that name.
 *
 * @param	string	$path	Absolute path to test
 * @return	bool			True when the path is under the main document root
 */
function dolIsUnderMainDocumentRoot($path)
{
	global $conf;

	$main = isset($conf->file->dol_document_root['main']) ? $conf->file->dol_document_root['main'] : DOL_DOCUMENT_ROOT;

	if (strpos($path, rtrim($main, '/').'/') !== 0) {
		return false;
	}

	// In the standard layout the alternate root is htdocs/custom, which sits inside
	// the main one, so being below the main root is not enough to be a core file.
	// Anything below an alternate root belongs to an external module.
	if (!empty($conf->file->dol_document_root) && is_array($conf->file->dol_document_root)) {
		foreach ($conf->file->dol_document_root as $key => $root) {
			if ($key === 'main' || empty($root)) {
				continue;
			}
			if (strpos($path, rtrim($root, '/').'/') === 0) {
				return false;
			}
		}
	}

	return true;
}

/**
 * Return the lowercase names of the classes a PHP file declares.
 *
 * The REST endpoint discovery needs to know which class an api_<key>.class.php file
 * provides without executing it: two of those files load main.inc.php when included,
 * which assumes the global scope of a web request and cannot run from a command line
 * tool or a test. Tokenising the file answers the same question with no side effect.
 *
 * @param	string	$filepath	Absolute path of the PHP file to read
 * @return	string[]			Lowercase class names declared by the file
 */
function dolGetDeclaredClasses($filepath)
{
	$content = @file_get_contents($filepath);
	if ($content === false) {
		return [];
	}

	$classes = [];
	$tokens = token_get_all($content);

	// A namespaced file is not for this reader to answer: the name to register is the
	// qualified one, and returning the short name would send the caller to a class that
	// does not exist. Let the caller fall back on loading the file.
	foreach ($tokens as $token) {
		if (is_array($token) && $token[0] === T_NAMESPACE) {
			return [];
		}
	}
	$count = count($tokens);
	// Only the type of the token before the current one is carried, and never the
	// token itself: reading it back as $tokens[$i - 1] is a negative offset on the
	// first turn, and keeping the token would be an array access on a null.
	// 0 is not a token type, so it stands for "no token before this one".
	$previoustype = 0;
	foreach ($tokens as $i => $token) {
		// Skip the ::class constant and anonymous classes: both are T_CLASS without a name after it.
		if (is_array($token) && $token[0] === T_CLASS && $previoustype !== T_DOUBLE_COLON) {
			for ($j = $i + 1; $j < $count; $j++) {
				if (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
					continue;
				}
				if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
					$classes[] = strtolower($tokens[$j][1]);
				}
				break;
			}
		}
		$previoustype = is_array($token) ? $token[0] : 0;
	}

	return $classes;
}
