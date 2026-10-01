<?php
/**
 * Copyright (C) 2013	Marcos García	<marcosgdf@gmail.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2024-2026	MDW					<mdeweerd@users.noreply.github.com>
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
 * Return the default seed for online signature links.
 *
 * @return	string
 */
function getOnlineSignatureDefaultSecuritySeed()
{
	global $dolibarr_main_instance_unique_id;

	return substr(dol_hash('dolibarr'.$dolibarr_main_instance_unique_id, 'sha256'), 0, 32);
}

/**
 * Return the source definition used by the public online signature workflow.
 *
 * External modules can expose their own sources with the hook
 * getOnlineSignatureSourceDefinition in contexts onlinesign/ajaxonlinesign.
 *
 * @param	string				$source		Source code from URL
 * @param	string				$ref		Object reference
 * @param	int					$entity		Entity id
 * @param	CommonObject|null	$obj		Object, when already available
 * @return	array<string,mixed>				Source definition, empty array if unsupported
 */
function getOnlineSignatureSourceDefinition($source, $ref = '', $entity = 0, $obj = null)
{
	global $db, $hookmanager, $action;

	$source = (string) $source;
	if ($source == 'propale') {
		$source = 'proposal';
	}

	$definitions = array(
		'order' => array(
			'source' => 'order',
			'module' => 'commande',
			'elementtype' => 'commande',
			'modulepart' => 'commande',
			'document_modulepart' => 'order',
			'langfiles' => array('orders', 'commercial'),
			'allow_const' => 'ORDER_ALLOW_ONLINESIGN',
			'securekey_const' => 'ORDER_ONLINE_SIGNATURE_SECURITY_TOKEN',
			'signature_position_prefix' => 'ORDER',
			'native_finalizer' => 'order',
		),
		'proposal' => array(
			'source' => 'proposal',
			'module' => 'propal',
			'elementtype' => 'propal',
			'modulepart' => 'propal',
			'document_modulepart' => 'proposal',
			'langfiles' => array('proposal', 'commercial'),
			'allow_const' => 'PROPOSAL_ALLOW_ONLINESIGN',
			'securekey_const' => 'PROPOSAL_ONLINE_SIGNATURE_SECURITY_TOKEN',
			'signature_position_prefix' => 'PROPAL',
			'native_finalizer' => 'proposal',
		),
		'contract' => array(
			'source' => 'contract',
			'module' => 'contract',
			'elementtype' => 'contract',
			'modulepart' => 'contract',
			'document_modulepart' => 'contract',
			'langfiles' => array('contracts', 'commercial'),
			'allow_const' => 'CONTRACT_ALLOW_ONLINESIGN',
			'securekey_const' => 'CONTRACT_ONLINE_SIGNATURE_SECURITY_TOKEN',
			'signature_position_prefix' => 'CONTRACT',
			'signed_status_method' => 'setSignedStatus',
			'signed_status_value' => 3,
			'signed_trigger' => 'CONTRACT_MODIFY',
			'native_finalizer' => 'signed_status',
		),
		'fichinter' => array(
			'source' => 'fichinter',
			'module' => 'intervention',
			'elementtype' => 'fichinter',
			'modulepart' => 'fichinter',
			'document_modulepart' => 'fichinter',
			'langfiles' => array('interventions', 'commercial'),
			'allow_const' => 'FICHINTER_ALLOW_ONLINE_SIGN',
			'securekey_const' => 'FICHINTER_ONLINE_SIGNATURE_SECURITY_TOKEN',
			'signature_position_prefix' => 'FICHINTER',
			'signed_status_method' => 'setSignedStatus',
			'signed_status_value' => 3,
			'signed_trigger' => 'FICHINTER_MODIFY',
			'native_finalizer' => 'signed_status',
		),
		'societe_rib' => array(
			'source' => 'societe_rib',
			'module' => '',
			'elementtype' => 'societe_rib',
			'modulepart' => 'company',
			'document_modulepart' => 'company',
			'langfiles' => array('companies', 'commercial', 'withdrawals'),
			'allow_const' => 'SOCIETE_RIB_ALLOW_ONLINESIGN',
			'securekey_const' => 'SOCIETE_RIB_ONLINE_SIGNATURE_SECURITY_TOKEN',
			'signature_position_prefix' => 'SOCIETE_RIB',
			'native_finalizer' => 'societe_rib',
		),
		'expedition' => array(
			'source' => 'expedition',
			'module' => 'expedition',
			'elementtype' => 'shipping',
			'modulepart' => 'expedition',
			'document_modulepart' => 'expedition',
			'langfiles' => array('sendings', 'commercial'),
			'allow_const' => 'EXPEDITION_ALLOW_ONLINESIGN',
			'securekey_const' => 'EXPEDITION_ONLINE_SIGNATURE_SECURITY_TOKEN',
			'signature_position_prefix' => 'SHIPMENT',
			'signed_status_method' => 'setSignedStatus',
			'signed_status_value' => 3,
			'signed_trigger' => 'SHIPPING_MODIFY',
			'native_finalizer' => 'signed_status',
		),
	);

	$definition = $definitions[$source] ?? array();
	if (is_object($obj)) {
		$definition['object'] = $obj;
	}

	if (!is_object($hookmanager)) {
		require_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
		$hookmanager = new HookManager($db);
	}
	$hookmanager->initHooks(array('onlinesign', 'ajaxonlinesign'));

	$parameters = array(
		'source' => $source,
		'ref' => $ref,
		'entity' => (int) $entity,
		'source_definition' => $definition,
	);
	$tmpobject = is_object($obj) ? $obj : new stdClass();
	$reshook = $hookmanager->executeHooks('getOnlineSignatureSourceDefinition', $parameters, $tmpobject, $action);
	if ($reshook < 0) {
		return array();
	}

	if (!empty($hookmanager->resArray) && is_array($hookmanager->resArray)) {
		if (!empty($hookmanager->resArray['source']) && $hookmanager->resArray['source'] == $source) {
			$definition = array_replace($definition, $hookmanager->resArray);
		} elseif (!empty($hookmanager->resArray['source_definition']) && is_array($hookmanager->resArray['source_definition'])) {
			$definition = array_replace($definition, $hookmanager->resArray['source_definition']);
		} elseif (!empty($hookmanager->resArray[$source]) && is_array($hookmanager->resArray[$source])) {
			$definition = array_replace($definition, $hookmanager->resArray[$source]);
		}
	}

	if (!empty($definition) && empty($definition['source'])) {
		$definition['source'] = $source;
	}

	return $definition;
}

/**
 * Tell if an online signature source can be used in the current environment.
 *
 * @param	array<string,mixed>	$definition		Source definition
 * @return	bool
 */
function isOnlineSignatureSourceEnabled($definition)
{
	if (empty($definition) || !is_array($definition)) {
		return false;
	}

	if (isset($definition['enabled']) && empty($definition['enabled'])) {
		return false;
	}

	if (!empty($definition['module']) && !isModEnabled((string) $definition['module'])) {
		return false;
	}

	if (!empty($definition['allow_const']) && !getDolGlobalInt((string) $definition['allow_const'])) {
		return false;
	}

	return true;
}

/**
 * Return the seed used for a source secure key.
 *
 * @param	array<string,mixed>	$definition		Source definition
 * @return	string
 */
function getOnlineSignatureSecuritySeed($definition)
{
	$defaultsalt = getOnlineSignatureDefaultSecuritySeed();
	$constname = '';
	if (!empty($definition['securekey_const'])) {
		$constname = (string) $definition['securekey_const'];
	} elseif (!empty($definition['source'])) {
		$constname = dol_strtoupper((string) $definition['source']).'_ONLINE_SIGNATURE_SECURITY_TOKEN';
	}

	return $constname ? getDolGlobalString($constname, $defaultsalt) : $defaultsalt;
}

/**
 * Return the secure key for an online signature source/ref/entity tuple.
 *
 * @param	array<string,mixed>	$definition		Source definition
 * @param	string				$ref			Object reference
 * @param	int					$entity			Entity id
 * @return	string
 */
function getOnlineSignatureSecureKey($definition, $ref, $entity = 0)
{
	$source = empty($definition['source']) ? '' : (string) $definition['source'];
	$securekeyseed = getOnlineSignatureSecuritySeed($definition);

	return dol_hash($securekeyseed.$source.$ref.(isModEnabled('multicompany') ? (int) $entity : ''), 'hash');
}

/**
 * Verify the secure key for an online signature source/ref/entity tuple.
 *
 * @param	array<string,mixed>	$definition		Source definition
 * @param	string				$ref			Object reference
 * @param	int					$entity			Entity id
 * @param	string				$securekey		Key to verify
 * @return	bool
 */
function verifyOnlineSignatureSecureKey($definition, $ref, $entity, $securekey)
{
	$source = empty($definition['source']) ? '' : (string) $definition['source'];
	$securekeyseed = getOnlineSignatureSecuritySeed($definition);

	if ($securekeyseed === '' || strpos($securekeyseed, "\0") !== false || $securekey === '') {
		return false;
	}

	return (bool) dol_verifyHash($securekeyseed.$source.$ref.(isModEnabled('multicompany') ? (int) $entity : ''), $securekey, 'hash');
}

/**
 * Load an object declared as an online signature source.
 *
 * @param	array<string,mixed>	$definition		Source definition
 * @param	string				$ref			Object reference
 * @param	int					$entity			Entity id
 * @return	CommonObject|int					Object if found, 0 if not found, <0 if error
 */
function fetchOnlineSignatureObject($definition, $ref, $entity = 0)
{
	global $db;

	if (!empty($definition['object']) && is_object($definition['object'])) {
		return $definition['object'];
	}

	$source = empty($definition['source']) ? '' : (string) $definition['source'];

	if ($source == 'proposal') {
		require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
		$object = new Propal($db);
		$result = $object->fetch(0, $ref, '', (int) $entity);
		return ($result > 0) ? $object : $result;
	} elseif ($source == 'contract') {
		require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
		$object = new Contrat($db);
		$result = $object->fetch(0, $ref);
		return ($result > 0) ? $object : $result;
	} elseif ($source == 'fichinter') {
		require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
		$object = new Fichinter($db);
		$result = $object->fetch(0, $ref);
		return ($result > 0) ? $object : $result;
	} elseif ($source == 'societe_rib') {
		require_once DOL_DOCUMENT_ROOT.'/societe/class/companybankaccount.class.php';
		$object = new CompanyBankAccount($db);
		$result = $object->fetch(0, $ref);
		return ($result > 0) ? $object : $result;
	} elseif ($source == 'expedition') {
		require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
		$object = new Expedition($db);
		$result = $object->fetch(0, $ref);
		return ($result > 0) ? $object : $result;
	}

	if (empty($definition['elementtype'])) {
		return -1;
	}

	$result = fetchObjectByElement(0, (string) $definition['elementtype'], $ref);
	if (is_object($result)) {
		return $result;
	}

	return (int) $result;
}

/**
 * Return string with full online Url to accept and sign a quote
 *
 * @param   string			$type		Type of URL ('proposal', ...)
 * @param	string			$ref		Ref of object
 * @param   CommonObject 	$obj  		object (needed to make multicompany good links)
 * @param	string			$mode		Mode
 * @return	string						Url string
 */
function showOnlineSignatureUrl($type, $ref, $obj = null, $mode = '')
{
	global $langs;

	// Load translation files required by the page
	$langs->loadLangs(array("payment", "stripe"));

	$servicename = 'Online';

	$out = '';
	if ($mode != 'short') {
		$out .= img_picto('', 'globe', 'class="pictofixedwidth"');
	}
	$out .= '<span class="opacitymedium">'.$langs->trans("ToOfferALinkForOnlineSignature", $servicename).'</span><br>';
	$url = getOnlineSignatureUrl(0, $type, $ref, 1, $obj);
	$out .= '<div class="urllink">';
	if ($url == $langs->trans("FeatureOnlineSignDisabled")) {
		$out .= $url;
	} else {
		$out .= '<input type="text" id="onlinesignatureurl" class="'.($mode == 'short' ? 'centpercentminusx' : 'quatrevingtpercentminusx').'" value="'.$url.'">';
	}
	$out .= '<a class="" href="'.$url.'" target="_blank" rel="noopener noreferrer">'.img_picto('', 'globe', 'class="paddingleft"').'</a>';
	$out .= '</div>';
	$out .= ajax_autoselect("onlinesignatureurl", '');
	return $out;
}


/**
 * Return string with full Url
 *
 * @param   int<0,1>		$mode				0=True url, 1=Url formatted with colors
 * @param   string			$type				Type of URL ('proposal', ...)
 * @param	string			$ref				Ref of object
 * @param   int<0,1>   		$localorexternal  	0=Url for browser, 1=Url for external access
 * @param   ?CommonObject  	$obj  				object (needed to make multicompany good links)
 * @return	string								Url string
 */
function getOnlineSignatureUrl($mode, $type, $ref = '', $localorexternal = 1, $obj = null)
{
	global $dolibarr_main_url_root, $langs;

	if (empty($obj)) {
		// For compatibility with 15.0 -> 19.0
		global $object;
		if (empty($object)) {
			$obj = new stdClass();
		} else {
			dol_syslog(__FUNCTION__." using global object is deprecated, please give obj as argument", LOG_WARNING);
			$obj = $object;
		}
	}

	$out = '';

	// Define $urlwithroot
	$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
	$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
	//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

	$urltouse = DOL_MAIN_URL_ROOT;
	if ($localorexternal) {
		$urltouse = $urlwithroot;
	}

	$entity = (empty($obj->entity) ? 0 : (int) $obj->entity);
	$definition = getOnlineSignatureSourceDefinition($type, $ref, $entity, $obj);
	if (empty($definition) || !isOnlineSignatureSourceEnabled($definition)) {
		return is_object($langs) ? $langs->trans("FeatureOnlineSignDisabled") : 'FeatureOnlineSignDisabled';
	}

	$securekeyseed = getOnlineSignatureSecuritySeed($definition);
	if (strpos($securekeyseed, "\0") !== false) {
		$constname = empty($definition['securekey_const']) ? dol_strtoupper($type).'_ONLINE_SIGNATURE_SECURITY_TOKEN' : (string) $definition['securekey_const'];
		return 'Invalid parameter '.$constname.'. Contains a null character.';
	}

	$source = (string) $definition['source'];
	$out = $urltouse.'/public/onlinesign/newonlinesign.php?source='.urlencode($source).'&ref='.($mode ? '<span style="color: #666666">' : '');
	if ($mode == 1) {
		$out .= dol_escape_htmltag($source).'_ref';
	}
	if ($mode == 0) {
		$out .= urlencode($ref);
	}
	$out .= ($mode ? '</span>' : '');
	if ($mode == 1) {
		$out .= "hash('".$securekeyseed."' + '".$source."' + ".$source."_ref)";
	} else {
		$out .= '&securekey='.urlencode(getOnlineSignatureSecureKey($definition, $ref, $entity));
	}

	// For multicompany
	if (!empty($out) && isModEnabled('multicompany')) {
		$out .= "&entity=".$entity; // Check the entity because we may have the same reference in several entities
	}

	return $out;
}
