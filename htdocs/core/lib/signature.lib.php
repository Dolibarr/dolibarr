<?php
/**
 * Copyright (C) 2013	Marcos García	<marcosgdf@gmail.com>
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
 * Return string with full online Url to accept and sign a quote
 *
 * @param   string	$type		Type of URL ('proposal', ...)
 * @param	string	$ref		Ref of object
 * @return	string				Url string
 */
function showOnlineSignatureUrl($type, $ref)
{
	global $conf, $langs;

	// Load translation files required by the page
	$langs->loadLangs(array("payment", "paybox"));

	$servicename = 'Online';

	$out = img_picto('', 'globe').' <span class="opacitymedium">'.$langs->trans("ToOfferALinkForOnlineSignature", $servicename).'</span><br>';
	$url = getOnlineSignatureUrl(0, $type, $ref);
	$out .= '<div class="urllink">';
	if ($url == $langs->trans("FeatureOnlineSignDisabled")) {
		$out .= $url;
	} else {
		$out .= '<input type="text" id="onlinesignatureurl" class="quatrevingtpercentminusx" value="'.$url.'">';
	}
	$out .= '<a class="" href="'.$url.'" target="_blank" rel="noopener noreferrer">'.img_picto('', 'globe', 'class="paddingleft"').'</a>';
	$out .= '</div>';
	$out .= ajax_autoselect("onlinesignatureurl", 0);
	return $out;
}


/**
 * Return string with full Url
 *
 * @param   int		$mode				0=True url, 1=Url formated with colors
 * @param   string	$type				Type of URL ('proposal', ...)
 * @param	string	$ref				Ref of object
 * @param   string  $localorexternal  	0=Url for browser, 1=Url for external access
 * @return	string						Url string
 */
function getOnlineSignatureUrl($mode, $type, $ref = '', $localorexternal = 1)
{
	global $conf, $db, $langs, $dolibarr_main_url_root;

	$ref = str_replace(' ', '', $ref);
	$out = '';

	// Define $urlwithroot
	$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
	$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
	//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

	$urltouse = DOL_MAIN_URL_ROOT;
	if ($localorexternal) {
		$urltouse = $urlwithroot;
	}

	$securekeyseed = '';

	if ($type == 'proposal') {
		$securekeyseed = isset($conf->global->PROPOSAL_ONLINE_SIGNATURE_SECURITY_TOKEN) ? $conf->global->PROPOSAL_ONLINE_SIGNATURE_SECURITY_TOKEN : '';

		$out = $urltouse.'/public/onlinesign/newonlinesign.php?source=proposal&ref='.($mode ? '<span style="color: #666666">' : '');
		if ($mode == 1) {
			$out .= 'proposal_ref';
		}
		if ($mode == 0) {
			$out .= urlencode($ref);
		}
		$out .= ($mode ? '</span>' : '');
		if ($mode == 1) {
			$out .= "hash('".$securekeyseed."' + '".$type."' + proposal_ref)";
		} else {
			$out .= '&securekey='.dol_hash($securekeyseed.$type.$ref.(empty($conf->multicompany->enabled) ? '' : $object->entity), '0');
		}
		/*
		if ($mode == 1) {
			$out .= '&hashp=<span style="color: #666666">hash_of_file</span>';
		} else {
			include_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
			$propaltmp = new Propal($db);
			$res = $propaltmp->fetch(0, $ref);
			if ($res <= 0) {
				return 'FailedToGetProposal';
			}

			include_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
			$ecmfile = new EcmFiles($db);

			$ecmfile->fetch(0, '', $propaltmp->last_main_doc);

			$hashp = $ecmfile->share;
			if (empty($hashp)) {
				$out = $langs->trans("FeatureOnlineSignDisabled");
				return $out;
			} else {
				$out .= '&hashp='.$hashp;
			}
		}*/
	}

	// For multicompany
	if (!empty($out) && !empty($conf->multicompany->enabled)) {
		$out .= "&entity=".$conf->entity; // Check the entity because we may have the same reference in several entities
	}

	return $out;
}
