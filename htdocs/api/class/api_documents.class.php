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
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';

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
	 * Returns a document. Note that, this API is similar to using the wrapper link "documents.php" to download
	 * a file (used for internal HTML links of documents into application), but with no need to be into a logged session (no need to post the session cookie).
	 *
	 * @param   string  $module_part    Name of module or area concerned by file download ('facture', ...)
	 * @param   string  $original_file  Relative path with filename, relative to modulepart (for example: IN201701-999/IN201701-999.pdf)
	 * @param	int		$regeneratedoc	If requested document is the main document of an object, setting this to 1 ask API to regenerate document before returning it (supported for some module_part only). It is no effect in other cases.
	 * 									Also, note that setting this to 1 nead write access on object.
	 * @return  array                   List of documents
	 *
	 * @throws 500
	 * @throws 501
	 * @throws 400
	 * @throws 401
	 * @throws 200
	 */
	public function index($module_part, $original_file='', $regeneratedoc=0)
	{
		global $conf, $langs;

		if (empty($module_part)) {
				throw new RestException(400, 'bad value for parameter modulepart');
		}
		if (empty($original_file)) {
			throw new RestException(400, 'bad value for parameter ref or subdir');
		}

		//--- Finds and returns the document
		$entity=$conf->entity;

		$check_access = dol_check_secure_access_document($module_part, $original_file, $entity, DolibarrApiAccess::$user, '', ($regeneratedoc ? 'write' : 'read'));
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

		// --- Generates the document
		if ($regeneratedoc)
		{
			$hidedetails = empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 0 : 1;
			$hidedesc = empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ? 0 : 1;
			$hideref = empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 0 : 1;

			if ($module_part == 'facture' || $module_part == 'invoice')
			{
				require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
				$this->invoice = new Facture($this->db);
				$result = $this->invoice->fetch(0, preg_replace('/\.[^\.]+$/', '', basename($original_file)));
				if( ! $result ) {
					throw new RestException(404, 'Invoice not found');
				}
				$result = $this->invoice->generateDocument($this->invoice->modelpdf, $langs, $hidedetails, $hidedesc, $hideref);
				if( $result <= 0 ) {
					throw new RestException(500, 'Error generating document');
				}
			}
		}

		$filename = basename($original_file);
		$original_file_osencoded=dol_osencode($original_file);	// New file name encoded in OS encoding charset

		if (! file_exists($original_file_osencoded))
		{
			throw new RestException(404, 'File not found');
		}

		$file_content=file_get_contents($original_file_osencoded);
		return array('filename'=>$filename, 'content'=>base64_encode($file_content), 'encoding'=>'MIME base64 (base64_encode php function, http://php.net/manual/en/function.base64-encode.php)' );
	}

	/**
	 * Return the list of documents of a dedicated element (from its ID or Ref)
	 *
	 * @param   string 	$modulepart		Name of module or area concerned ('facture', 'project', 'member', ...)
	 * @param	int		$id				ID of element
	 * @param	string	$ref			Ref of element
	 * @param	string	$sortfield		Sort criteria ('','fullname','relativename','name','date','size')
	 * @param	string	$sortorder		Sort order ('asc' or 'desc')
	 * @return	array					Array of documents with path
	 *
	 * @throws RestException
	 *
	 * @url GET list
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
		else
		{
			throw new RestException(500, 'Modulepart '.$modulepart.' not implemented yet.');
		}

		$filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview.*\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
		if (empty($filearray)) {
			throw new RestException(404, 'Modulepart '.$modulepart.' with Id '.$object->id.(! empty($object->Ref)?' and Ref '.$object->ref:'').' does not have any documents.');
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
	 * Push a file.
	 * Test sample 1: { "filename": "mynewfile.txt", "modulepart": "facture", "ref": "FA1701-001", "subdir": "", "filecontent": "content text", "fileencoding": "", "overwriteifexists": "0" }.
	 * Test sample 2: { "filename": "mynewfile.txt", "modulepart": "medias", "ref": "", "subdir": "mysubdir1/mysubdir2", "filecontent": "content text", "fileencoding": "", "overwriteifexists": "0" }.
	 *
	 * @param   string  $filename           Name of file to create ('FA1705-0123')
	 * @param   string  $modulepart         Name of module or area concerned by file upload ('facture', 'project', 'project_task', ...)
	 * @param   string  $ref                Reference of object (This will define subdir automatically and store submited file into it)
	 * @param   string  $subdir             Subdirectory (Only if ref not provided)
	 * @param   string  $filecontent        File content (string with file content. An empty file will be created if this parameter is not provided)
	 * @param   string  $fileencoding       File encoding (''=no encoding, 'base64'=Base 64)
	 * @param   int 	$overwriteifexists  Overwrite file if exists (1 by default)
	 * @return  bool     				    State of copy
	 * @throws RestException
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
				$object = new Facture($this->db);
			}
			elseif ($modulepart == 'project')
			{
				$object = new Project($this->db);
			}
			elseif ($modulepart == 'task' || $modulepart == 'project_task')
			{
				$modulepart = 'project_task';
				$object = new Task($this->db);

				$task_result = $object->fetch('', $ref);

				// Fetching the tasks project is required because its out_dir might be a subdirectory of the project
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
					throw new RestException(500, "Object with ref '".$ref.'" was not found.');
			}
				elseif ($result < 0)
				{
					throw new RestException(500, 'Error while fetching object.');
				}
			}

			if (! ($object->id > 0))
			{
   				throw new RestException(500, 'The object '.$modulepart." with ref '".$ref."' was not found.");
			}

			$tmp = dol_check_secure_access_document($modulepart, $tmpreldir.dol_sanitizeFileName($object->ref), $entity, DolibarrApiAccess::$user, $ref, 'write');
			$upload_dir = $tmp['original_file'];

			if (empty($upload_dir) || $upload_dir == '/')
			{
				throw new RestException(500, 'This value of modulepart does not support yet usage of ref. Check modulepart parameter or try to use subdir parameter instead of ref.');
			}
		}
		else
		{
			if ($modulepart == 'invoice') $modulepart ='facture';

			$tmp = dol_check_secure_access_document($modulepart, $subdir, $entity, DolibarrApiAccess::$user, '', 'write');
			$upload_dir = $tmp['original_file'];

			if (empty($upload_dir) || $upload_dir == '/')
			{
				throw new RestException(500, 'This value of modulepart does not support yet usage of ref. Check modulepart parameter or try to use subdir parameter instead of ref.');
			}
		}


		$upload_dir = dol_sanitizePathName($upload_dir);

		$destfile = $upload_dir . '/' . $original_file;
		$destfiletmp = DOL_DATA_ROOT.'/admin/temp/' . $original_file;
		dol_delete_file($destfiletmp);

		if (!dol_is_dir($upload_dir)) {
			throw new RestException(401,'Directory not exists : '.$upload_dir);
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

		return $result;
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
