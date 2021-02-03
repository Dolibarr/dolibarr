<?php
/* Copyright (C) 2008-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2008-2017 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2020	   Ferran Marcet        <fmarcet@2byte.es>
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
 *  \file		htdocs/core/lib/security.lib.php
 *  \ingroup    core
 *  \brief		Set of function used for dolibarr security (common function included into filefunc.inc.php)
 *  			Warning, this file must not depends on other library files, except function.lib.php
 *  			because it is used at low code level.
 */


/**
 *	Encode a string with base 64 algorithm + specific delta change.
 *
 *	@param   string		$chain		string to encode
 *	@param   string		$key		rule to use for delta ('0', '1' or 'myownkey')
 *	@return  string					encoded string
 *  @see dol_decode()
 */
function dol_encode($chain, $key = '1')
{
	if (is_numeric($key) && $key == '1')	// rule 1 is offset of 17 for char
	{
		$output_tab = array();
		$strlength = dol_strlen($chain);
		for ($i = 0; $i < $strlength; $i++)
		{
			$output_tab[$i] = chr(ord(substr($chain, $i, 1)) + 17);
		}
		$chain = implode("", $output_tab);
	} elseif ($key)
	{
		$result = '';
		$strlength = dol_strlen($chain);
		for ($i = 0; $i < $strlength; $i++)
		{
			$keychar = substr($key, ($i % strlen($key)) - 1, 1);
			$result .= chr(ord(substr($chain, $i, 1)) + (ord($keychar) - 65));
		}
		$chain = $result;
	}

	return base64_encode($chain);
}

/**
 *	Decode a base 64 encoded + specific delta change.
 *  This function is called by filefunc.inc.php at each page call.
 *
 *	@param   string		$chain		string to decode
 *	@param   string		$key		rule to use for delta ('0', '1' or 'myownkey')
 *	@return  string					decoded string
 *  @see dol_encode()
 */
function dol_decode($chain, $key = '1')
{
	$chain = base64_decode($chain);

	if (is_numeric($key) && $key == '1')	// rule 1 is offset of 17 for char
	{
		$output_tab = array();
		$strlength = dol_strlen($chain);
		for ($i = 0; $i < $strlength; $i++)
		{
			$output_tab[$i] = chr(ord(substr($chain, $i, 1)) - 17);
		}

		$chain = implode("", $output_tab);
	} elseif ($key)
	{
		$result = '';
		$strlength = dol_strlen($chain);
		for ($i = 0; $i < $strlength; $i++)
		{
			$keychar = substr($key, ($i % strlen($key)) - 1, 1);
			$result .= chr(ord(substr($chain, $i, 1)) - (ord($keychar) - 65));
		}
		$chain = $result;
	}

	return $chain;
}


/**
 * 	Returns a hash of a string.
 *  If constant MAIN_SECURITY_HASH_ALGO is defined, we use this function as hashing function (recommanded value is 'password_hash')
 *  If constant MAIN_SECURITY_SALT is defined, we use it as a salt (used only if hashing algorightm is something else than 'password_hash').
 *
 * 	@param 		string		$chain		String to hash
 * 	@param		string		$type		Type of hash ('0':auto will use MAIN_SECURITY_HASH_ALGO else md5, '1':sha1, '2':sha1+md5, '3':md5, '4':md5 for OpenLdap with no salt, '5':sha256). Use '3' here, if hash is not needed for security purpose, for security need, prefer '0'.
 * 	@return		string					Hash of string
 *  @see getRandomPassword()
 */
function dol_hash($chain, $type = '0')
{
	global $conf;

	// No need to add salt for password_hash
	if (($type == '0' || $type == 'auto') && !empty($conf->global->MAIN_SECURITY_HASH_ALGO) && $conf->global->MAIN_SECURITY_HASH_ALGO == 'password_hash' && function_exists('password_hash'))
	{
		return password_hash($chain, PASSWORD_DEFAULT);
	}

	// Salt value
	if (!empty($conf->global->MAIN_SECURITY_SALT) && $type != '4' && $type !== 'md5openldap') $chain = $conf->global->MAIN_SECURITY_SALT.$chain;

	if ($type == '1' || $type == 'sha1') return sha1($chain);
	elseif ($type == '2' || $type == 'sha1md5') return sha1(md5($chain));
	elseif ($type == '3' || $type == 'md5') return md5($chain);
	elseif ($type == '4' || $type == 'md5openldap') return '{md5}'.base64_encode(mhash(MHASH_MD5, $chain)); // For OpenLdap with md5 (based on an unencrypted password in base)
	elseif ($type == '5') return hash('sha256', $chain);
	elseif (!empty($conf->global->MAIN_SECURITY_HASH_ALGO) && $conf->global->MAIN_SECURITY_HASH_ALGO == 'sha1') return sha1($chain);
	elseif (!empty($conf->global->MAIN_SECURITY_HASH_ALGO) && $conf->global->MAIN_SECURITY_HASH_ALGO == 'sha1md5') return sha1(md5($chain));

	// No particular encoding defined, use default
	return md5($chain);
}

/**
 * 	Compute a hash and compare it to the given one
 *  For backward compatibility reasons, if the hash is not in the password_hash format, we will try to match against md5 and sha1md5
 *  If constant MAIN_SECURITY_HASH_ALGO is defined, we use this function as hashing function.
 *  If constant MAIN_SECURITY_SALT is defined, we use it as a salt.
 *
 * 	@param 		string		$chain		String to hash (not hashed string)
 * 	@param 		string		$hash		hash to compare
 * 	@param		string		$type		Type of hash ('0':auto, '1':sha1, '2':sha1+md5, '3':md5, '4':md5 for OpenLdap, '5':sha256). Use '3' here, if hash is not needed for security purpose, for security need, prefer '0'.
 * 	@return		bool					True if the computed hash is the same as the given one
 */
function dol_verifyHash($chain, $hash, $type = '0')
{
	global $conf;

	if ($type == '0' && !empty($conf->global->MAIN_SECURITY_HASH_ALGO) && $conf->global->MAIN_SECURITY_HASH_ALGO == 'password_hash' && function_exists('password_verify')) {
		if ($hash[0] == '$') return password_verify($chain, $hash);
		elseif (strlen($hash) == 32) return dol_verifyHash($chain, $hash, '3'); // md5
		elseif (strlen($hash) == 40) return dol_verifyHash($chain, $hash, '2'); // sha1md5

		return false;
	}

	return dol_hash($chain, $type) == $hash;
}


/**
 *	Check permissions of a user to show a page and an object. Check read permission.
 * 	If GETPOST('action','aZ09') defined, we also check write and delete permission.
 *
 *	@param	User	$user      	  	User to check
 *	@param  string	$features	    Features to check (it must be module $object->element. Examples: 'societe', 'contact', 'produit&service', 'produit|service', ...)
 *	@param  int		$objectid      	Object ID if we want to check a particular record (optional) is linked to a owned thirdparty (optional).
 *	@param  string	$tableandshare  'TableName&SharedElement' with Tablename is table where object is stored. SharedElement is an optional key to define where to check entity for multicompany module. Param not used if objectid is null (optional).
 *	@param  string	$feature2		Feature to check, second level of permission (optional). Can be a 'or' check with 'sublevela|sublevelb'.
 *  @param  string	$dbt_keyfield   Field name for socid foreign key if not fk_soc. Not used if objectid is null (optional)
 *  @param  string	$dbt_select     Field name for select if not rowid. Not used if objectid is null (optional)
 *  @param	int		$isdraft		1=The object with id=$objectid is a draft
 * 	@return	int						Always 1, die process if not allowed
 *  @see dol_check_secure_access_document()
 */
function restrictedArea($user, $features, $objectid = 0, $tableandshare = '', $feature2 = '', $dbt_keyfield = 'fk_soc', $dbt_select = 'rowid', $isdraft = 0)
{
	global $db, $conf;
	global $hookmanager;

	//dol_syslog("functions.lib:restrictedArea $feature, $objectid, $dbtablename,$feature2,$dbt_socfield,$dbt_select");
	//print "user_id=".$user->id.", features=".$features.", feature2=".$feature2.", objectid=".$objectid;
	//print ", dbtablename=".$dbtablename.", dbt_socfield=".$dbt_keyfield.", dbt_select=".$dbt_select;
	//print ", perm: ".$features."->".$feature2."=".($user->rights->$features->$feature2->lire)."<br>";

	$parentfortableentity = '';

	if ($features == 'facturerec') $features = 'facture';
	if ($features == 'mo') $features = 'mrp';
	if ($features == 'member') $features = 'adherent';
	if ($features == 'subscription') { $features = 'adherent'; $feature2 = 'cotisation'; };
	if ($features == 'websitepage') { $features = 'website'; $tableandshare = 'website_page'; $parentfortableentity = 'fk_website@website'; }
	if ($features == 'project') $features = 'projet';
	if ($features == 'product') $features = 'produit';

	// Get more permissions checks from hooks
	$parameters = array('features'=>$features, 'objectid'=>$objectid, 'idtype'=>$dbt_select);
	$reshook = $hookmanager->executeHooks('restrictedArea', $parameters);

	if (isset($hookmanager->resArray['result'])) {
		if ($hookmanager->resArray['result'] == 0) accessforbidden(); // Module returns 0, so access forbidden
	}
	if ($reshook > 0) {		// No other test done.
		return 1;
	}

	if ($dbt_select != 'rowid' && $dbt_select != 'id') $objectid = "'".$objectid."'";

	// Features/modules to check
	$featuresarray = array($features);
	if (preg_match('/&/', $features)) $featuresarray = explode("&", $features);
	elseif (preg_match('/\|/', $features)) $featuresarray = explode("|", $features);

	// More subfeatures to check
	if (!empty($feature2)) $feature2 = explode("|", $feature2);

	// More parameters
	$params = explode('&', $tableandshare);
	$dbtablename = (!empty($params[0]) ? $params[0] : '');
	$sharedelement = (!empty($params[1]) ? $params[1] : $dbtablename);

	$listofmodules = explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL);

	// Check read permission from module
	$readok = 1; $nbko = 0;
	foreach ($featuresarray as $feature) {	// first we check nb of test ko
		$featureforlistofmodule = $feature;
		if ($featureforlistofmodule == 'produit') $featureforlistofmodule = 'product';
		if (!empty($user->socid) && !empty($conf->global->MAIN_MODULES_FOR_EXTERNAL) && !in_array($featureforlistofmodule, $listofmodules)) {	// If limits on modules for external users, module must be into list of modules for external users
			$readok = 0; $nbko++;
			continue;
		}

		if ($feature == 'societe') {
			if (!$user->rights->societe->lire && !$user->rights->fournisseur->lire) { $readok = 0; $nbko++; }
		} elseif ($feature == 'contact') {
			if (!$user->rights->societe->contact->lire) { $readok = 0; $nbko++; }
		} elseif ($feature == 'produit|service') {
			if (!$user->rights->produit->lire && !$user->rights->service->lire) { $readok = 0; $nbko++; }
		} elseif ($feature == 'prelevement') {
			if (!$user->rights->prelevement->bons->lire) { $readok = 0; $nbko++; }
		} elseif ($feature == 'cheque') {
			if (!$user->rights->banque->cheque) { $readok = 0; $nbko++; }
		} elseif ($feature == 'projet') {
			if (!$user->rights->projet->lire && !$user->rights->projet->all->lire) { $readok = 0; $nbko++; }
		} elseif (!empty($feature2)) { 													// This is for permissions on 2 levels
			$tmpreadok = 1;
			foreach ($feature2 as $subfeature) {
				if ($subfeature == 'user' && $user->id == $objectid) continue; // A user can always read its own card
				if (!empty($subfeature) && empty($user->rights->$feature->$subfeature->lire) && empty($user->rights->$feature->$subfeature->read)) { $tmpreadok = 0; } elseif (empty($subfeature) && empty($user->rights->$feature->lire) && empty($user->rights->$feature->read)) { $tmpreadok = 0; } else { $tmpreadok = 1; break; } // Break is to bypass second test if the first is ok
			}
			if (!$tmpreadok) {	// We found a test on feature that is ko
				$readok = 0; // All tests are ko (we manage here the and, the or will be managed later using $nbko).
				$nbko++;
			}
		} elseif (!empty($feature) && ($feature != 'user' && $feature != 'usergroup')) {		// This is permissions on 1 level
			if (empty($user->rights->$feature->lire)
				&& empty($user->rights->$feature->read)
				&& empty($user->rights->$feature->run)) { $readok = 0; $nbko++; }
		}
	}

	// If a or and at least one ok
	if (preg_match('/\|/', $features) && $nbko < count($featuresarray)) $readok = 1;

	if (!$readok) accessforbidden();
	//print "Read access is ok";

	// Check write permission from module (we need to know write permission to create but also to delete drafts record or to upload files)
	$createok = 1; $nbko = 0;
	$wemustcheckpermissionforcreate = (GETPOST('sendit', 'alpha') || GETPOST('linkit', 'alpha') || GETPOST('action', 'aZ09') == 'create' || GETPOST('action', 'aZ09') == 'update');
	$wemustcheckpermissionfordeletedraft = ((GETPOST("action", "aZ09") == 'confirm_delete' && GETPOST("confirm", "aZ09") == 'yes') || GETPOST("action", "aZ09") == 'delete');

	if ($wemustcheckpermissionforcreate || $wemustcheckpermissionfordeletedraft)
	{
		foreach ($featuresarray as $feature)
		{
			if ($feature == 'contact') {
				if (!$user->rights->societe->contact->creer) { $createok = 0; $nbko++; }
			} elseif ($feature == 'produit|service') {
				if (!$user->rights->produit->creer && !$user->rights->service->creer) { $createok = 0; $nbko++; }
			} elseif ($feature == 'prelevement') {
				if (!$user->rights->prelevement->bons->creer) { $createok = 0; $nbko++; }
			} elseif ($feature == 'commande_fournisseur') {
				if (!$user->rights->fournisseur->commande->creer) { $createok = 0; $nbko++; }
			} elseif ($feature == 'banque') {
				if (!$user->rights->banque->modifier) { $createok = 0; $nbko++; }
			} elseif ($feature == 'cheque') {
				if (!$user->rights->banque->cheque) { $createok = 0; $nbko++; }
			} elseif ($feature == 'import') {
				if (!$user->rights->import->run) { $createok = 0; $nbko++; }
			} elseif ($feature == 'ecm') {
				if (!$user->rights->ecm->upload) { $createok = 0; $nbko++; }
			} elseif (!empty($feature2)) {														// This is for permissions on one level
				foreach ($feature2 as $subfeature) {
					if ($subfeature == 'user' && $user->id == $objectid && $user->rights->user->self->creer) continue; // User can edit its own card
					if ($subfeature == 'user' && $user->id == $objectid && $user->rights->user->self->password) continue; // User can edit its own password

					if (empty($user->rights->$feature->$subfeature->creer)
					&& empty($user->rights->$feature->$subfeature->write)
					&& empty($user->rights->$feature->$subfeature->create)) {
						$createok = 0;
						$nbko++;
					} else {
						$createok = 1;
						// Break to bypass second test if the first is ok
						break;
					}
				}
			} elseif (!empty($feature)) {												// This is for permissions on 2 levels ('creer' or 'write')
				//print '<br>feature='.$feature.' creer='.$user->rights->$feature->creer.' write='.$user->rights->$feature->write; exit;
				if (empty($user->rights->$feature->creer)
				&& empty($user->rights->$feature->write)
				&& empty($user->rights->$feature->create)) {
					$createok = 0;
					$nbko++;
				}
			}
		}

		// If a or and at least one ok
		if (preg_match('/\|/', $features) && $nbko < count($featuresarray)) $createok = 1;

		if ($wemustcheckpermissionforcreate && !$createok) accessforbidden();
		//print "Write access is ok";
	}

	// Check create user permission
	$createuserok = 1;
	if (GETPOST('action', 'aZ09') == 'confirm_create_user' && GETPOST("confirm", 'aZ09') == 'yes')
	{
		if (!$user->rights->user->user->creer) $createuserok = 0;

		if (!$createuserok) accessforbidden();
		//print "Create user access is ok";
	}

	// Check delete permission from module
	$deleteok = 1; $nbko = 0;
	if ((GETPOST("action", "aZ09") == 'confirm_delete' && GETPOST("confirm", "aZ09") == 'yes') || GETPOST("action", "aZ09") == 'delete')
	{
		foreach ($featuresarray as $feature)
		{
			if ($feature == 'contact')
			{
				if (!$user->rights->societe->contact->supprimer) $deleteok = 0;
			} elseif ($feature == 'produit|service')
			{
				if (!$user->rights->produit->supprimer && !$user->rights->service->supprimer) $deleteok = 0;
			} elseif ($feature == 'commande_fournisseur')
			{
				if (!$user->rights->fournisseur->commande->supprimer) $deleteok = 0;
			} elseif ($feature == 'banque')
			{
				if (!$user->rights->banque->modifier) $deleteok = 0;
			} elseif ($feature == 'cheque')
			{
				if (!$user->rights->banque->cheque) $deleteok = 0;
			} elseif ($feature == 'ecm')
			{
				if (!$user->rights->ecm->upload) $deleteok = 0;
			} elseif ($feature == 'ftp')
			{
				if (!$user->rights->ftp->write) $deleteok = 0;
			} elseif ($feature == 'salaries')
			{
				if (!$user->rights->salaries->delete) $deleteok = 0;
			} elseif ($feature == 'salaries')
			{
				if (!$user->rights->salaries->delete) $deleteok = 0;
			} elseif (!empty($feature2))							// This is for permissions on 2 levels
			{
				foreach ($feature2 as $subfeature)
				{
					if (empty($user->rights->$feature->$subfeature->supprimer) && empty($user->rights->$feature->$subfeature->delete)) $deleteok = 0;
					else { $deleteok = 1; break; } // For bypass the second test if the first is ok
				}
			} elseif (!empty($feature))							// This is used for permissions on 1 level
			{
				//print '<br>feature='.$feature.' creer='.$user->rights->$feature->supprimer.' write='.$user->rights->$feature->delete;
				if (empty($user->rights->$feature->supprimer)
					&& empty($user->rights->$feature->delete)
					&& empty($user->rights->$feature->run)) $deleteok = 0;
			}
		}

		// If a or and at least one ok
		if (preg_match('/\|/', $features) && $nbko < count($featuresarray)) $deleteok = 1;

		if (!$deleteok && !($isdraft && $createok)) accessforbidden();
		//print "Delete access is ok";
	}

	// If we have a particular object to check permissions on, we check this object
	// is linked to a company allowed to $user.
	if (!empty($objectid) && $objectid > 0)
	{
		$ok = checkUserAccessToObject($user, $featuresarray, $objectid, $tableandshare, $feature2, $dbt_keyfield, $dbt_select, $parentfortableentity);
		$params = array('objectid' => $objectid, 'features' => join(',', $featuresarray), 'features2' => $feature2);
		return $ok ? 1 : accessforbidden('', 1, 1, 0, $params);
	}

	return 1;
}

/**
 * Check access by user to object.
 * This function is also called by restrictedArea
 *
 * @param User			$user					User to check
 * @param array			$featuresarray			Features/modules to check. Example: ('user','service','member','project','task',...)
 * @param int|string	$objectid				Object ID if we want to check a particular record (optional) is linked to a owned thirdparty (optional).
 * @param string		$tableandshare			'TableName&SharedElement' with Tablename is table where object is stored. SharedElement is an optional key to define where to check entity for multicompany modume. Param not used if objectid is null (optional).
 * @param string		$feature2				Feature to check, second level of permission (optional). Can be or check with 'level1|level2'.
 * @param string		$dbt_keyfield			Field name for socid foreign key if not fk_soc. Not used if objectid is null (optional)
 * @param string		$dbt_select				Field name for select if not rowid. Not used if objectid is null (optional)
 * @param string		$parenttableforentity  	Parent table for entity. Example 'fk_website@website'
 * @return	bool								True if user has access, False otherwise
 * @see restrictedArea()
 */
function checkUserAccessToObject($user, $featuresarray, $objectid = 0, $tableandshare = '', $feature2 = '', $dbt_keyfield = '', $dbt_select = 'rowid', $parenttableforentity = '')
{
	global $db, $conf;

	// More parameters
	$params = explode('&', $tableandshare);
	$dbtablename = (!empty($params[0]) ? $params[0] : '');
	$sharedelement = (!empty($params[1]) ? $params[1] : $dbtablename);

	foreach ($featuresarray as $feature)
	{
		$sql = '';

		// For backward compatibility
		if ($feature == 'member')  $feature = 'adherent';
		if ($feature == 'project') $feature = 'projet';
		if ($feature == 'task')    $feature = 'projet_task';

		$check = array('adherent', 'banque', 'bom', 'don', 'mrp', 'user', 'usergroup', 'product', 'produit', 'service', 'produit|service', 'categorie', 'resource', 'expensereport', 'holiday', 'website'); // Test on entity only (Objects with no link to company)
		$checksoc = array('societe'); // Test for societe object
		$checkother = array('contact', 'agenda'); // Test on entity and link to third party. Allowed if link is empty (Ex: contacts...).
		$checkproject = array('projet', 'project'); // Test for project object
		$checktask = array('projet_task');	// Test for task object
		$nocheck = array('barcode', 'stock'); // No test
		//$checkdefault = 'all other not already defined'; // Test on entity and link to third party. Not allowed if link is empty (Ex: invoice, orders...).

		// If dbtablename not defined, we use same name for table than module name
		if (empty($dbtablename))
		{
			$dbtablename = $feature;
			$sharedelement = (!empty($params[1]) ? $params[1] : $dbtablename); // We change dbtablename, so we set sharedelement too.
		}

		// Check permission for object with entity
		if (in_array($feature, $check))
		{
			$sql = "SELECT COUNT(dbt.".$dbt_select.") as nb";
			$sql .= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
			if (($feature == 'user' || $feature == 'usergroup') && !empty($conf->multicompany->enabled))
			{
				if (!empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE))
				{
					if ($conf->entity == 1 && $user->admin && !$user->entity)
					{
						$sql .= " WHERE dbt.".$dbt_select." IN (".$objectid.")";
						$sql .= " AND dbt.entity IS NOT NULL";
					} else {
						$sql .= ",".MAIN_DB_PREFIX."usergroup_user as ug";
						$sql .= " WHERE dbt.".$dbt_select." IN (".$objectid.")";
						$sql .= " AND ((ug.fk_user = dbt.rowid";
						$sql .= " AND ug.entity IN (".getEntity('usergroup')."))";
						$sql .= " OR dbt.entity = 0)"; // Show always superadmin
					}
				} else {
					$sql .= " WHERE dbt.".$dbt_select." IN (".$objectid.")";
					$sql .= " AND dbt.entity IN (".getEntity($sharedelement, 1).")";
				}
			} else {
				$reg = array();
				if ($parenttableforentity && preg_match('/(.*)@(.*)/', $parenttableforentity, $reg)) {
					$sql .= ", ".MAIN_DB_PREFIX.$reg[2]." as dbtp";
					$sql .= " WHERE dbt.".$reg[1]." = dbtp.rowid AND dbt.".$dbt_select." IN (".$objectid.")";
					$sql .= " AND dbtp.entity IN (".getEntity($sharedelement, 1).")";
				} else {
					$sql .= " WHERE dbt.".$dbt_select." IN (".$objectid.")";
					$sql .= " AND dbt.entity IN (".getEntity($sharedelement, 1).")";
				}
			}
		} elseif (in_array($feature, $checksoc))	// We check feature = checksoc
		{
			// If external user: Check permission for external users
			if ($user->socid > 0)
			{
				if ($user->socid <> $objectid) return false;
			} // If internal user: Check permission for internal users that are restricted on their objects
			elseif (!empty($conf->societe->enabled) && ($user->rights->societe->lire && !$user->rights->societe->client->voir))
			{
				$sql = "SELECT COUNT(sc.fk_soc) as nb";
				$sql .= " FROM (".MAIN_DB_PREFIX."societe_commerciaux as sc";
				$sql .= ", ".MAIN_DB_PREFIX."societe as s)";
				$sql .= " WHERE sc.fk_soc IN (".$objectid.")";
				$sql .= " AND sc.fk_user = ".$user->id;
				$sql .= " AND sc.fk_soc = s.rowid";
				$sql .= " AND s.entity IN (".getEntity($sharedelement, 1).")";
			} // If multicompany and internal users with all permissions, check user is in correct entity
			elseif (!empty($conf->multicompany->enabled))
			{
				$sql = "SELECT COUNT(s.rowid) as nb";
				$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
				$sql .= " WHERE s.rowid IN (".$objectid.")";
				$sql .= " AND s.entity IN (".getEntity($sharedelement, 1).")";
			}
		} elseif (in_array($feature, $checkother))	// Test on entity and link to societe. Allowed if link is empty (Ex: contacts...).
		{
			// If external user: Check permission for external users
			if ($user->socid > 0)
			{
				$sql = "SELECT COUNT(dbt.".$dbt_select.") as nb";
				$sql .= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
				$sql .= " WHERE dbt.".$dbt_select." IN (".$objectid.")";
				$sql .= " AND dbt.fk_soc = ".$user->socid;
			} // If internal user: Check permission for internal users that are restricted on their objects
			elseif (!empty($conf->societe->enabled) && ($user->rights->societe->lire && !$user->rights->societe->client->voir))
			{
				$sql = "SELECT COUNT(dbt.".$dbt_select.") as nb";
				$sql .= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON dbt.fk_soc = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
				$sql .= " WHERE dbt.".$dbt_select." IN (".$objectid.")";
				$sql .= " AND (dbt.fk_soc IS NULL OR sc.fk_soc IS NOT NULL)"; // Contact not linked to a company or to a company of user
				$sql .= " AND dbt.entity IN (".getEntity($sharedelement, 1).")";
			} // If multicompany and internal users with all permissions, check user is in correct entity
			elseif (!empty($conf->multicompany->enabled))
			{
				$sql = "SELECT COUNT(dbt.".$dbt_select.") as nb";
				$sql .= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
				$sql .= " WHERE dbt.".$dbt_select." IN (".$objectid.")";
				$sql .= " AND dbt.entity IN (".getEntity($sharedelement, 1).")";
			}
			if ($feature == 'agenda')// Also check owner or attendee for users without allactions->read
			{
				if ($objectid > 0 && empty($user->rights->agenda->allactions->read)) {
					require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
					$action = new ActionComm($db);
					$action->fetch($objectid);
					if ($action->authorid != $user->id && $action->userownerid != $user->id && !(array_key_exists($user->id, $action->userassigned))) {
						return false;
					}
				}
			}
		} elseif (in_array($feature, $checkproject)) {
			if (!empty($conf->projet->enabled) && empty($user->rights->projet->all->lire))
			{
				include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
				$projectstatic = new Project($db);
				$tmps = $projectstatic->getProjectsAuthorizedForUser($user, 0, 1, 0);
				$tmparray = explode(',', $tmps);
				if (!in_array($objectid, $tmparray)) return false;
			} else {
				$sql = "SELECT COUNT(dbt.".$dbt_select.") as nb";
				$sql .= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
				$sql .= " WHERE dbt.".$dbt_select." IN (".$objectid.")";
				$sql .= " AND dbt.entity IN (".getEntity($sharedelement, 1).")";
			}
		} elseif (in_array($feature, $checktask)) {
			if (!empty($conf->projet->enabled) && empty($user->rights->projet->all->lire))
			{
				$task = new Task($db);
				$task->fetch($objectid);

				include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
				$projectstatic = new Project($db);
				$tmps = $projectstatic->getProjectsAuthorizedForUser($user, 0, 1, 0);
				$tmparray = explode(',', $tmps);
				if (!in_array($task->fk_project, $tmparray)) return false;
			} else {
				$sql = "SELECT COUNT(dbt.".$dbt_select.") as nb";
				$sql .= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
				$sql .= " WHERE dbt.".$dbt_select." IN (".$objectid.")";
				$sql .= " AND dbt.entity IN (".getEntity($sharedelement, 1).")";
			}
		} elseif (!in_array($feature, $nocheck)) {		// By default (case of $checkdefault), we check on object entity + link to third party on field $dbt_keyfield
			// If external user: Check permission for external users
			if ($user->socid > 0) {
				if (empty($dbt_keyfield)) dol_print_error('', 'Param dbt_keyfield is required but not defined');
				$sql = "SELECT COUNT(dbt.".$dbt_keyfield.") as nb";
				$sql .= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
				$sql .= " WHERE dbt.rowid IN (".$objectid.")";
				$sql .= " AND dbt.".$dbt_keyfield." = ".$user->socid;
			} elseif (!empty($conf->societe->enabled)) {
				// If internal user: Check permission for internal users that are restricted on their objects
				if ($feature != 'ticket' && !$user->rights->societe->client->voir) {
					if (empty($dbt_keyfield)) dol_print_error('', 'Param dbt_keyfield is required but not defined');
					$sql = "SELECT COUNT(sc.fk_soc) as nb";
					$sql .= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
					$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
					$sql .= " WHERE dbt.".$dbt_select." IN (".$objectid.")";
					$sql .= " AND dbt.entity IN (".getEntity($sharedelement, 1).")";
					$sql .= " AND sc.fk_soc = dbt.".$dbt_keyfield;
					$sql .= " AND sc.fk_user = ".$user->id;
				}
				// On ticket, the thirdparty is not mandatory, so we need a special test to accept record with no thirdparties.
				if ($feature == 'ticket' && !$user->rights->societe->client->voir) {
					$sql = "SELECT COUNT(dbt.".$dbt_select.") as nb";
					$sql .= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
					$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON sc.fk_soc = dbt.".$dbt_keyfield." AND sc.fk_user = ".$user->id;
					$sql .= " WHERE dbt.".$dbt_select." IN (".$objectid.")";
					$sql .= " AND dbt.entity IN (".getEntity($sharedelement, 1).")";
					$sql .= " AND (sc.fk_user = ".$user->id." OR sc.fk_user IS NULL)";
				}
			} // If multicompany and internal users with all permissions, check user is in correct entity
			elseif (!empty($conf->multicompany->enabled))
			{
				$sql = "SELECT COUNT(dbt.".$dbt_select.") as nb";
				$sql .= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
				$sql .= " WHERE dbt.".$dbt_select." IN (".$objectid.")";
				$sql .= " AND dbt.entity IN (".getEntity($sharedelement, 1).")";
			}
		}

		if ($sql)
		{
			$resql = $db->query($sql);
			if ($resql)
			{
				$obj = $db->fetch_object($resql);
				if (!$obj || $obj->nb < count(explode(',', $objectid))) return false;
			} else {
				return false;
			}
		}
	}

	return true;
}

/**
 *	Show a message to say access is forbidden and stop program
 *	Calling this function terminate execution of PHP.
 *
 *	@param	string		$message			Force error message
 *	@param	int			$printheader		Show header before
 *  @param  int			$printfooter        Show footer after
 *  @param  int			$showonlymessage    Show only message parameter. Otherwise add more information.
 *  @param  array|null  $params         	More parameters provided to hook
 *  @return	void
 */
function accessforbidden($message = '', $printheader = 1, $printfooter = 1, $showonlymessage = 0, $params = null)
{
	global $conf, $db, $user, $langs, $hookmanager;
	if (!is_object($langs))
	{
		include_once DOL_DOCUMENT_ROOT.'/core/class/translate.class.php';
		$langs = new Translate('', $conf);
		$langs->setDefaultLang();
	}

	$langs->load("errors");

	if ($printheader)
	{
		if (function_exists("llxHeader")) llxHeader('');
		elseif (function_exists("llxHeaderVierge")) llxHeaderVierge('');
	}
	print '<div class="error">';
	if (!$message) print $langs->trans("ErrorForbidden");
	else print $message;
	print '</div>';
	print '<br>';
	if (empty($showonlymessage))
	{
		global $action, $object;
		if (empty($hookmanager))
		{
			$hookmanager = new HookManager($db);
			// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
			$hookmanager->initHooks(array('main'));
		}
		$parameters = array('message'=>$message, 'params'=>$params);
		$reshook = $hookmanager->executeHooks('getAccessForbiddenMessage', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
		print $hookmanager->resPrint;
		if (empty($reshook))
		{
			if ($user->login)
			{
				print $langs->trans("CurrentLogin").': <font class="error">'.$user->login.'</font><br>';
				print $langs->trans("ErrorForbidden2", $langs->transnoentitiesnoconv("Home"), $langs->transnoentitiesnoconv("Users"));
			} else {
				print $langs->trans("ErrorForbidden3");
			}
		}
	}
	if ($printfooter && function_exists("llxFooter")) llxFooter();
	exit(0);
}
