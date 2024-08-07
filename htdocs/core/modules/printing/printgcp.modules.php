<?php
/*
 * Copyright (C) 2014-2019  Frédéric France      <frederic.france@netlogic.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *      \file       htdocs/core/modules/printing/printgcp.modules.php
 *      \ingroup    printing
 *      \brief      File to provide printing with Google Cloud Print
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/printing/modules_printing.php';
require_once DOL_DOCUMENT_ROOT.'/includes/OAuth/bootstrap.php';

use OAuth\Common\Storage\DoliStorage;
use OAuth\Common\Consumer\Credentials;

/**
 *     Class to provide printing with Google Cloud Print
 */
class printing_printgcp extends PrintingDriver
{
	/**
	 * @var string module name
	 */
	public $name = 'printgcp';

	/**
	 * @var string module description
	 */
	public $desc = 'PrintGCPDesc';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'printer';

	/**
	 * @var string module description
	 */
	public $active = 'PRINTING_PRINTGCP';

	/**
	 * @var array module parameters
	 */
	public $conf = array();

	/**
	 * @var string google id
	 */
	public $google_id = '';

	/**
	 * @var string google secret
	 */
	public $google_secret = '';

	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	private $OAUTH_SERVICENAME_GOOGLE = 'Google';

	const LOGIN_URL = 'https://accounts.google.com/o/oauth2/token';
	const PRINTERS_SEARCH_URL = 'https://www.google.com/cloudprint/search';
	const PRINTERS_GET_JOBS = 'https://www.google.com/cloudprint/jobs';
	const PRINT_URL = 'https://www.google.com/cloudprint/submit';


	/**
	 *  Constructor
	 *
	 *  @param      DoliDB      $db      Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs, $dolibarr_main_url_root;

		// Define $urlwithroot
		$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
		$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
		//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

		$this->db = $db;

		if (!$conf->oauth->enabled) {
			$this->conf[] = array(
				'varname' => 'PRINTGCP_INFO',
				'info' => $langs->transnoentitiesnoconv("WarningModuleNotActive", "OAuth"),
				'type' => 'info',
			);
		} else {
			$keyforprovider = '';	// @FIXME

			$this->google_id = getDolGlobalString('OAUTH_GOOGLE_ID');
			$this->google_secret = getDolGlobalString('OAUTH_GOOGLE_SECRET');
			// Token storage
			$storage = new DoliStorage($this->db, $conf, $keyforprovider);
			//$storage->clearToken($this->OAUTH_SERVICENAME_GOOGLE);
			// Setup the credentials for the requests
			$credentials = new Credentials(
				$this->google_id,
				$this->google_secret,
				$urlwithroot.'/core/modules/oauth/google_oauthcallback.php'
			);
			$access = ($storage->hasAccessToken($this->OAUTH_SERVICENAME_GOOGLE) ? 'HasAccessToken' : 'NoAccessToken');
			$serviceFactory = new \OAuth\ServiceFactory();
			$apiService = $serviceFactory->createService($this->OAUTH_SERVICENAME_GOOGLE, $credentials, $storage, array());
			$token_ok = true;
			try {
				$token = $storage->retrieveAccessToken($this->OAUTH_SERVICENAME_GOOGLE);
			} catch (Exception $e) {
				$this->errors[] = $e->getMessage();
				$token_ok = false;
			}

			$expire = false;
			// Is token expired or will token expire in the next 30 seconds
			if ($token_ok) {
				$expire = ($token->getEndOfLife() !== -9002 && $token->getEndOfLife() !== -9001 && time() > ($token->getEndOfLife() - 30));
			}

			// Token expired so we refresh it
			if ($token_ok && $expire) {
				try {
					// il faut sauvegarder le refresh token car google ne le donne qu'une seule fois
					$refreshtoken = $token->getRefreshToken();
					$token = $apiService->refreshAccessToken($token);
					$token->setRefreshToken($refreshtoken);
					$storage->storeAccessToken($this->OAUTH_SERVICENAME_GOOGLE, $token);
				} catch (Exception $e) {
					$this->errors[] = $e->getMessage();
				}
			}
			if ($this->google_id != '' && $this->google_secret != '') {
				$this->conf[] = array('varname' => 'PRINTGCP_INFO', 'info' => 'GoogleAuthConfigured', 'type' => 'info');
				$this->conf[] = array(
					'varname' => 'PRINTGCP_TOKEN_ACCESS',
					'info' => $access,
					'type' => 'info',
					'renew' => $urlwithroot.'/core/modules/oauth/google_oauthcallback.php?state=userinfo_email,userinfo_profile,cloud_print&backtourl='.urlencode(DOL_URL_ROOT.'/printing/admin/printing.php?mode=setup&driver=printgcp'),
					'delete' => ($storage->hasAccessToken($this->OAUTH_SERVICENAME_GOOGLE) ? $urlwithroot.'/core/modules/oauth/google_oauthcallback.php?action=delete&token='.newToken().'&backtourl='.urlencode(DOL_URL_ROOT.'/printing/admin/printing.php?mode=setup&driver=printgcp') : '')
				);
				if ($token_ok) {
					$expiredat = '';

					$refreshtoken = $token->getRefreshToken();

					$endoflife = $token->getEndOfLife();

					if ($endoflife == $token::EOL_NEVER_EXPIRES) {
						$expiredat = $langs->trans("Never");
					} elseif ($endoflife == $token::EOL_UNKNOWN) {
						$expiredat = $langs->trans("Unknown");
					} else {
						$expiredat = dol_print_date($endoflife, "dayhour");
					}

					$this->conf[] = array('varname' => 'TOKEN_REFRESH', 'info' => ((!empty($refreshtoken)) ? 'Yes' : 'No'), 'type' => 'info');
					$this->conf[] = array('varname' => 'TOKEN_EXPIRED', 'info' => ($expire ? 'Yes' : 'No'), 'type' => 'info');
					$this->conf[] = array('varname' => 'TOKEN_EXPIRE_AT', 'info' => ($expiredat), 'type' => 'info');
				}
				/*
				if ($storage->hasAccessToken($this->OAUTH_SERVICENAME_GOOGLE)) {
					$this->conf[] = array('varname'=>'PRINTGCP_AUTHLINK', 'link'=>$urlwithroot.'/core/modules/oauth/google_oauthcallback.php?backtourl='.urlencode(DOL_URL_ROOT.'/printing/admin/printing.php?mode=setup&driver=printgcp'), 'type'=>'authlink');
					$this->conf[] = array('varname'=>'DELETE_TOKEN', 'link'=>$urlwithroot.'/core/modules/oauth/google_oauthcallback.php?action=delete&token='.newToken().'&backtourl='.urlencode(DOL_URL_ROOT.'/printing/admin/printing.php?mode=setup&driver=printgcp'), 'type'=>'delete');
				} else {
					$this->conf[] = array('varname'=>'PRINTGCP_AUTHLINK', 'link'=>$urlwithroot.'/core/modules/oauth/google_oauthcallback.php?backtourl='.urlencode(DOL_URL_ROOT.'/printing/admin/printing.php?mode=setup&driver=printgcp'), 'type'=>'authlink');
				}*/
			} else {
				$this->conf[] = array('varname' => 'PRINTGCP_INFO', 'info' => 'GoogleAuthNotConfigured', 'type' => 'info');
			}
		}
		// do not display submit button
		$this->conf[] = array('enabled' => 0, 'type' => 'submit');
	}

	/**
	 *  Return list of available printers
	 *
	 *  @return  int                     0 if OK, >0 if KO
	 */
	public function listAvailablePrinters()
	{
		global $conf, $langs;
		$error = 0;
		$langs->load('printing');

		$html = '<tr class="liste_titre">';
		$html .= '<td>'.$langs->trans('GCP_Name').'</td>';
		$html .= '<td>'.$langs->trans('GCP_displayName').'</td>';
		$html .= '<td>'.$langs->trans('GCP_Id').'</td>';
		$html .= '<td>'.$langs->trans('GCP_OwnerName').'</td>';
		$html .= '<td>'.$langs->trans('GCP_State').'</td>';
		$html .= '<td>'.$langs->trans('GCP_connectionStatus').'</td>';
		$html .= '<td>'.$langs->trans('GCP_Type').'</td>';
		$html .= '<td class="center">'.$langs->trans("Select").'</td>';
		$html .= '</tr>'."\n";
		$list = $this->getlistAvailablePrinters();
		//$html.= '<td><pre>'.print_r($list,true).'</pre></td>';
		foreach ($list['available'] as $printer_det) {
			$html .= '<tr class="oddeven">';
			$html .= '<td>'.$printer_det['name'].'</td>';
			$html .= '<td>'.$printer_det['displayName'].'</td>';
			$html .= '<td>'.$printer_det['id'].'</td>'; // id to identify printer to use
			$html .= '<td>'.$printer_det['ownerName'].'</td>';
			$html .= '<td>'.$printer_det['status'].'</td>';
			$html .= '<td>'.$langs->trans('STATE_'.$printer_det['connectionStatus']).'</td>';
			$html .= '<td>'.$langs->trans('TYPE_'.$printer_det['type']).'</td>';
			// Default
			$html .= '<td class="center">';
			if ($conf->global->PRINTING_GCP_DEFAULT == $printer_det['id']) {
				$html .= img_picto($langs->trans("Default"), 'on');
			} else {
				$html .= '<a href="'.$_SERVER["PHP_SELF"].'?action=setvalue&token='.newToken().'&mode=test&varname=PRINTING_GCP_DEFAULT&driver=printgcp&value='.urlencode($printer_det['id']).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
			}
			$html .= '</td>';
			$html .= '</tr>'."\n";
		}
		$this->resprint = $html;
		return $error;
	}


	/**
	 *  Return list of available printers
	 *
	 *  @return array{available:array}	list of printers
	 */
	public function getlistAvailablePrinters()
	{
		global $conf;
		$ret = array();

		$keyforprovider = '';	// @FIXME

		// Token storage
		$storage = new DoliStorage($this->db, $conf, $keyforprovider);
		// Setup the credentials for the requests
		$credentials = new Credentials(
			$this->google_id,
			$this->google_secret,
			DOL_MAIN_URL_ROOT.'/core/modules/oauth/google_oauthcallback.php'
		);
		$serviceFactory = new \OAuth\ServiceFactory();
		$apiService = $serviceFactory->createService($this->OAUTH_SERVICENAME_GOOGLE, $credentials, $storage, array());
		// Check if we have auth token
		$token_ok = true;
		try {
			$token = $storage->retrieveAccessToken($this->OAUTH_SERVICENAME_GOOGLE);
		} catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$token_ok = false;
		}
		$expire = false;
		// Is token expired or will token expire in the next 30 seconds
		if ($token_ok) {
			$expire = ($token->getEndOfLife() !== -9002 && $token->getEndOfLife() !== -9001 && time() > ($token->getEndOfLife() - 30));
		}

		// Token expired so we refresh it
		if ($token_ok && $expire) {
			try {
				// il faut sauvegarder le refresh token car google ne le donne qu'une seule fois
				$refreshtoken = $token->getRefreshToken();
				$token = $apiService->refreshAccessToken($token);
				$token->setRefreshToken($refreshtoken);
				$storage->storeAccessToken($this->OAUTH_SERVICENAME_GOOGLE, $token);
			} catch (Exception $e) {
				$this->errors[] = $e->getMessage();
			}
		}
		// Send a request with api
		try {
			$response = $apiService->request(self::PRINTERS_SEARCH_URL);
		} catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			print '<pre>'.print_r($e->getMessage(), true).'</pre>';
		}
		//print '<tr><td><pre>'.print_r($response, true).'</pre></td></tr>';
		$responsedata = json_decode($response, true);
		$printers = $responsedata['printers'];
		// Check if we have printers?
		if (is_array($printers) && count($printers) == 0) {
			// We don't have printers so return blank array
			$ret['available'] = array();
		} else {
			// We have printers so returns printers as array
			$ret['available'] = $printers;
		}
		return $ret;
	}

	/**
	 *  Print selected file
	 *
	 * @param   string      $file       file
	 * @param   string      $module     module
	 * @param   string      $subdir     subdir for file
	 * @return  int                     0 if OK, >0 if KO
	 */
	public function printFile($file, $module, $subdir = '')
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		global $conf, $user;
		$error = 0;

		$fileprint = $conf->{$module}->dir_output;
		if ($subdir != '') {
			$fileprint .= '/'.$subdir;
		}
		$fileprint .= '/'.$file;
		$mimetype = dol_mimetype($fileprint);
		$printer_id = '';
		// select printer uri for module order, propal,...
		$sql = "SELECT rowid, printer_id, copy FROM ".MAIN_DB_PREFIX."printing WHERE module='".$this->db->escape($module)."' AND driver='printgcp' AND userid=".((int) $user->id);
		$result = $this->db->query($sql);
		if ($result) {
			$obj = $this->db->fetch_object($result);
			if ($obj) {
				$printer_id = $obj->printer_id;
			} else {
				if (getDolGlobalString('PRINTING_GCP_DEFAULT')) {
					$printer_id = getDolGlobalString('PRINTING_GCP_DEFAULT');
				} else {
					$this->errors[] = 'NoDefaultPrinterDefined';
					$error++;
					return $error;
				}
			}
		} else {
			dol_print_error($this->db);
		}

		$ret = $this->sendPrintToPrinter($printer_id, $file, $fileprint, $mimetype);
		$this->error = 'PRINTGCP: '.$ret['errormessage'];
		if ($ret['status'] != 1) {
			$error++;
		}
		return $error;
	}

	/**
	 *  Sends document to the printer
	 *
	 *  @param  string      $printerid      Printer id returned by Google Cloud Print
	 *  @param  string      $printjobtitle  Job Title
	 *  @param  string      $filepath       File Path to be send to Google Cloud Print
	 *  @param  string      $contenttype    File content type by example application/pdf, image/png
	 *  @return array                       status array
	 */
	public function sendPrintToPrinter($printerid, $printjobtitle, $filepath, $contenttype)
	{
		global $conf;
		// Check if printer id
		if (empty($printerid)) {
			return array('status' => 0, 'errorcode' => '', 'errormessage' => 'No provided printer ID');
		}
		// Open the file which needs to be print
		$handle = fopen($filepath, "rb");
		if (!$handle) {
			return array('status' => 0, 'errorcode' => '', 'errormessage' => 'Could not read the file.');
		}
		// Read file content
		$contents = fread($handle, filesize($filepath));
		fclose($handle);
		// Prepare post fields for sending print
		$post_fields = array(
			'printerid' => $printerid,
			'title' => $printjobtitle,
			'contentTransferEncoding' => 'base64',
			'content' => base64_encode($contents), // encode file content as base64
			'contentType' => $contenttype,
		);

		$keyforprovider = '';	// @FIXME

		// Dolibarr Token storage
		$storage = new DoliStorage($this->db, $conf, $keyforprovider);
		// Setup the credentials for the requests
		$credentials = new Credentials(
			$this->google_id,
			$this->google_secret,
			DOL_MAIN_URL_ROOT.'/core/modules/oauth/google_oauthcallback.php?service=google'
		);
		$serviceFactory = new \OAuth\ServiceFactory();
		$apiService = $serviceFactory->createService($this->OAUTH_SERVICENAME_GOOGLE, $credentials, $storage, array());

		// Check if we have auth token and refresh it
		$token_ok = true;
		try {
			$token = $storage->retrieveAccessToken($this->OAUTH_SERVICENAME_GOOGLE);
		} catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$token_ok = false;
		}
		if ($token_ok) {
			try {
				// il faut sauvegarder le refresh token car google ne le donne qu'une seule fois
				$refreshtoken = $token->getRefreshToken();
				$token = $apiService->refreshAccessToken($token);
				$token->setRefreshToken($refreshtoken);
				$storage->storeAccessToken($this->OAUTH_SERVICENAME_GOOGLE, $token);
			} catch (Exception $e) {
				$this->errors[] = $e->getMessage();
			}
		}

		// Send a request with api
		$response = json_decode($apiService->request(self::PRINT_URL, 'POST', $post_fields), true);
		//print '<tr><td><pre>'.print_r($response, true).'</pre></td></tr>';
		return array('status' => $response['success'], 'errorcode' => $response['errorCode'], 'errormessage' => $response['message']);
	}


	/**
	 *  List jobs print
	 *
	 *  @return  int                     0 if OK, >0 if KO
	 */
	public function listJobs()
	{
		global $conf, $langs;

		$error = 0;
		$html = '';

		$keyforprovider = '';	// @FIXME

		// Token storage
		$storage = new DoliStorage($this->db, $conf, $keyforprovider);
		// Setup the credentials for the requests
		$credentials = new Credentials(
			$this->google_id,
			$this->google_secret,
			DOL_MAIN_URL_ROOT.'/core/modules/oauth/google_oauthcallback.php'
		);
		$serviceFactory = new \OAuth\ServiceFactory();
		$apiService = $serviceFactory->createService($this->OAUTH_SERVICENAME_GOOGLE, $credentials, $storage, array());
		// Check if we have auth token
		$token_ok = true;
		try {
			$token = $storage->retrieveAccessToken($this->OAUTH_SERVICENAME_GOOGLE);
		} catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$token_ok = false;
			$error++;
		}
		$expire = false;
		// Is token expired or will token expire in the next 30 seconds
		if ($token_ok) {
			$expire = ($token->getEndOfLife() !== -9002 && $token->getEndOfLife() !== -9001 && time() > ($token->getEndOfLife() - 30));
		}

		// Token expired so we refresh it
		if ($token_ok && $expire) {
			try {
				// il faut sauvegarder le refresh token car google ne le donne qu'une seule fois
				$refreshtoken = $token->getRefreshToken();
				$token = $apiService->refreshAccessToken($token);
				$token->setRefreshToken($refreshtoken);
				$storage->storeAccessToken($this->OAUTH_SERVICENAME_GOOGLE, $token);
			} catch (Exception $e) {
				$this->errors[] = $e->getMessage();
				$error++;
			}
		}
		// Getting Jobs
		// Send a request with api
		try {
			$response = $apiService->request(self::PRINTERS_GET_JOBS);
		} catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			$error++;
		}
		$responsedata = json_decode($response, true);
		//$html .= '<pre>'.print_r($responsedata,true).'</pre>';
		$html .= '<div class="div-table-responsive">';
		$html .= '<table width="100%" class="noborder">';
		$html .= '<tr class="liste_titre">';
		$html .= '<td>'.$langs->trans("Id").'</td>';
		$html .= '<td>'.$langs->trans("Date").'</td>';
		$html .= '<td>'.$langs->trans("Owner").'</td>';
		$html .= '<td>'.$langs->trans("Printer").'</td>';
		$html .= '<td>'.$langs->trans("Filename").'</td>';
		$html .= '<td>'.$langs->trans("Status").'</td>';
		$html .= '<td>'.$langs->trans("Cancel").'</td>';
		$html .= '</tr>'."\n";

		$jobs = $responsedata['jobs'];
		//$html .= '<pre>'.print_r($jobs['0'],true).'</pre>';
		if (is_array($jobs)) {
			foreach ($jobs as $value) {
				$html .= '<tr class="oddeven">';
				$html .= '<td>'.$value['id'].'</td>';
				$dates = dol_print_date((int) substr($value['createTime'], 0, 10), 'dayhour');
				$html .= '<td>'.$dates.'</td>';
				$html .= '<td>'.$value['ownerId'].'</td>';
				$html .= '<td>'.$value['printerName'].'</td>';
				$html .= '<td>'.$value['title'].'</td>';
				$html .= '<td>'.$value['status'].'</td>';
				$html .= '<td>&nbsp;</td>';
				$html .= '</tr>';
			}
		} else {
			$html .= '<tr class="oddeven">';
			$html .= '<td colspan="7" class="opacitymedium">'.$langs->trans("None").'</td>';
			$html .= '</tr>';
		}
		$html .= '</table>';
		$html .= '</div>';

		$this->resprint = $html;

		return $error;
	}
}
