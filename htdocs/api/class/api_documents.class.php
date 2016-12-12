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
     *
     */
     public function get($module_part, $filename) {

     }


    /**
     * Receive file
     *
     * @param   array   $request_data   Request datas
     *
     * @return  bool     State of copy
     * @throws RestException
     */
    public function post($request_data) {
        global $conf;
        
        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

        if (!DolibarrApiAccess::$user->rights->ecm->upload) {
            throw new RestException(401);
        }

        // Suppression de la chaine de caractere ../ dans $original_file
		$original_file = str_replace("../","/", $request_data['name']);
        $refname = str_replace("../","/", $request_data['refname']);

		// find the subdirectory name as the reference
		if (empty($request_data['refname'])) $refname=basename(dirname($original_file)."/");

        // Security:
		// On interdit les remontees de repertoire ainsi que les pipe dans
		// les noms de fichiers.
		if (preg_match('/\.\./',$original_file) || preg_match('/[<>|]/',$original_file))
		{
            throw new RestException(401,'Refused to deliver file '.$original_file);
		}
        if (preg_match('/\.\./',$refname) || preg_match('/[<>|]/',$refname))
		{
            throw new RestException(401,'Refused to deliver file '.$refname);
		}

        $modulepart = $request_data['modulepart'];

        // Check mandatory fields
        $result = $this->_validate_file($request_data);

        $upload_dir = DOL_DATA_ROOT . '/' .$modulepart.'/'.dol_sanitizeFileName($refname);
        $destfile = $upload_dir . $original_file;

        if (!is_dir($upload_dir)) {
            throw new RestException(401,'Directory not exists : '.$upload_dir);
        }

        $file = $_FILES['file'];
        $srcfile = $file['tmp_name'];
        $res = dol_move($srcfile, $destfile, 0, 1);

        if (!$res) {
            throw new RestException(500);
        }

        return $res;
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
