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
        'name',
        'modulepart',
        'file'
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
     * Return a document
     *
     * @param   string  $module_part    Module part for file
     * @param   string  $filename       File name
     *
     * @return array
     * @throws RestException
     */
     /*
     public function get($module_part, $filename) {

     }*/


    /**
     * Push a file. 
     * Test sample: { "filename": "mynewfile.txt", "modulepart": "facture", "ref": "FA1701-001", "subdir": "", "filecontent": "content text", "fileencoding": "" }
     *
     * @param   string  $filename           Name of file to create ('FA1705-0123')
     * @param   string  $modulepart         Module part ('facture', ...)
     * @param   string  $ref                Reference of object (This will define subdir automatically and store submited file into it)
     * @param   string  $subdir             Subdirectory (Only if refname not provided)
     * @param   string  $filecontent        File content (string with file content. An empty file will be created if this parameter is not provided)
     * @param   string  $fileencoding       File encoding (''=no encoding, 'base64'=Base 64)
     * @return  bool     				    State of copy
     * @throws RestException
     */
    public function post($filename, $modulepart, $ref='', $subdir='', $filecontent='', $fileencoding='') {
        global $db, $conf;
        
        /*var_dump($modulepart);
        var_dump($filename);
        var_dump($filecontent);
        exit;*/
        
        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

        if (!DolibarrApiAccess::$user->rights->ecm->upload) {
            throw new RestException(401);
        }

        $newfilecontent = '';
        if (empty($fileencoding)) $newfilecontent = $filecontent;
        if ($fileencoding == 'base64') $newfilecontent = base64_decode($filecontent);

		$original_file = dol_sanitizeFileName($filename);

		// Define $uploadir
		$object = null;
		$entity = $user->entity;
		if ($ref)
		{
    		if ($modulepart == 'facture' || $modulepart == 'invoice')
    		{
    		    $modulepart='facture';
    		    $object=new Facture($db);
    		    $result = $object->fetch('', $ref);
    		    if (! ($result > 0))
    		    {
    		        throw new RestException(500, 'The object '.$modulepart." with ref '".$ref."' was not found.");
    		    }
    		    if (! empty($entity))
    		    {
    		        $tmpreldir = get_exdir(0, 0, 0, 0, $object, $modulepart);
                    $upload_dir = $conf->{$modulepart}->multidir_output[$entity].'/'.$tmpreldir.$object->ref;
    		    }
    		    else
    		    {
    		        $tmpreldir = get_exdir(0, 0, 0, 0, $object, $modulepart);
    		        $upload_dir = $conf->{$modulepart}->dir_output.'/'.$tmpreldir.$object->ref;
    		    }
    		}
    		
    		if (empty($upload_dir) || $upload_dir == '/')
    		{
    		    throw new RestException(500, 'This value of modulepart does not support yet usage of refname. Check modulepart parameter or try to use subdir parameter instead of ref.');
    		}
		}
		else
		{
		    if ($modulepart == 'invoice') $modulepart ='facture';
		    if (empty($conf->{$modulepart}->dir_output))
		    {
		        throw new RestException(500, 'This value of modulepart is not supported with refname not defined.');
		    }
		    $upload_dir = $conf->{$modulepart}->multidir_output[$entity];

		    if (empty($upload_dir) || $upload_dir == '/')
		    {
		        throw new RestException(500, 'This value of modulepart is not yet supported.');
		    }
		}
		$upload_dir = dol_sanitizePathName($upload_dir);
		
        // Security:
        // TODO Use dol_check_secure_access_document

        // Check mandatory fields
        //$result = $this->_validate_file($request_data);

        $destfile = $upload_dir . '/' . $original_file;

        if (!dol_is_dir($upload_dir)) {
            throw new RestException(401,'Directory not exists : '.$upload_dir);
        }

        if (dol_is_file($destfile))
        {
            throw new RestException(500, "File with name '".$original_file."' already exists.");
        }
        
        $fhandle = fopen($destfile, 'w');
        if ($fhandle)
        {
            $nbofbyteswrote = fwrite($fhandle, $newfilecontent);
            fclose($fhandle);
            @chmod($destfile, octdec($conf->global->MAIN_UMASK));
        }
        else
        {
            throw new RestException(500, 'Failed to open file for write');
        }
        
        return true;
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
