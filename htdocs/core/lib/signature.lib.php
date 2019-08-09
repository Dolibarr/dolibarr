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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 * Return string with full Url
 *
 * @param   string	$type		Type of URL ('proposal', ...)
 * @param	string	$ref		Ref of object
 * @return	string				Url string
 */
function showOnlineSignatureUrl($type, $ref)
{
	global $conf, $langs;

	// Load translation files required by the page
    $langs->loadLangs(array("payment","paybox"));

	$servicename='Online';

	$out = img_picto('', 'object_globe.png').' '.$langs->trans("ToOfferALinkForOnlineSignature", $servicename).'<br>';
	$url = getOnlineSignatureUrl(0, $type, $ref);
	$out.= '<input type="text" id="onlinesignatureurl" class="quatrevingtpercent" value="'.$url.'">';
	$out.= ajax_autoselect("onlinesignatureurl", 0);
	return $out;
}


/**
 * Return string with full Url
 *
 * @param   int		$mode		0=True url, 1=Url formated with colors
 * @param   string	$type		Type of URL ('proposal', ...)
 * @param	string	$ref		Ref of object
 * @return	string				Url string
 */
function getOnlineSignatureUrl($mode, $type, $ref = '')
{
	global $conf, $db, $langs;

	$ref=str_replace(' ', '', $ref);
	$out='';

	if ($type == 'proposal')
	{
		$out=DOL_MAIN_URL_ROOT.'/public/onlinesign/newonlinesign.php?source=proposal&ref='.($mode?'<font color="#666666">':'');
		if ($mode == 1) $out.='proposal_ref';
		if ($mode == 0) $out.=urlencode($ref);
		$out.=($mode?'</font>':'');
		if ($mode == 1) $out.='&hashp=<font color="#666666">hash_of_file</font>';
		else
		{
			include_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
			$propaltmp=new Propal($db);
			$res = $propaltmp->fetch(0, $ref);
			if ($res <= 0) return 'FailedToGetProposal';

			include_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
			$ecmfile=new EcmFiles($db);

			$ecmfile->fetch(0, '', $propaltmp->last_main_doc);

			$hashp=$ecmfile->share;
			if (empty($hashp))
			{
				$out = $langs->trans("FeatureOnlineSignDisabled");
				return $out;
			}
			else
			{
				$out.='&hashp='.$hashp;
			}
		}
	}

	// For multicompany
	if (! empty($out)) $out.="&entity=".$conf->entity; // Check the entity because He may be the same reference in several entities

	return $out;
}
