<?php
/* Copyright (C) 2016   Xebax Christy           <xebax@wanadoo.fr>
 * Copyright (C) 2016	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2016   Jean-François Ferry     <jfefe@aternatik.fr>
 *
 * This program is free software you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

use Luracast\Restler\RestException;
use Luracast\Restler\Format\UploadFormat;


require_once DOL_DOCUMENT_ROOT.'/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

/**
 * API class for receive files
 *
 * @access protected
 * @class Documents {@requires user,external}
 */
class Documents extends DolibarrApi
{

	/**
	 * @var array   $DOCUMENT_FIELDS     Mandatory fields, checked when create and update object
	 */
	static $DOCUMENT_FIELDS = array(
		'modulepart'
	);

	/**
	 * Constructor
	 */
	function __construct()
	{
		global $db;
		$this->db = $db;
	}


	/**
	 * Download a document.
	 *
	 * Note that, this API is similar to using the wrapper link "documents.php" to download a file (used for
	 * internal HTML links of documents into application), but with no need to have a session cookie (the token is used instead).
	 *
	 * @param   string  $module_part    Name of module or area concerned by file download ('facture', ...)
	 * @param   string  $original_file  Relative path with filename, relative to modulepart (for example: IN201701-999/IN201701-999.pdf)
	 * @return  array                   List of documents
	 *
	 * @throws 400
	 * @throws 401
	 * @throws 404
	 * @throws 200
	 *
	 * @url GET /download
	 */
	public function index($module_part, $original_file='')
	{
		global $conf, $langs;

		if (empty($module_part)) {
				throw new RestException(400, 'bad value for parameter modulepart');
		}
		if (empty($original_file)) {
			throw new RestException(400, 'bad value for parameter original_file');
		}

		//--- Finds and returns the document
		$entity=$conf->entity;

		$check_access = dol_check_secure_access_document($module_part, $original_file, $entity, DolibarrApiAccess::$user, '', 'read');
		$accessallowed              = $check_access['accessallowed'];
		$sqlprotectagainstexternals = $check_access['sqlprotectagainstexternals'];
		$original_file              = $check_access['original_file'];

		if (preg_match('/\.\./',$original_file) || preg_match('/[<>|]/',$original_file))
		{
			throw new RestException(401);
		}
		if (!$accessallowed) {
			throw new RestException(401);
		}

		$filename = basename($original_file);
		$original_file_osencoded=dol_osencode($original_file);	// New file name encoded in OS encoding charset

		if (! file_exists($original_file_osencoded))
		{
			throw new RestException(404, 'File not found');
		}

		$file_content=file_get_contents($original_file_osencoded);
		return array('filename'=>$filename, 'content-type' => dol_mimetype($filename), 'filesize'=>filesize($original_file), 'content'=>base64_encode($file_content), 'encoding'=>'base64' );
	}


	/**
	 * Build a document.
	 *
	 * Test sample 1: { "module_part": "invoice", "original_file": "FA1701-001/FA1701-001.pdf", "doctemplate": "crabe", "langcode": "fr_FR" }.
	 *
	 * @param   string  $module_part    Name of module or area concerned by file download ('invoice', 'order', ...).
	 * @param   string  $original_file  Relative path with filename, relative to modulepart (for example: IN201701-999/IN201701-999.pdf).
	 * @param	string	$doctemplate	Set here the doc template to use for document generation (If not set, use the default template).
	 * @param	string	$langcode		Language code like 'en_US', 'fr_FR', 'es_ES', ... (If not set, use the default language).
	 * @return  array                   List of documents
	 *
	 * @throws 500
	 * @throws 501
	 * @throws 400
	 * @throws 401
	 * @throws 404
	 * @throws 200
	 *
	 * @url PUT /builddoc
	 */
	public function builddoc($module_part, $original_file='', $doctemplate='', $langcode='')
	{
		global $conf, $langs;

		if (empty($module_part)) {
			throw new RestException(400, 'bad value for parameter modulepart');
		}
		if (empty($original_file)) {
			throw new RestException(400, 'bad value for parameter original_file');
		}

		$outputlangs = $langs;
		if ($langcode && $langs->defaultlang != $langcode)
		{
			$outputlangs=new Translate('', $conf);
			$outputlangs->setDefaultLang($langcode);
		}

		//--- Finds and returns the document
		$entity=$conf->entity;

		$check_access = dol_check_secure_access_document($module_part, $original_file, $entity, DolibarrApiAccess::$user, '', 'write');
		$accessallowed              = $check_access['accessallowed'];
		$sqlprotectagainstexternals = $check_access['sqlprotectagainstexternals'];
		$original_file              = $check_access['original_file'];

		if (preg_match('/\.\./',$original_file) || preg_match('/[<>|]/',$original_file)) {
			throw new RestException(401);
		}
		if (!$accessallowed) {
			throw new RestException(401);
		}

		// --- Generates the document
		$hidedetails = empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 0 : 1;
		$hidedesc = empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ? 0 : 1;
		$hideref = empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 0 : 1;

		$templateused='';

		if ($module_part == 'facture' || $module_part == 'invoice')
		{
			require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
			$this->invoice = new Facture($this->db);
			$result = $this->invoice->fetch(0, preg_replace('/\.[^\.]+$/', '', basename($original_file)));
			if( ! $result ) {
				throw new RestException(404, 'Invoice not found');
			}

			$templateused = $doctemplate?$doctemplate:$this->invoice->modelpdf;
			$result = $this->invoice->generateDocument($templateused, $outputlangs, $hidedetails, $hidedesc, $hideref);
			if( $result <= 0 ) {
				throw new RestException(500, 'Error generating document');
			}
		}
		elseif ($module_part == 'commande' || $module_part == 'order')
		{
			require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
			$this->order = new Commande($this->db);
			$result = $this->order->fetch(0, preg_replace('/\.[^\.]+$/', '', basename($original_file)));
			if( ! $result ) {
				throw new RestException(404, 'Order not found');
			}
			$templateused = $doctemplate?$doctemplate:$this->order->modelpdf;
			$result = $this->order->generateDocument($templateused, $outputlangs, $hidedetails, $hidedesc, $hideref);
			if( $result <= 0 ) {
				throw new RestException(500, 'Error generating document');
			}
		}
		elseif ($module_part == 'propal' || $module_part == 'proposal')
		{
			require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
			$this->propal = new Propal($this->db);
			$result = $this->propal->fetch(0, preg_replace('/\.[^\.]+$/', '', basename($original_file)));
			if( ! $result ) {
				throw new RestException(404, 'Proposal not found');
			}
			$templateused = $doctemplate?$doctemplate:$this->propal->modelpdf;
			$result = $this->propal->generateDocument($templateused, $outputlangs, $hidedetails, $hidedesc, $hideref);
			if( $result <= 0 ) {
				throw new RestException(500, 'Error generating document');
			}
		}
		else
		{
			throw new RestException(403, 'Generation not available for this modulepart');
		}

		$filename = basename($original_file);
		$original_file_osencoded=dol_osencode($original_file);	// New file name encoded in OS encoding charset

		if (! file_exists($original_file_osencoded))
		{
			throw new RestException(404, 'File not found');
		}

		$file_content=file_get_contents($original_file_osencoded);
		return array('filename'=>$filename, 'content-type' => dol_mimetype($filename), 'filesize'=>filesize($original_file), 'content'=>base64_encode($file_content), 'langcode'=>$outputlangs->defaultlang, 'template'=>$templateused, 'encoding'=>'base64' );
	}

	/**
	 * Return the list of documents of a dedicated element (from its ID or Ref)
	 *
	 * @param   string 	$modulepart		Name of module or area concerned ('thirdparty', 'member', 'proposal', 'order', 'invoice', 'shipment', 'project',  ...)
	 * @param	int		$id				ID of element
	 * @param	string	$ref			Ref of element
	 * @param	string	$sortfield		Sort criteria ('','fullname','relativename','name','date','size')
	 * @param	string	$sortorder		Sort order ('asc' or 'desc')
	 * @return	array					Array of documents with path
	 *
	 * @throws 200
	 * @throws 400
	 * @throws 401
	 * @throws 404
	 * @throws 500
	 *
	 * @url GET /
	 */
	function getDocumentsListByElement($modulepart, $id=0, $ref='', $sortfield='', $sortorder='')
	{
		global $conf;

		if (empty($modulepart)) {
			throw new RestException(400, 'bad value for parameter modulepart');
		}

		if (empty($id) && empty($ref)) {
			throw new RestException(400, 'bad value for parameter id or ref');
		}

		$id = (empty($id)?0:$id);

		if ($modulepart == 'societe' || $modulepart == 'thirdparty')
		{
			require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

			if (!DolibarrApiAccess::$user->rights->societe->lire) {
				throw new RestException(401);
			}

			$object = new Societe($this->db);
			$result=$object->fetch($id, $ref);
			if ( ! $result ) {
				throw new RestException(404, 'Thirdparty not found');
			}

			$upload_dir = $conf->societe->multidir_output[$object->entity] . "/" . $object->id;
		}
		else if ($modulepart == 'adherent' || $modulepart == 'member')
		{
			require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';

			if (!DolibarrApiAccess::$user->rights->adherent->lire) {
				throw new RestException(401);
			}

			$object = new Adherent($this->db);
			$result=$object->fetch($id, $ref);
			if ( ! $result ) {
				throw new RestException(404, 'Member not found');
			}

			$upload_dir = $conf->adherent->dir_output . "/" . get_exdir(0, 0, 0, 1, $object, 'member');
		}
		else if ($modulepart == 'propal' || $modulepart == 'proposal')
		{
			require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';

			if (!DolibarrApiAccess::$user->rights->propal->lire) {
				throw new RestException(401);
			}

			$object = new Propal($this->db);
			$result=$object->fetch($id, $ref);
			if ( ! $result ) {
				throw new RestException(404, 'Proposal not found');
			}

			$upload_dir = $conf->propal->multidir_output[$object->entity] . "/" . get_exdir(0, 0, 0, 1, $object, 'propal');
		}
		else if ($modulepart == 'commande' || $modulepart == 'order')
		{
			require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

			if (!DolibarrApiAccess::$user->rights->commande->lire) {
				throw new RestException(401);
			}

			$object = new Commande($this->db);
			$result=$object->fetch($id, $ref);
			if ( ! $result ) {
				throw new RestException(404, 'Order not found');
			}

			$upload_dir = $conf->commande->dir_output . "/" . get_exdir(0, 0, 0, 1, $object, 'commande');
		}
		else if ($modulepart == 'shipment' || $modulepart == 'expedition')
		{
			require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';

			if (!DolibarrApiAccess::$user->rights->expedition->lire) {
				throw new RestException(401);
			}

			$object = new Expedition($this->db);
			$result=$object->fetch($id, $ref);
			if ( ! $result ) {
				throw new RestException(404, 'Shipment not found');
			}

			$upload_dir = $conf->expedition->dir_output . "/sending/" . get_exdir(0, 0, 0, 1, $object, 'shipment');
		}
		else if ($modulepart == 'facture' || $modulepart == 'invoice')
		{
			require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

			if (!DolibarrApiAccess::$user->rights->facture->lire) {
				throw new RestException(401);
			}

			$object = new Facture($this->db);
			$result=$object->fetch($id, $ref);
			if ( ! $result ) {
				throw new RestException(404, 'Invoice not found');
			}

			$upload_dir = $conf->facture->dir_output . "/" . get_exdir(0, 0, 0, 1, $object, 'invoice');
		}
		else if ($modulepart == 'agenda' || $modulepart == 'action' || $modulepart == 'event')
		{
			require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

			if (!DolibarrApiAccess::$user->rights->agenda->myactions->read && !DolibarrApiAccess::$user->rights->agenda->allactions->read) {
				throw new RestException(401);
			}

			$object = new ActionComm($this->db);
			$result=$object->fetch($id, $ref);
			if ( ! $result ) {
				throw new RestException(404, 'Event not found');
			}

			$upload_dir = $conf->agenda->dir_output.'/'.dol_sanitizeFileName($object->ref);
		}
		else
		{
			throw new RestException(500, 'Modulepart '.$modulepart.' not implemented yet.');
		}

		$filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview.*\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
		if (empty($filearray)) {
			throw new RestException(404, 'Search for modulepart '.$modulepart.' with Id '.$object->id.(! empty($object->Ref)?' or Ref '.$object->ref:'').' does not return any document.');
		}

		return $filearray;
	}


	/**
	 * Return a document.
	 *
	 * @param   int         $id          ID of document
	 * @return  array                    Array with data of file
	 *
	 * @throws RestException
	 */
	/*
    public function get($id) {
        return array('note'=>'xxx');
    }*/


	/**
	 * Upload a file.
	 *
	 * Test sample 1: { "filename": "mynewfile.txt", "modulepart": "facture", "ref": "FA1701-001", "subdir": "", "filecontent": "content text", "fileencoding": "", "overwriteifexists": "0" }.
	 * Test sample 2: { "filename": "mynewfile.txt", "modulepart": "medias", "ref": "", "subdir": "image/mywebsite", "filecontent": "Y29udGVudCB0ZXh0Cg==", "fileencoding": "base64", "overwriteifexists": "0" }.
	 *
	 * @param   string  $filename           Name of file to create ('FA1705-0123.txt')
	 * @param   string  $modulepart         Name of module or area concerned by file upload ('facture', 'project', 'project_task', ...)
	 * @param   string  $ref                Reference of object (This will define subdir automatically and store submited file into it)
	 * @param   string  $subdir       		Subdirectory (Only if ref not provided)
	 * @param   string  $filecontent        File content (string with file content. An empty file will be created if this parameter is not provided)
	 * @param   string  $fileencoding       File encoding (''=no encoding, 'base64'=Base 64) {@example '' or 'base64'}
	 * @param   int 	$overwriteifexists  Overwrite file if exists (1 by default)
	 *
	 * @throws 200
	 * @throws 400
	 * @throws 401
	 * @throws 404
	 * @throws 500
	 *
	 * @url POST /upload
	 */
	public function post($filename, $modulepart, $ref='', $subdir='', $filecontent='', $fileencoding='', $overwriteifexists=0)
	{
		global $db, $conf;

		/*var_dump($modulepart);
        var_dump($filename);
        var_dump($filecontent);
        exit;*/

		if(empty($modulepart))
		{
			throw new RestException(400, 'Modulepart not provided.');
		}

		if (!DolibarrApiAccess::$user->rights->ecm->upload) {
			throw new RestException(401);
		}

		$newfilecontent = '';
		if (empty($fileencoding)) $newfilecontent = $filecontent;
		if ($fileencoding == 'base64') $newfilecontent = base64_decode($filecontent);

		$original_file = dol_sanitizeFileName($filename);

		// Define $uploadir
		$object = null;
		$entity = DolibarrApiAccess::$user->entity;
		if ($ref)
		{
			$tmpreldir='';

			if ($modulepart == 'facture' || $modulepart == 'invoice')
			{
				$modulepart='facture';

				require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
				$object = new Facture($this->db);
			}
			elseif ($modulepart == 'project')
			{
				require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
				$object = new Project($this->db);
			}
			elseif ($modulepart == 'task' || $modulepart == 'project_task')
			{
				$modulepart = 'project_task';

				require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
				$object = new Task($this->db);

				$task_result = $object->fetch('', $ref);

				// Fetching the tasks project is required because its out_dir might be a sub-directory of the project
				if($task_result > 0)
				{
					$project_result = $object->fetch_projet();

					if($project_result >= 0)
					{
						$tmpreldir = dol_sanitizeFileName($object->project->ref).'/';
					}
				}
				else
				{
					throw new RestException(500, 'Error while fetching Task '.$ref);
				}
			}
			// TODO Implement additional moduleparts
			else
			{
				throw new RestException(500, 'Modulepart '.$modulepart.' not implemented yet.');
			}

			if(is_object($object))
			{
				$result = $object->fetch('', $ref);

				if($result == 0)
				{
					throw new RestException(404, "Object with ref '".$ref."' was not found.");
			}
				elseif ($result < 0)
				{
					throw new RestException(500, 'Error while fetching object.');
				}
			}

			if (! ($object->id > 0))
			{
   				throw new RestException(404, 'The object '.$modulepart." with ref '".$ref."' was not found.");
			}

			$relativefile = $tmpreldir.dol_sanitizeFileName($object->ref);

			$tmp = dol_check_secure_access_document($modulepart, $relativefile, $entity, DolibarrApiAccess::$user, $ref, 'write');
			$upload_dir = $tmp['original_file'];	// No dirname here, tmp['original_file'] is already the dir because dol_check_secure_access_document was called with param original_file that is only the dir

			if (empty($upload_dir) || $upload_dir == '/')
			{
				throw new RestException(500, 'This value of modulepart does not support yet usage of ref. Check modulepart parameter or try to use subdir parameter instead of ref.');
			}
		}
		else
		{
			if ($modulepart == 'invoice') $modulepart ='facture';

			$relativefile = $subdir;

			$tmp = dol_check_secure_access_document($modulepart, $relativefile, $entity, DolibarrApiAccess::$user, '', 'write');
			$upload_dir = $tmp['original_file'];	// No dirname here, tmp['original_file'] is already the dir because dol_check_secure_access_document was called with param original_file that is only the dir

			if (empty($upload_dir) || $upload_dir == '/')
			{
				throw new RestException(500, 'This value of modulepart does not support yet usage of ref. Check modulepart parameter or try to use subdir parameter instead of ref.');
			}
		}
		// $original_file here is still value of filename without any dir.

		$upload_dir = dol_sanitizePathName($upload_dir);

		$destfile = $upload_dir . '/' . $original_file;
		$destfiletmp = DOL_DATA_ROOT.'/admin/temp/' . $original_file;
		dol_delete_file($destfiletmp);
		//var_dump($original_file);exit;

		if (!dol_is_dir(dirname($destfile))) {
			throw new RestException(401, 'Directory not exists : '.dirname($destfile));
		}

		if (! $overwriteifexists && dol_is_file($destfile))
		{
			throw new RestException(500, "File with name '".$original_file."' already exists.");
		}

		$fhandle = @fopen($destfiletmp, 'w');
		if ($fhandle)
		{
			$nbofbyteswrote = fwrite($fhandle, $newfilecontent);
			fclose($fhandle);
			@chmod($destfiletmp, octdec($conf->global->MAIN_UMASK));
		}
		else
		{
			throw new RestException(500, "Failed to open file '".$destfiletmp."' for write");
		}

		$result = dol_move($destfiletmp, $destfile, 0, $overwriteifexists, 1);
		if (! $result)
		{
			throw new RestException(500, "Failed to move file into '".$destfile."'");
		}

		return dol_basename($destfile);
	}

	/**
	 * Validate fields before create or update object
	 *
	 * @param   array           $data   Array with data to verify
	 * @return  array
	 * @throws  RestException
	 */
	function _validate_file($data) {
		$result = array();
		foreach (Documents::$DOCUMENT_FIELDS as $field) {
			if (!isset($data[$field]))
				throw new RestException(400, "$field field missing");
			$result[$field] = $data[$field];
		}
		return $result;
	}
}
